<?php
declare(strict_types=1);

final class PortalController
{
    private PortalSocial $model;

    // Este bloque prepara el modelo social y conserva los servicios compartidos.
    public function __construct(private PDO $conexion, private HttpController $http)
    {
        $this->model = new PortalSocial($conexion);
    }

    // Este bloque combina métricas, actividad reciente y solicitudes pendientes del portal.
    public function dashboard(): void
    {
        $this->http->requirePermission('portal.dashboard');
        $this->http->render('dashboard_licenciada.php', fn(string $html): string => $this->populateDashboard($html));
    }

    // Este bloque aplica filtros y renderiza el calendario respetando permisos de gestión.
    public function calendar(): void
    {
        $this->http->requirePermission('actividades.calendario');
        $this->http->render('calendario_actividades.php', function (string $html): string {
            $html = $this->populateCalendar($html);
            if (!$this->http->userCan('proyectos.crear')) {
                $html = preg_replace(
                    '~<a\b[^>]*href="cronograma_nuevo\.php"[^>]*>.*?</a>~is',
                    '',
                    $html,
                    1
                ) ?? $html;
            }

            return $html;
        });
    }

    // Este bloque consulta y presenta las respuestas de cuestionarios permitidas.
    public function responses(): void
    {
        $this->http->requirePermission('respuestas.consultar');
        $this->http->render('consulta_respuestas.php', fn(string $html): string => $this->populateResponses($html));
    }

    // Este bloque exporta a CSV las respuestas filtradas por el usuario.
    public function exportResponses(): never
    {
        $this->http->requirePermission('respuestas.consultar');
        $filters = $this->responseFilterInput();
        $summary = $this->model->responseSummary($filters);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="respuestas-cuestionarios.csv"');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new RuntimeException('No se pudo generar el archivo de respuestas.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Cuestionario', 'Estudiante', 'Carnet', 'Proyecto', 'Actividad', 'Fecha de respuesta', 'Calificación']);
        foreach ($summary['respuestas'] as $response) {
            fputcsv($output, [
                $response['titulo_cuestionario'],
                $response['estudiante'],
                $response['carnet'],
                $response['proyecto'] ?? '',
                $response['actividad'] ?? '',
                $response['fecha_respuesta'],
                $response['calificacion_general'] ?? '',
            ]);
        }
        fclose($output);
        exit;
    }

    // Este bloque lista plantillas y procesa sus acciones de administración.
    public function templates(): void
    {
        $this->http->requirePermission('notificaciones.gestionar');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->saveTemplate();
        }
        $this->http->render(
            'crear_plantilla.php',
            fn(string $html): string => $this->populateTemplates($html)
        );
    }

    // Este bloque muestra y guarda solicitudes propias del estudiante.
    public function requests(): void
    {
        $user = $this->http->requirePermission('solicitudes.consultar_propias');
        $studentId = $this->http->studentIdForUser((int) $user['usuario_id']);
        if ($studentId === null) {
            http_response_code(403);
            exit('La cuenta no tiene un expediente de estudiante asociado.');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->createRequest($studentId, (int) $user['usuario_id']);
        }
        $this->http->render(
            'mis_solicitudes.php',
            fn(string $html): string => $this->populateRequests($html, $studentId)
        );
    }

    // Este bloque revisa una solicitud, audita la decisión y notifica al estudiante.
    public function reviewRequest(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $reviewer = $this->http->requirePermission('solicitudes.revisar');
        $requestId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = (string) ($_POST['decision'] ?? '');
        $response = trim((string) ($_POST['respuesta'] ?? ''));
        if (!$requestId || !in_array($decision, ['Aprobada', 'Rechazada'], true)) {
            $this->http->rememberErrors(['acceso' => 'Los datos de revisión no son válidos.'], 'dashboard');
            $this->http->redirect('/dashboard_licenciada.php');
        }

        try {
            $updated = $this->model->reviewRequest(
                (int) $requestId,
                (int) $reviewer['usuario_id'],
                $decision,
                $response
            );
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'dashboard');
            $this->http->redirect('/dashboard_licenciada.php');
        }

        if (!$updated) {
            $this->http->rememberErrors(['acceso' => 'La solicitud ya fue atendida o no existe.'], 'dashboard');
        } else {
            (new Auditoria($this->conexion))->registrar(
                (int) $reviewer['usuario_id'],
                'solicitud_estudiante',
                (int) $requestId,
                'ACTUALIZAR',
                null,
                ['estado' => $decision, 'respuesta' => $response]
            );
            $this->model->notifyRequestStudent((int) $requestId);
            $this->http->rememberNotice('La solicitud se actualizó correctamente.');
        }
        $this->http->redirect('/dashboard_licenciada.php');
    }

    // Este bloque muestra los filtros y candidatos para emitir cartas o diplomas.
    public function documents(string $type): void
    {
        $this->http->requirePermission('documentos.generar');
        if (!in_array($type, ['diploma', 'carta'], true)) {
            http_response_code(404);
            exit('Tipo de documento no encontrado.');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->generateDocuments($type);
        }
        $view = $type === 'diploma' ? 'diplomas_generar.php' : 'cartas_participacion.php';
        $this->http->render(
            $view,
            fn(string $html): string => $this->populateDocuments($html, $type)
        );
    }

    // Este bloque autoriza la consulta e impresión de documentos ya emitidos.
    public function printDocuments(): void
    {
        $user = $this->http->requireAuthentication();
        $rawIds = (string) ($_GET['ids'] ?? $_GET['id'] ?? '');
        $ids = array_values(array_filter(
            array_map('intval', preg_split('/[,\s]+/', $rawIds) ?: []),
            static fn(int $id): bool => $id > 0
        ));
        if ($ids === []) {
            http_response_code(400);
            exit('Selecciona al menos un documento.');
        }

        $documents = $this->model->issuedDocuments($ids);
        if (count($documents) !== count(array_unique($ids))) {
            http_response_code(404);
            exit('No se encontraron todos los documentos solicitados.');
        }
        $canGenerate = $this->http->userCan('documentos.generar');
        foreach ($documents as $document) {
            if (
                !$canGenerate
                && !$this->http->userOwnsStudent((int) $document['estudiante_id'], (int) $user['usuario_id'])
            ) {
                http_response_code(403);
                exit('No tienes permiso para consultar este documento.');
            }
        }

        $this->renderIssuedDocuments($documents);
    }

    // Este bloque valida y guarda una plantilla de notificación interna.
    private function saveTemplate(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('notificaciones.gestionar');
        $data = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'tipo' => trim((string) ($_POST['tipo'] ?? '')),
            'asunto' => trim((string) ($_POST['asunto'] ?? '')),
            'mensaje' => trim((string) ($_POST['mensaje'] ?? '')),
        ];
        $errors = [];
        if ($data['nombre'] === '' || mb_strlen($data['nombre']) > 120) {
            $errors['nombre'] = 'Indica un nombre de plantilla de hasta 120 caracteres.';
        }
        if ($data['tipo'] !== 'Actividad') {
            $errors['tipo'] = 'Selecciona un tipo de notificación válido.';
        }
        if ($data['asunto'] === '' || mb_strlen($data['asunto']) > 180) {
            $errors['asunto'] = 'Indica un asunto de hasta 180 caracteres.';
        }
        if ($data['mensaje'] === '' || mb_strlen($data['mensaje']) > 12000) {
            $errors['mensaje'] = 'El mensaje es obligatorio y no debe exceder 12,000 caracteres.';
        }
        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'notification_template', $data);
            $this->http->redirect('/crear_plantilla.php');
        }

        $templateId = $this->model->saveTemplate(
            $data['nombre'],
            $data['tipo'],
            $data['asunto'],
            $data['mensaje'],
            (int) $user['usuario_id']
        );
        (new Auditoria($this->conexion))->registrar(
            (int) $user['usuario_id'],
            'plantilla_notificacion',
            $templateId,
            'CREAR',
            null,
            $data
        );
        $this->http->rememberNotice('La plantilla se guardó correctamente.');
        $this->http->redirect('/crear_plantilla.php');
    }

    // Este bloque valida y registra una nueva solicitud del estudiante.
    private function createRequest(int $studentId, int $userId): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $subject = trim((string) ($_POST['asunto'] ?? ''));
        $details = trim((string) ($_POST['detalle'] ?? ''));
        $projectInput = $_POST['proyecto_id'] ?? '';
        $projectId = $projectInput === '' ? null : filter_var($projectInput, FILTER_VALIDATE_INT);
        $errors = [];
        if ($subject === '' || mb_strlen($subject) > 180) {
            $errors['asunto'] = 'El asunto es obligatorio y no debe exceder 180 caracteres.';
        }
        if ($details === '' || mb_strlen($details) > 5000) {
            $errors['detalle'] = 'Describe la solicitud (máximo 5,000 caracteres).';
        }
        if ($projectInput !== '' && (!$projectId || !in_array(
            (int) $projectId,
            array_map(static fn(array $project): int => (int) $project['proyecto_id'], $this->model->activeProjects()),
            true
        ))) {
            $errors['proyecto_id'] = 'Selecciona un proyecto activo válido.';
        }
        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'student_request', $_POST);
            $this->http->redirect('/mis_solicitudes.php?nueva=1');
        }

        $requestId = $this->model->createRequest($studentId, $projectId === null ? null : (int) $projectId, $subject, $details);
        (new Auditoria($this->conexion))->registrar(
            $userId,
            'solicitud_estudiante',
            $requestId,
            'CREAR',
            null,
            ['asunto' => $subject, 'proyecto_id' => $projectId, 'detalle' => $details]
        );
        $this->http->rememberNotice('La solicitud se envió para revisión.');
        $this->http->redirect('/mis_solicitudes.php');
    }

    // Este bloque emite documentos para estudiantes elegibles y registra auditoría.
    private function generateDocuments(string $type): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('documentos.generar');
        $projectId = filter_var($_POST['proyecto_id'] ?? null, FILTER_VALIDATE_INT);
        $activityId = filter_var($_POST['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        $issueDate = trim((string) ($_POST['fecha_emision'] ?? ''));
        $studentIds = $_POST['estudiante_ids'] ?? [];
        if (!$projectId || !$activityId || !is_array($studentIds)) {
            $this->http->rememberErrors(['acceso' => 'Selecciona el proyecto, la actividad y los estudiantes.'], 'document');
            $this->http->redirect($this->documentRoute($type));
        }

        try {
            $ids = $this->model->generateDocuments(
                $type,
                (int) $projectId,
                (int) $activityId,
                $studentIds,
                $issueDate,
                (int) $user['usuario_id'],
                is_string($_POST['plantilla'] ?? null) ? (string) $_POST['plantilla'] : null
            );
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'document');
            $this->http->redirect($this->documentRoute($type, (int) $projectId, (int) $activityId, $issueDate));
        }
        foreach ($this->model->issuedDocuments($ids) as $document) {
            (new Auditoria($this->conexion))->registrar(
                (int) $user['usuario_id'],
                'documento_emitido',
                (int) $document['documento_id'],
                'CREAR',
                null,
                $document['contenido']
            );
        }

        $this->http->rememberNotice('Se emitieron ' . count($ids) . ' documento(s).');
        $this->http->redirect('/documento_generado.php?ids=' . implode(',', $ids));
    }

    // Este bloque inserta datos reales en las secciones del panel de proyección social.
    private function populateDashboard(string $html): string
    {
        $summary = $this->model->dashboardSummary();
        $statuses = $this->model->projectStatusCounts();
        $totalProjects = array_sum($statuses);
        $activePercent = $totalProjects > 0 ? (int) round(($statuses['Activo'] / $totalProjects) * 100) : 0;
        $pausedPercent = $totalProjects > 0 ? (int) round(($statuses['En Pausa'] / $totalProjects) * 100) : 0;
        $finishedPercent = max(0, 100 - $activePercent - $pausedPercent);
        $html = $this->replaceRegion($html, '<!-- KPIs -->', '<!-- Gráficas -->', $this->dashboardKpis($summary));
        $html = $this->replaceRegion($html, '<!-- Gráficas -->', '<!-- Actividades Próximas y Notificaciones -->', $this->dashboardCharts($statuses, $totalProjects, $activePercent, $pausedPercent, $finishedPercent));
        $html = $this->replaceRegion($html, '<!-- Actividades Próximas y Notificaciones -->', '</main>', $this->dashboardEvents());

        if ($this->http->userCan('solicitudes.revisar')) {
            $html = str_replace('</main>', $this->pendingRequestPanel() . '</main>', $html);
        }

        return $html;
    }

    // Este bloque representa indicadores numéricos del panel.
    private function dashboardKpis(array $summary): string
    {
        $items = [
            ['👥', '#fee2e2', 'var(--itca-red)', 'Estudiantes activos', $summary['estudiantes'], '/estudiantes_listado.php?estado=Activo'],
            ['📂', '#e0f2fe', '#0284c7', 'Proyectos activos', $summary['proyectos'], '/proyectos.php?estado=Activo'],
            ['📨', '#fef3c7', '#d97706', 'Solicitudes pendientes', $summary['solicitudes_pendientes'], '/solicitudes_revisar.php?estado=Pendiente'],
            ['⏱️', '#dcfce7', '#16a34a', 'Horas aprobadas', number_format((float) $summary['horas_aprobadas'], 2), '/horas_bandeja.php?estado=Aprobado'],
        ];
        $html = '<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">';
        foreach ($items as [$icon, $background, $color, $label, $count, $url]) {
            $canOpen = match ($label) {
                'Estudiantes activos' => $this->http->userCan('estudiantes.consultar'),
                'Proyectos activos' => $this->http->userCan('proyectos.consultar'),
                'Solicitudes pendientes' => $this->http->userCan('solicitudes.revisar'),
                'Horas aprobadas' => $this->http->userCan('horas.revisar'),
                default => false,
            };
            $html .= '<a class="kpi-card" style="color:inherit;text-decoration:none;"'
                . ($canOpen ? ' href="' . $url . '"' : '') . '><div class="kpi-icon" style="background:' . $background . ';color:' . $color . ';">'
                . $icon . '</div><div><span style="font-size:.8rem;color:var(--text-muted);">' . $label . '</span>'
                . '<h3 style="font-size:1.6rem;margin:.1rem 0;">' . $count . '</h3></div></a>';
        }

        return $html . '</div><p style="font-size:.8rem;color:var(--text-muted)">Datos consultados: '
            . $this->escape($summary['actualizado']) . '</p>';
    }

    // Este bloque convierte agregados de proyectos y meses en gráficos del panel.
    private function dashboardCharts(array $statuses, int $total, int $active, int $paused, int $finished): string
    {
        $gradient = 'conic-gradient(var(--itca-red) 0% ' . $active . '%, #f59e0b ' . $active . '% '
            . ($active + $paused) . '%, #9ca3af ' . ($active + $paused) . '% 100%)';
        $months = $this->model->monthlyActivityCounts();
        $max = max(1, max(array_column($months, 'cantidad')));
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $bars = '';
        foreach ($months as $month) {
            $height = $month['cantidad'] > 0 ? max(4, (int) round(115 * $month['cantidad'] / $max)) : 2;
            $monthNumber = (int) substr($month['periodo'], 5, 2);
            $bars .= '<div class="bar-col"><div class="bar-fill" title="' . (int) $month['cantidad'] . ' actividad(es)" style="height:' . $height . 'px;"></div>'
                . '<span style="font-size:.75rem;">' . $monthNames[$monthNumber - 1] . '</span></div>';
        }
        $activities = $this->model->upcomingActivities(5);
        $notifications = $this->model->recentNotifications(5);
        $activityRows = '';
        foreach ($activities as $activity) {
            $start = new DateTimeImmutable($activity['fecha_inicio']);
            $activityRows .= '<div class="event-row"><div style="display:flex;gap:.8rem;align-items:center;">'
                . '<div class="date-box">' . $this->escape(mb_strtoupper($start->format('M'))) . '<br><span style="font-size:1rem;">' . $start->format('d') . '</span></div>'
                . '<div><strong>' . $this->escape($activity['titulo']) . '</strong><br><span style="font-size:.8rem;color:var(--text-muted);">📍 '
                . $this->escape($activity['lugar']) . ' · ' . $this->escape($activity['proyecto']) . '</span></div></div>'
                . '<span style="font-size:.8rem;color:var(--text-muted);">' . $start->format('H:i') . '</span></div>';
        }
        if ($activityRows === '') {
            $activityRows = '<p class="empty-state">No hay actividades próximas programadas.</p>';
        }
        $notificationRows = '';
        foreach ($notifications as $notification) {
            $notificationRows .= '<div class="event-row"><div><strong>' . $this->escape($notification['asunto']) . '</strong><br>'
                . '<span style="font-size:.8rem;color:var(--text-muted);">' . $this->escape($notification['mensaje']) . '</span></div>'
                . '<span style="font-size:.75rem;color:var(--text-muted);">' . $this->escape($notification['nombre_completo']) . '</span></div>';
        }
        if ($notificationRows === '') {
            $notificationRows = '<p class="empty-state">No hay notificaciones recientes.</p>';
        }

        return '<div class="charts-grid"><div class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);margin-bottom:1.2rem;">📊 Estado de proyectos</h4>'
            . '<div style="display:flex;align-items:center;justify-content:space-around;"><div class="donut-chart" style="background:' . $gradient . ';">'
            . '<div class="donut-hole"><strong style="font-size:1.3rem;">' . $active . '%</strong><span style="font-size:.65rem;color:var(--text-muted);">Proyectos<br>activos</span></div></div>'
            . '<div style="font-size:.85rem;display:flex;flex-direction:column;gap:.7rem;"><div>En ejecución <strong>' . $statuses['Activo'] . ' (' . $active . '%)</strong></div>'
            . '<div>En pausa <strong>' . $statuses['En Pausa'] . ' (' . $paused . '%)</strong></div><div>Finalizados <strong>' . $statuses['Finalizado'] . ' (' . $finished . '%)</strong></div>'
            . ($total === 0 ? '<p>No hay proyectos registrados.</p>' : '') . '</div></div></div>'
            . '<div class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);margin-bottom:1rem;">📈 Actividades por mes</h4><div class="bar-chart">' . $bars . '</div></div></div>';
    }

    // Este bloque presenta actividades próximas y notificaciones recientes.
    private function dashboardEvents(): string
    {
        $activities = $this->model->upcomingActivities(3);
        $activityRows = '';
        foreach ($activities as $activity) {
            $date = new DateTimeImmutable($activity['fecha_inicio']);
            $activityRows .= '<div class="event-row"><div style="display:flex;gap:.8rem;align-items:center;"><div class="date-box">'
                . $this->escape(mb_strtoupper($date->format('M'))) . '<br><span style="font-size:1rem;">' . $date->format('d') . '</span></div>'
                . '<div><strong>' . $this->escape($activity['titulo']) . '</strong><br><span style="font-size:.8rem;color:var(--text-muted);">📍 '
                . $this->escape($activity['lugar']) . ' · ' . $this->escape($activity['proyecto']) . '</span></div></div><span style="font-size:.8rem;">'
                . $date->format('H:i') . '</span></div>';
        }
        if ($activityRows === '') {
            $activityRows = '<p class="empty-state">No hay actividades próximas.</p>';
        }
        $notifications = '';
        foreach ($this->model->recentNotifications(3) as $notification) {
            $subject = $this->friendlyNotificationText((string) $notification['asunto']);
            $message = $this->friendlyNotificationText((string) $notification['mensaje']);
            $date = new DateTimeImmutable($notification['fecha_creacion']);
            $notifications .= '<div class="event-row"><div><strong>' . $this->escape($subject !== '' ? $subject : 'Actualización del sistema') . '</strong><br><span style="font-size:.8rem;color:var(--text-muted);">'
                . $this->escape($message !== '' ? mb_strimwidth($message, 0, 180, '…') : 'Hay una actualización disponible.') . '</span></div><span style="font-size:.75rem;color:var(--text-muted);">'
                . $this->escape($date->format('d/m/Y H:i')) . '</span></div>';
        }
        if ($notifications === '') {
            $notifications = '<p class="empty-state">No hay avisos recientes.</p>';
        }

        return '<div class="charts-grid" style="margin-bottom:0;"><div class="form-card" style="margin:0;"><div style="display:flex;justify-content:space-between;margin-bottom:.8rem;">'
            . '<h4 style="color:var(--itca-red);">📅 Actividades próximas</h4><a href="/calendario_actividades.php">Ver calendario →</a></div>'
            . $activityRows . '</div><div class="form-card" style="margin:0;"><div style="display:flex;justify-content:space-between;margin-bottom:.8rem;">'
            . '<h4 style="color:var(--itca-red);">🔔 Notificaciones recientes</h4></div>'
            . $notifications . '</div></div>';
    }

    // Este bloque ofrece un acceso compacto a las solicitudes pendientes.
    private function pendingRequestPanel(): string
    {
        $requests = $this->model->pendingRequests();
        $count = count($requests);
        $countLabel = $count >= 20 ? '20+' : (string) $count;
        $message = $count > 0
            ? $countLabel . ' solicitudes esperan revisión.'
            : 'No hay solicitudes pendientes de revisión.';

        return '<section class="form-card" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">'
            . '<div><h2 style="margin:0 0 .25rem;">Solicitudes pendientes</h2><p style="margin:0;color:var(--text-muted);">'
            . $this->escape($message) . '</p></div><a class="btn-red" href="/solicitudes_revisar.php?estado=Pendiente">Revisar solicitudes →</a></section>';
    }

    private function friendlyNotificationText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('~(?:Detalle y calendario|Enlace|Abrir solicitud)\s*:\s*~iu', '', $text) ?? $text;
        $text = preg_replace('~https?://\S+|www\.\S+|/?[A-Za-z0-9_-]+\.php(?:\?[^\s]*)?~iu', '', $text) ?? $text;
        $text = preg_replace('~\b(?:estado(?:_envio)?|status|state)\s*[:=]\s*(?:Enviado|Pendiente|Fallido|Error|Reintento|Sin destinatarios|Sent|Failed|Delivered|Queued)\b[.,;]?~iu', '', $text) ?? $text;
        $text = preg_replace('~[\[\(](?:Enviado|Pendiente|Fallido|Error|Reintento|Sin destinatarios|Sent|Failed|Delivered|Queued)[\]\)]~iu', '', $text) ?? $text;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text, " \t\n\r\0\x0B-–—:;,.|");
    }

    // Este bloque arma el calendario y conserva los filtros de navegación.
    private function populateCalendar(string $html): string
    {
        $monthValue = (string) ($_GET['month'] ?? date('Y-m'));
        $month = DateTimeImmutable::createFromFormat('!Y-m', $monthValue);
        if ($month === false || $month->format('Y-m') !== $monthValue) {
            $month = new DateTimeImmutable('first day of this month');
            $monthValue = $month->format('Y-m');
        }
        $view = in_array($_GET['view'] ?? 'month', ['month', 'week', 'day'], true) ? (string) ($_GET['view'] ?? 'month') : 'month';
        $dayValue = (string) ($_GET['day'] ?? $month->format('Y-m-d'));
        $selectedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $dayValue);
        if ($selectedDate === false || $selectedDate->format('Y-m-d') !== $dayValue) {
            $selectedDate = $month;
        }
        if ($view === 'month') {
            $firstDay = $month->modify('first day of this month');
            $lastDay = $month->modify('last day of this month');
            $rangeStart = $firstDay->modify('-' . (int) $firstDay->format('w') . ' days');
            $rangeEnd = $lastDay->modify('+' . (6 - (int) $lastDay->format('w')) . ' days')->modify('+1 day');
        } elseif ($view === 'week') {
            $rangeStart = $selectedDate->modify('-' . (int) $selectedDate->format('w') . ' days');
            $rangeEnd = $rangeStart->modify('+7 days');
        } else {
            $rangeStart = $selectedDate;
            $rangeEnd = $selectedDate->modify('+1 day');
        }
        $canManageProjects = $this->http->userCan('proyectos.consultar');
        $filters = [
            'inicio' => $rangeStart->format('Y-m-d 00:00:00'),
            'fin' => $rangeEnd->format('Y-m-d 00:00:00'),
            'proyecto_id' => (int) ($_GET['proyecto_id'] ?? 0),
            'estado' => $canManageProjects ? (string) ($_GET['estado'] ?? '') : 'Activo',
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];
        $activities = $this->model->calendarActivities($filters);
        if ($filters['q'] !== '') {
            $needle = mb_strtolower($filters['q']);
            $activities = array_values(array_filter($activities, function (array $activity) use ($needle): bool {
                $text = mb_strtolower(implode(' ', [
                    (string) $activity['titulo'],
                    (string) $activity['proyecto'],
                    (string) $activity['descripcion'],
                    (string) $activity['lugar'],
                ]));

                return mb_strpos($text, $needle) !== false;
            }));
        }
        $byDate = [];
        foreach ($activities as $activity) {
            $date = substr($activity['fecha_inicio'], 0, 10);
            $byDate[$date][] = $activity;
        }
        $showResults = ($_GET['results'] ?? '') === '1';
        $controls = $this->calendarControls($month, $view, $selectedDate, $filters, $showResults)
            . $this->calendarFilterForm($monthValue, $view, $selectedDate, $filters, $canManageProjects, $showResults);
        $calendar = $showResults
            ? $this->calendarResults($activities, $filters, $canManageProjects)
            : $this->calendarTable($rangeStart, $rangeEnd, $month, $byDate, $view, $canManageProjects, $filters);
        $details = $showResults ? '</div>' : $this->calendarSidebar($selectedDate, $byDate, $canManageProjects);

        $html = $this->replaceRegion($html, '<!-- Barra de Controles del Calendario -->', '<!-- Cuadrícula Mensual -->', $controls);
        $html = $this->replaceRegion($html, '<!-- Cuadrícula Mensual -->', '<!-- Panel Lateral de Filtros y Detalle -->', $calendar);
        $html = $this->replaceRegion($html, '<!-- Panel Lateral de Filtros y Detalle -->', '</main>', $details);
        if ($showResults) {
            $html = str_replace('<div class="cal-layout">', '<div class="cal-layout results-view">', $html);
        }

        return $html;
    }

    // Este bloque prepara controles de mes, día y vista para el calendario.
    private function calendarControls(
        DateTimeImmutable $month,
        string $view,
        DateTimeImmutable $selectedDate,
        array $filters,
        bool $showResults
    ): string
    {
        $options = '';
        for ($offset = -6; $offset <= 6; $offset++) {
            $optionMonth = $month->modify(($offset < 0 ? '' : '+') . $offset . ' months');
            $value = $optionMonth->format('Y-m');
            $options .= '<option value="' . $value . '"' . ($value === $month->format('Y-m') ? ' selected' : '') . '>'
                . $this->escape($this->spanishMonth($optionMonth)) . '</option>';
        }
        $previous = $month->modify('-1 month')->format('Y-m');
        $next = $month->modify('+1 month')->format('Y-m');
        $day = $selectedDate->format('Y-m-d');
        $baseQuery = [
            'month' => $month->format('Y-m'),
            'view' => $view,
            'day' => $day,
            'proyecto_id' => $filters['proyecto_id'] ?: '',
            'estado' => $filters['estado'],
            'q' => $filters['q'],
            'results' => $showResults ? '1' : '',
        ];
        $previousQuery = $baseQuery;
        $previousQuery['month'] = $previous;
        $nextQuery = $baseQuery;
        $nextQuery['month'] = $next;
        $html = '<div class="form-card calendar-controls" style="padding:.8rem 1rem;margin-bottom:1.2rem;display:flex;justify-content:space-between;align-items:center;">'
            . '<div style="display:flex;gap:.5rem;align-items:center;"><a class="btn-gray" href="?' . $this->escape(http_build_query($previousQuery)) . '" aria-label="Mes anterior">&lt;</a>'
            . '<form method="GET" action="/calendario_actividades.php"><input type="hidden" name="view" value="' . $view . '"><input type="hidden" name="day" value="' . $day . '">'
            . '<input type="hidden" name="proyecto_id" value="' . (int) $filters['proyecto_id'] . '"><input type="hidden" name="estado" value="' . $this->escape($filters['estado']) . '">'
            . '<input type="hidden" name="q" value="' . $this->escape($filters['q']) . '"><input type="hidden" name="results" value="' . ($showResults ? '1' : '') . '">'
            . '<select name="month" aria-label="Mes">' . $options . '</select><button class="btn-gray" type="submit">Ir</button></form>'
            . '<a class="btn-gray" href="?' . $this->escape(http_build_query($nextQuery)) . '" aria-label="Mes siguiente">&gt;</a></div><div style="display:flex;gap:.4rem;">';
        foreach (['month' => 'Mes', 'week' => 'Semana', 'day' => 'Día'] as $mode => $label) {
            $class = $view === $mode ? 'btn-red' : 'btn-gray';
            $modeQuery = $baseQuery;
            $modeQuery['view'] = $mode;
            $html .= '<a class="' . $class . '" href="?' . $this->escape(http_build_query($modeQuery)) . '">' . $label . '</a>';
        }

        return $html . '</div></div>';
    }

    private function calendarFilterForm(
        string $month,
        string $view,
        DateTimeImmutable $selectedDate,
        array $filters,
        bool $canManageProjects,
        bool $showResults
    ): string {
        $projects = (new Proyecto($this->conexion))->all($canManageProjects ? [] : ['estado' => 'Activo']);
        $projectOptions = '<option value="">Todos los proyectos</option>';
        foreach ($projects as $project) {
            $selected = (int) $filters['proyecto_id'] === (int) $project['proyecto_id'] ? ' selected' : '';
            $projectOptions .= '<option value="' . (int) $project['proyecto_id'] . '"' . $selected . '>' . $this->escape($project['nombre']) . '</option>';
        }
        $statusOptions = $canManageProjects
            ? '<option value="">Todos los estados</option>'
            : '';
        foreach ($canManageProjects ? ['Activo', 'En Pausa', 'Finalizado', 'Inactivo'] : ['Activo'] as $status) {
            $statusOptions .= '<option value="' . $status . '"' . ($filters['estado'] === $status ? ' selected' : '') . '>' . $status . '</option>';
        }
        $calendarQuery = http_build_query([
            'month' => $month,
            'view' => $view,
            'day' => $selectedDate->format('Y-m-d'),
            'proyecto_id' => $filters['proyecto_id'] ?: '',
            'estado' => $filters['estado'],
            'q' => $filters['q'],
        ]);
        $statusField = $canManageProjects
            ? '<div class="form-group"><label for="activity-status">Estado del proyecto</label><select id="activity-status" name="estado">' . $statusOptions . '</select></div>'
            : '<input type="hidden" name="estado" value="Activo">';

        return '<form method="GET" action="/calendario_actividades.php" class="form-card calendar-filter-form">'
            . '<h4 style="color:var(--itca-red);margin:0;">Filtrar actividades</h4>'
            . '<input type="hidden" name="month" value="' . $this->escape($month) . '"><input type="hidden" name="view" value="' . $this->escape($view) . '">'
            . '<input type="hidden" name="day" value="' . $selectedDate->format('Y-m-d') . '">'
            . '<div class="form-group"><label for="activity-search">Buscar por actividad, proyecto o lugar</label><input id="activity-search" name="q" type="search" value="'
            . $this->escape($filters['q']) . '" placeholder="Nombre, proyecto o lugar"></div>'
            . '<div class="form-group"><label for="activity-project">Proyecto</label><select id="activity-project" name="proyecto_id">' . $projectOptions . '</select></div>'
            . $statusField . '<div class="calendar-filter-actions"><button class="btn-red" type="submit" name="results" value="1">Buscar actividades</button>'
            . '<a class="btn-gray" href="?' . $this->escape($calendarQuery) . '">Ver calendario</a></div></form>';
    }

    // Este bloque distribuye las actividades dentro de las fechas visibles.
    private function calendarTable(
        DateTimeImmutable $start,
        DateTimeImmutable $end,
        DateTimeImmutable $month,
        array $byDate,
        string $view,
        bool $canManageProjects,
        array $filters
    ): string
    {
        $html = '<div class="form-card" style="padding:.8rem;margin:0;"><table class="cal-table"><thead><tr>';
        foreach (['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $day) {
            $html .= '<th>' . $day . '</th>';
        }
        $html .= '</tr></thead><tbody><tr>';
        $date = $start;
        $weekday = (int) $date->format('w');
        for ($index = 0; $index < $weekday; $index++) {
            $html .= '<td class="muted"></td>';
        }
        $column = $weekday;
        while ($date < $end) {
            $key = $date->format('Y-m-d');
            $muted = $view === 'month' && $date->format('Y-m') !== $month->format('Y-m');
            $dayQuery = [
                'month' => $month->format('Y-m'),
                'view' => $view,
                'day' => $key,
                'proyecto_id' => $filters['proyecto_id'] ?: '',
                'estado' => $filters['estado'],
                'q' => $filters['q'],
            ];
            $html .= '<td' . ($muted ? ' class="muted"' : '') . '><a href="?' . $this->escape(http_build_query($dayQuery)) . '">'
                . $date->format('j') . '</a>';
            foreach ($byDate[$key] ?? [] as $activity) {
                $activityUrl = $canManageProjects
                    ? '/proyectos.php?id=' . (int) $activity['proyecto_id']
                    : '/catalogo_proyectos.php?' . http_build_query([
                        'q' => $activity['proyecto'],
                        'actividad_id' => (int) $activity['actividad_id'],
                    ]) . '#activity-' . (int) $activity['actividad_id'];
                $cancelled = $activity['fecha_cancelacion'] !== null;
                $eventClass = $cancelled ? 'ev-cancelled' : 'ev-blue';
                $eventTitle = $activity['proyecto'] . ' · ' . $activity['fecha_inicio'];
                if ($cancelled) {
                    $eventTitle .= ' · Cancelada: ' . $activity['motivo_cancelacion'];
                }
                $html .= '<a class="cal-ev ' . $eventClass . '" title="' . $this->escape($eventTitle)
                    . '" href="' . $this->escape($activityUrl) . '">' . ($cancelled ? 'Cancelada: ' : '')
                    . $this->escape($activity['titulo']) . '</a>';
            }
            $html .= '</td>';
            $date = $date->modify('+1 day');
            $column++;
            if ($column === 7 && $date < $end) {
                $html .= '</tr><tr>';
                $column = 0;
            }
        }
        while ($column > 0 && $column < 7) {
            $html .= '<td class="muted"></td>';
            $column++;
        }

        return $html . '</tr></tbody></table></div>';
    }

    private function calendarResults(array $activities, array $filters, bool $canManageProjects): string
    {
        $rows = '';
        foreach ($activities as $activity) {
            $activityUrl = $canManageProjects
                ? '/proyectos.php?id=' . (int) $activity['proyecto_id']
                : '/catalogo_proyectos.php?' . http_build_query([
                    'q' => $activity['proyecto'],
                    'actividad_id' => (int) $activity['actividad_id'],
                ]) . '#activity-' . (int) $activity['actividad_id'];
            $start = new DateTimeImmutable($activity['fecha_inicio']);
            $end = new DateTimeImmutable($activity['fecha_fin']);
            $cancelled = $activity['fecha_cancelacion'] !== null;
            $activityStatus = $cancelled
                ? '<span class="badge badge-cancelada">Cancelada</span><br><small>' . $this->escape($activity['motivo_cancelacion']) . '</small>'
                : '<span class="badge badge-activo">Programada</span>';
            $rows .= '<tr><td>' . $this->escape($start->format('d/m/Y')) . '<br><span style="color:var(--text-muted);font-size:.82rem;">'
                . $start->format('H:i') . '–' . $end->format('H:i') . '</span></td><td><strong>' . $this->escape($activity['titulo'])
                . '</strong><br><span style="color:var(--text-muted);font-size:.82rem;">' . $this->escape($activity['lugar']) . '</span></td><td>'
                . $this->escape($activity['proyecto']) . '</td><td>' . $this->escape($activity['estado']) . '</td><td><a href="'
                . $this->escape($activityUrl) . '">Ver detalle</a></td><td>' . $activityStatus . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="6" class="empty-state">No se encontraron actividades para estos filtros.</td></tr>';
        }
        $period = $filters['inicio'] !== '' ? substr($filters['inicio'], 0, 10) . ' – ' . date('Y-m-d', strtotime($filters['fin'] . ' -1 day')) : '';

        return '<section class="form-card calendar-results" style="padding:0;"><div style="padding:1rem;border-bottom:1px solid var(--border-color);">'
            . '<h3 style="margin:0;">Actividades encontradas</h3><span style="color:var(--text-muted);font-size:.85rem;">'
            . count($activities) . ' resultado(s) · ' . $this->escape($period) . '</span></div><div class="table-container"><table><thead><tr>'
            . '<th>Fecha y hora</th><th>Actividad</th><th>Proyecto</th><th>Estado del proyecto</th><th>Acción</th><th>Estado de actividad</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></section>';
    }

    // Este bloque presenta las actividades de la fecha elegida.
    private function calendarSidebar(
        DateTimeImmutable $selectedDate,
        array $byDate,
        bool $canManageProjects
    ): string
    {
        $selectedActivities = $byDate[$selectedDate->format('Y-m-d')] ?? [];
        $events = '';
        foreach ($selectedActivities as $activity) {
            $start = new DateTimeImmutable($activity['fecha_inicio']);
            $end = new DateTimeImmutable($activity['fecha_fin']);
            $activityUrl = $canManageProjects
                ? '/proyectos.php?id=' . (int) $activity['proyecto_id']
                : '/catalogo_proyectos.php?' . http_build_query([
                    'q' => $activity['proyecto'],
                    'actividad_id' => (int) $activity['actividad_id'],
                ]) . '#activity-' . (int) $activity['actividad_id'];
            $cancelled = $activity['fecha_cancelacion'] !== null;
            $events .= '<article style="background:#f9fafb;border:1px solid var(--border-color);border-radius:6px;padding:.8rem;margin-bottom:.6rem;">'
                . '<span style="font-size:.78rem;color:var(--text-muted);">🕘 ' . $start->format('H:i') . ' - ' . $end->format('H:i') . '</span>'
                . '<div style="font-weight:700;margin:.25rem 0;">' . $this->escape($activity['titulo'])
                . ($cancelled ? ' <span class="badge badge-cancelada">Cancelada</span>' : '') . '</div><div style="font-size:.8rem;color:var(--text-muted);">'
                . 'Proyecto: ' . $this->escape($activity['proyecto']) . '</div><span>' . $this->escape($activity['estado']) . '</span>'
                . ($cancelled ? '<p><strong>Motivo de cancelación:</strong> ' . $this->escape($activity['motivo_cancelacion']) . '</p>' : '')
                . '<p>' . $this->escape($activity['lugar']) . '</p><details><summary>Descripción y modalidad</summary><p>'
                . $this->escape($activity['descripcion']) . '</p><p>' . $this->escape($activity['modalidad']) . '</p></details>'
                . '<a href="' . $this->escape($activityUrl) . '">Ver detalle</a>'
                . (!$cancelled && !$canManageProjects && $this->http->userCan('asistencia.responder')
                    ? ' · <a href="/asistencia.php#actividad-' . (int) $activity['actividad_id'] . '">Responder asistencia</a>'
                    : '')
                . '</article>';
        }
        if ($events === '') {
            $events = '<p class="empty-state">No hay actividades para este día.</p>';
        }
        $dayHeading = $this->spanishDate($selectedDate);

        return '<div style="display:flex;flex-direction:column;gap:1rem;"><section class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);margin-bottom:.8rem;">'
            . '📅 Actividades del ' . $this->escape($dayHeading) . '</h4>' . $events . '</section>'
            . '<section class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);">Leyenda de eventos</h4><p>🔵 Actividad programada</p></section></div></div>';
    }

    // Este bloque aplica filtros, métricas y detalles a la pantalla de respuestas.
    private function populateResponses(string $html): string
    {
        $filters = $this->responseFilterInput();
        $lookup = $this->model->responseFilters();
        $summary = $this->model->responseSummary($filters);
        $view = ($_GET['vista'] ?? '') === 'estudiantes' ? 'estudiantes' : 'resumen';
        $detailId = filter_var($_GET['respuesta_id'] ?? null, FILTER_VALIDATE_INT);
        $details = $detailId && $detailId > 0 ? $this->model->responseDetails((int) $detailId) : null;
        if ($detailId && $details === null) {
            http_response_code(404);
        }
        $html = $this->replaceRegion($html, '<!-- Filtros Superiores -->', '<!-- KPIs -->', $this->responseFilterHtml($lookup, $filters));
        $html = $this->replaceRegion($html, '<!-- KPIs -->', '<!-- Pestañas -->', $this->responseKpis(
            $summary,
            $this->model->eligibleResponseCount($filters)
        ));
        $html = $this->replaceRegion($html, '<!-- Pestañas -->', '<!-- Gráficos -->', $this->responseTabs($filters, $view));
        $html = $this->replaceRegion($html, '<!-- Gráficos -->', '<!-- Tabla Detalle de Respuestas -->', $this->responseCharts($summary));
        $html = $this->replaceRegion($html, '<!-- Tabla Detalle de Respuestas -->', '</main>', $this->responseTable($summary, $filters, $view));
        if ($details !== null) {
            $html = str_replace('</main>', $this->responseDetailHtml($details) . '</main>', $html);
        }

        return $html;
    }

    // Este bloque obtiene y valida los filtros recibidos para consultar respuestas.
    private function responseFilterInput(): array
    {
        return [
            'cuestionario' => trim((string) ($_GET['cuestionario'] ?? '')),
            'proyecto_id' => trim((string) ($_GET['proyecto_id'] ?? '')),
            'actividad_id' => trim((string) ($_GET['actividad_id'] ?? '')),
        ];
    }

    // Este bloque construye el formulario de filtros con sus opciones actuales.
    private function responseFilterHtml(array $lookup, array $filters): string
    {
        $questionnaires = '<option value="">Todos los cuestionarios</option>';
        foreach ($lookup['cuestionarios'] as $title) {
            $questionnaires .= '<option value="' . $this->escape($title) . '"' . ($filters['cuestionario'] === $title ? ' selected' : '') . '>'
                . $this->escape($title) . '</option>';
        }
        $projects = '<option value="">Todos los proyectos</option>';
        foreach ($lookup['proyectos'] as $project) {
            $projects .= '<option value="' . (int) $project['proyecto_id'] . '"' . ($filters['proyecto_id'] === (string) $project['proyecto_id'] ? ' selected' : '') . '>'
                . $this->escape($project['nombre']) . '</option>';
        }
        $activities = '<option value="">Todas las actividades</option>';
        foreach ($lookup['actividades'] as $activity) {
            $activities .= '<option value="' . (int) $activity['actividad_id'] . '"' . ($filters['actividad_id'] === (string) $activity['actividad_id'] ? ' selected' : '') . '>'
                . $this->escape($activity['titulo']) . '</option>';
        }

        return '<form method="GET" action="/consulta_respuestas.php" class="form-card filter-bar"><div class="form-group"><label>Cuestionario</label><select name="cuestionario">'
            . $questionnaires . '</select></div><div class="form-group"><label>Proyecto</label><select name="proyecto_id">' . $projects
            . '</select></div><div class="form-group"><label>Actividad</label><select name="actividad_id">' . $activities
            . '</select></div><button class="btn-red" type="submit">🌪️ Aplicar filtros</button></form>';
    }

    // Este bloque presenta conteos de respuestas, preguntas y población elegible.
    private function responseKpis(array $summary, int $eligible): string
    {
        $latest = $summary['fecha'] === null ? '—' : (new DateTimeImmutable($summary['fecha']))->format('d/m/Y');
        $rate = $eligible > 0 ? min(100, (int) round($summary['total'] / $eligible * 100)) : 0;
        $items = [
            ['👥', (string) $summary['total'], 'Respuestas registradas'],
            ['📝', (string) $summary['preguntas'], 'Preguntas con respuesta'],
            ['✔️', $rate . ' %', 'Participantes que respondieron'],
            ['📅', $latest, 'Última respuesta'],
        ];
        $html = '<div class="kpi-grid">';
        foreach ($items as [$icon, $value, $label]) {
            $html .= '<div class="kpi-card"><div style="font-size:1.5rem;background:#fee2e2;color:var(--itca-red);padding:.5rem;border-radius:8px;">'
                . $icon . '</div><div><strong style="font-size:1.3rem;">' . $this->escape($value) . '</strong><br><span style="font-size:.78rem;color:var(--text-muted);">'
                . $this->escape($label) . '</span></div></div>';
        }

        return $html . '</div>';
    }

    // Este bloque permite alternar las vistas conservando filtros aplicados.
    private function responseTabs(array $filters, string $view): string
    {
        $query = array_filter($filters, static fn(string $value): bool => $value !== '');
        $summaryUrl = '/consulta_respuestas.php' . ($query === [] ? '' : '?' . $this->escape(http_build_query($query)));
        $studentQuery = $query;
        $studentQuery['vista'] = 'estudiantes';
        $studentUrl = '/consulta_respuestas.php?' . $this->escape(http_build_query($studentQuery));

        return '<div class="tabs"><a class="tab' . ($view === 'resumen' ? ' active' : '') . '" href="' . $summaryUrl . '">📊 Resumen general</a>'
            . '<a class="tab' . ($view === 'estudiantes' ? ' active' : '') . '" href="' . $studentUrl . '">👥 Respuestas por estudiante</a></div>';
    }

    // Este bloque agrupa respuestas y crea las distribuciones visuales.
    private function responseCharts(array $summary): string
    {
        $questions = array_values(array_unique(array_column($summary['answers'], 'pregunta')));
        $selectedQuestion = (string) ($_GET['pregunta'] ?? ($questions[0] ?? ''));
        if (!in_array($selectedQuestion, $questions, true)) {
            $selectedQuestion = $questions[0] ?? '';
        }
        $questionAnswers = array_values(array_filter(
            $summary['answers'],
            static fn(array $answer): bool => $answer['pregunta'] === $selectedQuestion
        ));
        $distribution = [];
        foreach ($questionAnswers as $answer) {
            $value = trim((string) $answer['respuesta']);
            $distribution[$value] = ($distribution[$value] ?? 0) + 1;
        }
        arsort($distribution);
        $question = $selectedQuestion !== '' ? $selectedQuestion : 'No hay respuestas registradas para estos filtros.';
        $questionOptions = '';
        foreach ($questions as $availableQuestion) {
            $questionOptions .= '<option value="' . $this->escape($availableQuestion) . '"'
                . ($availableQuestion === $selectedQuestion ? ' selected' : '') . '>' . $this->escape($availableQuestion) . '</option>';
        }
        $questionSelector = $questionOptions === '' ? '' : '<form method="GET" action="/consulta_respuestas.php">';
        if ($questionOptions !== '') {
            foreach ($this->responseFilterInput() as $key => $value) {
                if ($value !== '') {
                    $questionSelector .= '<input type="hidden" name="' . $this->escape($key) . '" value="' . $this->escape($value) . '">';
                }
            }
            $questionSelector .= '<label>Pregunta <select name="pregunta" onchange="this.form.submit()">' . $questionOptions . '</select></label></form>';
        }
        $legend = '';
        $colors = ['#22c55e', '#3b82f6', '#eab308', '#ef4444', '#a855f7', '#06b6d4'];
        foreach ($distribution as $answer => $count) {
            $percentage = $questionAnswers !== [] ? (int) round($count / count($questionAnswers) * 100) : 0;
            $legend .= '<div style="display:flex;justify-content:space-between;"><span>' . $this->escape($answer) . '</span><strong>'
                . $percentage . '% (' . $count . ')</strong></div>';
        }
        if ($legend === '') {
            $legend = '<p class="empty-state">No hay respuestas que mostrar.</p>';
        }
        $bars = '';
        $max = max(1, max($distribution ?: [1]));
        $colorIndex = 0;
        foreach ($distribution as $answer => $count) {
            $height = max(4, (int) round(100 * $count / $max));
            $color = $colors[$colorIndex++ % count($colors)];
            $bars .= '<div class="v-bar-item"><strong>' . $count . '</strong><div class="v-bar" style="height:' . $height . 'px;background:' . $color . ';"></div><span>'
                . $this->escape(mb_strimwidth($answer, 0, 18, '…')) . '</span></div>';
        }

        return '<div class="charts-grid"><div class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);">📋 Resultados de respuestas</h4>'
            . $questionSelector . '<p style="font-weight:600;">' . $this->escape($question) . '</p><div style="display:flex;justify-content:space-around;align-items:center;">'
            . '<div class="donut-resp"><div class="donut-hole"><strong>' . count($questionAnswers) . '</strong><span>Respuestas</span></div></div>'
            . '<div style="display:flex;flex-direction:column;gap:.5rem;width:55%;">' . $legend . '</div></div></div>'
            . '<div class="form-card" style="margin:0;"><h4 style="color:var(--itca-red);">📊 Distribución</h4><div class="v-bars">' . $bars . '</div></div></div>';
    }

    // Este bloque lista respuestas y ofrece exportación o acceso al detalle.
    private function responseTable(array $summary, array $filters, string $view): string
    {
        $rows = '';
        if ($view === 'estudiantes') {
            $students = [];
            foreach ($summary['respuestas'] as $response) {
                $studentId = (int) $response['estudiante_id'];
                if (!isset($students[$studentId])) {
                    $students[$studentId] = [
                        'estudiante' => $response['estudiante'],
                        'carnet' => $response['carnet'] ?? '',
                        'fecha_reciente' => $response['fecha_respuesta'],
                        'respuestas' => [],
                    ];
                }
                $students[$studentId]['respuestas'][] = $response;
            }
            foreach ($students as $student) {
                $links = [];
                foreach ($student['respuestas'] as $response) {
                    $query = array_filter($filters, static fn(string $value): bool => $value !== '');
                    $query['vista'] = 'estudiantes';
                    $query['respuesta_id'] = (int) $response['respuesta_id'];
                    $links[] = '<a href="/consulta_respuestas.php?' . $this->escape(http_build_query($query)) . '">'
                        . $this->escape($response['titulo_cuestionario']) . ' · ' . $this->escape($response['fecha_respuesta']) . '</a>';
                }
                $rows .= '<tr><td>' . $this->escape($student['estudiante']) . '</td><td>' . $this->escape($student['carnet'])
                    . '</td><td>' . count($student['respuestas']) . '</td><td>' . $this->escape($student['fecha_reciente'])
                    . '</td><td>' . implode('<br>', $links) . '</td></tr>';
            }
        } else {
            foreach ($summary['respuestas'] as $index => $response) {
                $rows .= '<tr><td>' . ($index + 1) . '</td><td>' . $this->escape($response['estudiante']) . '</td><td>'
                    . $this->escape($response['carnet'] ?? '') . '</td><td>' . $this->escape($response['fecha_respuesta']) . '</td><td>'
                    . $this->escape($response['calificacion_general'] ?? '—') . '</td><td><a class="btn-gray" href="/consulta_respuestas.php?'
                    . $this->escape(http_build_query(array_filter($filters, static fn(string $value): bool => $value !== '')))
                    . '&respuesta_id=' . (int) $response['respuesta_id'] . '">👁️ Ver respuestas</a></td></tr>';
            }
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="' . ($view === 'estudiantes' ? '5' : '6') . '">No hay respuestas para los filtros seleccionados.</td></tr>';
        }
        $export = '/respuestas_exportar.php?' . $this->escape(http_build_query(array_filter($filters, static fn(string $value): bool => $value !== '')));

        return '<div class="form-card" style="padding:0;overflow:hidden;"><div style="padding:1rem;background:var(--bg-main);display:flex;justify-content:space-between;">'
            . '<strong>📄 ' . ($view === 'estudiantes' ? 'Respuestas agrupadas por estudiante' : 'Detalle de respuestas') . '</strong><a class="btn-gray" href="' . $export . '">📥 Exportar CSV</a></div><div class="table-container"><table><thead><tr>'
            . ($view === 'estudiantes'
                ? '<th>Estudiante</th><th>Carné</th><th>Respuestas registradas</th><th>Última respuesta</th><th>Cuestionarios</th>'
                : '<th>#</th><th>Estudiante</th><th>Carné</th><th>Fecha de respuesta</th><th>Calificación general</th><th>Acciones</th>')
            . '</tr></thead><tbody>'
            . $rows . '</tbody></table></div></div>';
    }

    // Este bloque presenta respuestas individuales de un cuestionario.
    private function responseDetailHtml(array $details): string
    {
        $answers = '';
        foreach ($details['answers'] as $answer) {
            $answers .= '<tr><td>' . $this->escape($answer['pregunta']) . '</td><td>' . $this->escape($answer['respuesta']) . '</td></tr>';
        }
        if ($answers === '') {
            $answers = '<tr><td colspan="2">Esta respuesta no tiene detalle registrado.</td></tr>';
        }

        return '<section class="form-card"><h2>Respuesta de ' . $this->escape($details['estudiante']) . '</h2><p>'
            . $this->escape($details['titulo_cuestionario']) . ' · ' . $this->escape($details['actividad'] ?? 'Sin actividad vinculada') . '</p>'
            . '<table><thead><tr><th>Pregunta</th><th>Respuesta</th></tr></thead><tbody>' . $answers . '</tbody></table></section>';
    }

    // Este bloque llena la pantalla de plantillas con datos persistidos.
    private function populateTemplates(string $html): string
    {
        $template = $this->model->templates()[0] ?? [
            'nombre' => 'Nueva actividad',
            'tipo' => 'Actividad',
            'asunto' => 'Nueva actividad programada',
            'mensaje' => "Se ha registrado una nueva actividad en el proyecto {{nombre_proyecto}}.\n\nActividad: {{nombre_actividad}}\nFecha: {{fecha_actividad}}\nHora: {{hora_actividad}}\nLugar: {{lugar_actividad}}\n\nTe invitamos a participar y cumplir con tu asignación.",
        ];
        $form = '<form action="/crear_plantilla.php" method="POST"><div class="form-card" style="margin:0;">'
            . '<h4 style="color:var(--itca-red);margin-bottom:1rem;">Configuración administrativa</h4>'
            . '<div class="form-group"><label>Nombre de la plantilla *</label><input name="nombre" required maxlength="120" value="' . $this->escape($template['nombre']) . '"></div>'
            . '<div class="form-group"><label>Tipo de notificación *</label><select name="tipo" required><option>Actividad</option></select></div>'
            . '<div class="form-group"><label>Asunto *</label><input name="asunto" required maxlength="180" value="' . $this->escape($template['asunto']) . '"></div>'
            . '<div class="form-group"><label>Mensaje *</label><textarea name="mensaje" rows="8" required maxlength="12000">' . $this->escape($template['mensaje']) . '</textarea></div>'
            . '<p>Variables disponibles: {{nombre_proyecto}}, {{nombre_actividad}}, {{fecha_actividad}}, {{hora_actividad}}, {{lugar_actividad}}</p>'
            . '<button class="btn-red" type="submit">Guardar plantilla</button></div></form>';
        $html = $this->replaceRegion($html, '<!-- Formulario Izquierda -->', '<!-- Vista Previa Derecha -->', $form);
        $previewBody = '<div class="form-card" style="margin:0;background:#f8fafc;"><h4 style="color:var(--text-main);">Vista previa de la plantilla</h4>'
            . '<div style="background:white;padding:1.5rem;"><strong>ITCA-FEPADE</strong><h4>' . $this->escape($template['asunto']) . '</h4>'
            . '<p>' . nl2br($this->escape($template['mensaje'])) . '</p></div></div>';
        $html = $this->replaceRegion($html, '<!-- Vista Previa Derecha -->', '</div>' . PHP_EOL . '    </main>', $previewBody);
        $rows = '';
        foreach ($this->model->templates() as $saved) {
            $rows .= '<tr><td>' . $this->escape($saved['nombre']) . '</td><td>' . $this->escape($saved['tipo']) . '</td><td>'
                . $this->escape($saved['asunto']) . '</td><td>' . $this->escape(mb_strimwidth($saved['mensaje'], 0, 100, '…')) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="4">No hay plantillas guardadas.</td></tr>';
        }
        $listing = '<section class="form-card"><h3>Plantillas activas para los avisos</h3><div class="table-container"><table><thead><tr><th>Nombre</th><th>Tipo</th><th>Asunto</th><th>Mensaje</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></section>';

        return str_replace('</main>', $listing . '</main>', $html);
    }

    // Este bloque muestra los estados y detalles de solicitudes propias.
    private function populateRequests(string $html, int $studentId): string
    {
        $search = trim((string) ($_GET['q'] ?? ''));
        $counts = $this->model->requestCountsForStudent($studentId);
        $requestId = filter_var($_GET['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $detail = '';
        if ($requestId && $requestId > 0) {
            $request = $this->model->requestDetailsForStudent((int) $requestId, $studentId);
            if ($request === null) {
                http_response_code(404);
                $detail = '<section class="form-card"><h3>Solicitud no encontrada</h3></section>';
            } else {
                $detail = '<section class="form-card"><h3>' . $this->escape($request['asunto']) . '</h3><p>'
                    . $this->escape($request['proyecto'] ?? 'Solicitud general') . ' · ' . $this->escape($request['estado'])
                    . '</p><p>' . nl2br($this->escape($request['detalle'])) . '</p>'
                    . ($request['respuesta'] ? '<p><strong>Respuesta:</strong> ' . nl2br($this->escape($request['respuesta'])) . '</p>' : '')
                    . '<a class="btn-gray" href="/mis_solicitudes.php">Volver a mis solicitudes</a></section>';
            }
        }
        $form = '';
        if (($_GET['nueva'] ?? '') === '1') {
            $projects = '<option value="">Solicitud general</option>';
            foreach ($this->model->activeProjects() as $project) {
                $projects .= '<option value="' . (int) $project['proyecto_id'] . '">' . $this->escape($project['nombre']) . '</option>';
            }
            $form = '<section class="form-card"><h3>Nueva solicitud</h3><form action="/mis_solicitudes.php" method="POST">'
                . '<div class="form-group"><label>Asunto *</label><input name="asunto" required maxlength="180"></div>'
                . '<div class="form-group"><label>Proyecto relacionado</label><select name="proyecto_id">' . $projects . '</select></div>'
                . '<div class="form-group"><label>Detalle de la solicitud *</label><textarea name="detalle" rows="4" required maxlength="5000"></textarea></div>'
                . '<button class="btn-red" type="submit">Enviar solicitud</button></form></section>';
        }
        $cards = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">'
            . $this->requestStatCard('Total de solicitudes', $counts['total'], '#fee2e2', 'var(--itca-red)')
            . $this->requestStatCard('Aprobadas', $counts['Aprobada'], '#dcfce7', '#16a34a')
            . $this->requestStatCard('En revisión', $counts['Pendiente'], '#fef3c7', '#d97706')
            . $this->requestStatCard('Rechazadas', $counts['Rechazada'], '#fee2e2', '#dc2626') . '</div>' . $form;
        $html = $this->replaceRegion($html, '<!-- Tarjetas de Estadísticas -->', '<!-- Tabla de Solicitudes -->', $cards);
        if ($detail !== '') {
            $html = str_replace('<!-- Tabla de Solicitudes -->', $detail . '<!-- Tabla de Solicitudes -->', $html);
        }
        $html = preg_replace(
            '~<a href="#"(?=[^>]*>\+ Nueva solicitud)~',
            '<a href="/mis_solicitudes.php?nueva=1"',
            $html,
            1
        ) ?? $html;
        $requests = $this->model->requestsForStudent($studentId, $search);
        $rows = '';
        foreach ($requests as $request) {
            $id = (int) $request['solicitud_id'];
            $label = match ($request['estado']) {
                'Aprobada' => '✔️ Aprobada',
                'Rechazada' => '❌ Rechazada',
                default => '⏳ En revisión',
            };
            $background = $request['estado'] === 'Aprobada' ? '#dcfce7' : ($request['estado'] === 'Rechazada' ? '#fee2e2' : '#fef3c7');
            $rows .= '<tr><td>#' . $id . '</td><td>' . $this->escape($request['asunto']) . '</td><td>'
                . $this->escape($request['proyecto'] ?? 'General') . '</td><td>' . $this->escape($request['fecha_solicitud'])
                . '</td><td><span style="background:' . $background . ';padding:.2rem .6rem;border-radius:4px;">' . $label . '</span>'
                . ($request['respuesta'] ? '<br><small>' . $this->escape($request['respuesta']) . '</small>' : '')
                . '</td><td><a href="/mis_solicitudes.php?solicitud_id=' . $id . '">Ver detalle</a></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="6">No hay solicitudes que coincidan con la búsqueda.</td></tr>';
        }
        $table = '<div class="form-card" style="padding:0;overflow:hidden;"><div style="padding:1rem;background:var(--bg-main);display:flex;justify-content:space-between;">'
            . '<strong>Mis solicitudes registradas</strong><form method="GET" action="/mis_solicitudes.php"><input type="search" name="q" placeholder="Buscar por asunto o proyecto..." value="'
            . $this->escape($search) . '"><button type="submit">Buscar</button></form></div><div class="table-container"><table><thead><tr>'
            . '<th>N.°</th><th>Asunto</th><th>Proyecto</th><th>Fecha de solicitud</th><th>Estado y respuesta</th><th>Acciones</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div></div>';

        return $this->replaceRegion($html, '<!-- Tabla de Solicitudes -->', '</main>', $table);
    }

    // Este bloque genera una tarjeta para resumir un estado de solicitud.
    private function requestStatCard(string $label, int $value, string $background, string $color): string
    {
        return '<div class="form-card" style="padding:1rem;margin:0;"><span style="color:var(--text-muted);font-size:.8rem;">'
            . $this->escape($label) . '</span><h3 style="font-size:1.5rem;margin:0;color:' . $color . ';">' . $value . '</h3></div>';
    }

    // Este bloque llena los formularios y las listas de documentos emitibles.
    private function populateDocuments(string $html, string $type): string
    {
        $projectId = filter_var($_GET['proyecto_id'] ?? null, FILTER_VALIDATE_INT);
        $projectId = $projectId && $projectId > 0 ? (int) $projectId : 0;
        $projects = (new Proyecto($this->conexion))->all([]);
        if ($projectId === 0 && $projects !== []) {
            $projectId = (int) $projects[0]['proyecto_id'];
        }
        $activityId = filter_var($_GET['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        $activityId = $activityId && $activityId > 0 ? (int) $activityId : 0;
        $activities = $projectId > 0 ? (new Proyecto($this->conexion))->activities($projectId) : [];
        if ($activityId === 0 && $activities !== []) {
            $activityId = (int) $activities[0]['actividad_id'];
        }
        $date = trim((string) ($_GET['fecha_emision'] ?? date('Y-m-d')));
        if (DateTimeImmutable::createFromFormat('!Y-m-d', $date) === false) {
            $date = date('Y-m-d');
        }
        $selectedProject = null;
        foreach ($projects as $project) {
            if ((int) $project['proyecto_id'] === $projectId) {
                $selectedProject = $project;
                break;
            }
        }
        $selectedActivity = null;
        foreach ($activities as $activity) {
            if ((int) $activity['actividad_id'] === $activityId) {
                $selectedActivity = $activity;
                break;
            }
        }
        $candidates = $selectedProject !== null && $selectedActivity !== null
            ? $this->model->documentCandidates($projectId, $activityId)
            : [];
        $activityCancelled = $selectedActivity !== null && $selectedActivity['fecha_cancelacion'] !== null;
        $title = $type === 'diploma' ? 'DIPLOMA' : 'CARTA DE PARTICIPACIÓN';
        $projectOptions = '';
        foreach ($projects as $project) {
            $projectOptions .= '<option value="' . (int) $project['proyecto_id'] . '"' . ((int) $project['proyecto_id'] === $projectId ? ' selected' : '') . '>'
                . $this->escape($project['nombre']) . '</option>';
        }
        $activityOptions = '';
        foreach ($activities as $activity) {
            $activityLabel = $activity['titulo'];
            if ($activity['fecha_cancelacion'] !== null) {
                $activityLabel .= ' · Cancelada';
            }
            $activityOptions .= '<option value="' . (int) $activity['actividad_id'] . '"' . ((int) $activity['actividad_id'] === $activityId ? ' selected' : '') . '>'
                . $this->escape($activityLabel) . '</option>';
        }
        $projectOptions = $projectOptions !== '' ? $projectOptions : '<option value="">No hay proyectos</option>';
        $activityOptions = $activityOptions !== '' ? $activityOptions : '<option value="">No hay actividades</option>';
        $previewCandidate = $candidates[0] ?? null;
        $period = $selectedProject === null
            ? 'Periodo del proyecto'
            : $this->escape($selectedProject['fecha_inicio']) . ' – ' . $this->escape($selectedProject['fecha_fin'] ?? 'Sin fecha final');
        $recordDetails = $previewCandidate === null
            ? '<p>Carné, carrera, año académico y modalidad de participación del expediente.</p>'
            : '<p><strong>Carné:</strong> ' . $this->escape($previewCandidate['carnet'] ?? '')
                . '</p><p><strong>Carrera:</strong> ' . $this->escape($previewCandidate['carrera'])
                . ' · <strong>Año académico:</strong> ' . (int) $previewCandidate['anio_academico']
                . '</p><p><strong>Modalidad de participación:</strong> ' . $this->escape($previewCandidate['modalidad_participacion'])
                . '</p><p><strong>Modalidad de actividad:</strong> ' . $this->escape($selectedActivity['modalidad'] ?? '')
                . '</p>';
        $configuration = '<form method="GET" action="' . $this->documentRoute($type) . '" class="split-grid"><div class="form-card" style="margin:0;">'
            . '<h4>⚙️ Configuración del documento</h4><div class="form-group"><label>Proyecto *</label><select name="proyecto_id" required>'
            . $projectOptions . '</select></div><div class="form-group"><label>Actividad *</label><select name="actividad_id" required>'
            . $activityOptions . '</select></div><div class="form-group"><label>Plantilla *</label><select name="plantilla"><option value="' . $type . '_institucional">'
            . ($type === 'diploma' ? 'Diploma institucional (ITCA-FEPADE)' : 'Carta de participación institucional') . '</option></select></div>'
            . '<div class="form-group"><label>Fecha de emisión *</label><input type="date" name="fecha_emision" value="' . $this->escape($date) . '" required></div>'
            . '<button class="btn-red" type="submit">Cargar participantes</button></div><div class="form-card" style="margin:0;background:#f8fafc;">'
            . '<h4>👁️ Vista previa</h4>'
            . ($activityCancelled ? '<p class="alert alert-error">Actividad cancelada: ' . $this->escape($selectedActivity['motivo_cancelacion']) . '. No se pueden emitir documentos.</p>' : '')
            . '<div class="cert-preview"><div class="cert-banner">ITCA-FEPADE</div><div class="cert-body"><div class="cert-title">'
            . $title . '</div><div>Se hace constar que:</div><div class="cert-student">' . $this->escape($candidates[0]['nombre_completo'] ?? 'Nombre del estudiante') . '</div>'
            . '<p class="cert-text">Ha participado en <strong>' . $this->escape($selectedActivity['titulo'] ?? 'la actividad') . '</strong> en el proyecto <strong>'
            . $this->escape($selectedProject['nombre'] ?? 'el proyecto') . '</strong>, con horas aprobadas registradas.</p>'
            . $recordDetails . '<p><strong>Periodo del proyecto:</strong> ' . $period . '</p><p><strong>Plantilla:</strong> '
            . $this->escape($type . '_institucional') . ' · versión ' . PortalSocial::DOCUMENT_TEMPLATE_VERSION
            . '</p><div class="cert-signatures"><div class="sig-line">Proyección Social</div><div class="seal-badge">★</div><div class="sig-line">Responsable</div></div></div></div></div></form>';
        $rows = '';
        foreach ($candidates as $candidate) {
            $alreadyIssued = $this->documentAlreadyIssued($type, (int) $candidate['estudiante_id'], $activityId);
            $checked = $alreadyIssued ? '' : ' checked';
            $rows .= '<tr><td data-label="Seleccionar"><input aria-label="Seleccionar ' . $this->escape($candidate['nombre_completo'])
                . '" type="checkbox" name="estudiante_ids[]" value="' . (int) $candidate['estudiante_id'] . '"' . $checked
                . ($alreadyIssued ? ' disabled' : '') . '></td><td data-label="Carnet">' . $this->escape($candidate['carnet'] ?? '') . '</td><td data-label="Estudiante">'
                . $this->escape($candidate['nombre_completo']) . '</td><td data-label="Proyecto">' . $this->escape($candidate['proyecto']) . '</td><td data-label="Actividad">'
                . $this->escape($candidate['actividad']) . '</td><td data-label="Elegibilidad">' . number_format((float) $candidate['horas_aprobadas'], 2) . ' horas aprobadas'
                . ($alreadyIssued ? ' · Emitido' : '') . '</td></tr>';
        }
        if ($rows === '') {
            $emptyMessage = $activityCancelled
                ? 'La actividad está cancelada; no se pueden emitir documentos.'
                : 'No hay estudiantes activos con horas aprobadas para esta actividad.';
            $rows = '<tr><td colspan="6">' . $this->escape($emptyMessage) . '</td></tr>';
        }
        $issuedRows = '';
        if ($selectedProject !== null && $selectedActivity !== null) {
            foreach ($this->model->issuedDocumentsForActivity($type, $projectId, $activityId) as $document) {
                $contents = $document['contenido'];
                $issuedRows .= '<tr><td>' . $this->escape($contents['estudiante'] ?? 'Estudiante') . '</td><td>'
                    . $this->escape($contents['carnet'] ?? '') . '</td><td>' . $this->escape($document['fecha_emision'])
                    . '</td><td><a class="btn-gray" href="/documento_generado.php?id=' . (int) $document['documento_id']
                    . '">Abrir / imprimir</a></td></tr>';
            }
        }
        if ($issuedRows === '') {
            $issuedRows = '<tr><td colspan="4">No hay documentos emitidos para esta actividad.</td></tr>';
        }
        $table = '<form action="' . $this->documentRoute($type) . '" method="POST"><input type="hidden" name="proyecto_id" value="' . $projectId . '">'
            . '<input type="hidden" name="actividad_id" value="' . $activityId . '"><input type="hidden" name="fecha_emision" value="' . $this->escape($date) . '">'
            . '<input type="hidden" name="plantilla" value="' . $this->escape($type . '_institucional') . '">'
            . '<section class="form-card document-candidates" style="padding:0;overflow:hidden;"><h3 style="padding:1rem;">👥 Estudiantes elegibles para ' . $this->escape($title) . '</h3>'
            . '<div class="table-container"><table><thead><tr><th>Seleccionar</th><th>Carnet</th><th>Estudiante</th><th>Proyecto</th><th>Actividad</th><th>Elegibilidad</th></tr></thead><tbody>'
            . $rows . '</tbody></table></div><div style="padding:1rem;display:flex;justify-content:flex-end;"><button class="btn-red" type="submit">📥 Generar ' . $this->escape($title) . '</button></div></section></form>';
        $issued = '<section class="form-card"><h3>Documentos ya emitidos</h3><div class="table-container"><table><thead><tr><th>Estudiante</th><th>Carné</th><th>Fecha de emisión</th><th>Acciones</th></tr></thead><tbody>'
            . $issuedRows . '</tbody></table></div></section>';
        $content = $configuration . $table . $issued;

        return $this->replaceRegion($html, '<div class="split-grid">', '</main>', $content);
    }

    // Este bloque comprueba si el estudiante ya recibió ese tipo de documento.
    private function documentAlreadyIssued(string $type, int $studentId, int $activityId): bool
    {
        $query = $this->conexion->prepare(
            'SELECT 1 FROM documentos_emitidos WHERE tipo = :tipo AND estudiante_id = :estudiante_id AND actividad_id = :actividad_id'
        );
        $query->execute(['tipo' => $type, 'estudiante_id' => $studentId, 'actividad_id' => $activityId]);

        return $query->fetchColumn() !== false;
    }

    // Este bloque presenta las copias históricas de documentos listos para imprimir.
    private function renderIssuedDocuments(array $documents): never
    {
        header('Content-Type: text/html; charset=UTF-8');
        $html = '<!doctype html><html lang="es"><meta charset="utf-8"><title>Documento institucional</title>'
            . '<style>body{font-family:Arial,sans-serif;margin:2rem;color:#222}.toolbar{margin-bottom:2rem}.certificate{border:3px double #991b1b;padding:3rem;margin:2rem auto;max-width:900px;text-align:center;page-break-after:always}.name{font:italic bold 2rem Georgia,serif;margin:2rem}.signatures{display:flex;justify-content:space-around;margin-top:4rem}.signature{border-top:1px solid #444;padding-top:.5rem;width:220px}@media print{.toolbar{display:none}.certificate{break-after:page}}</style>'
            . '<body><div class="toolbar"><button onclick="window.print()">Imprimir / Guardar como PDF</button> <a href="javascript:history.back()">Volver</a></div>';
        foreach ($documents as $document) {
            $data = $document['contenido'];
            $title = $document['tipo'] === 'diploma' ? 'DIPLOMA' : 'CARTA DE PARTICIPACIÓN';
            $period = $data['periodo_proyecto'] ?? [];
            $templateCode = $document['plantilla_codigo'] ?? $data['plantilla_codigo'] ?? '';
            $templateVersion = $document['plantilla_version'] ?? $data['plantilla_version'] ?? '';
            $html .= '<article class="certificate"><h2>ITCA-FEPADE</h2><h1>' . $title . '</h1><p>Se hace constar que</p><div class="name">'
                . $this->escape($data['estudiante']) . '</div><p>participó en la actividad <strong>' . $this->escape($data['actividad'])
                . '</strong> del proyecto <strong>' . $this->escape($data['proyecto']) . '</strong>.</p><p>Horas aprobadas: '
                . number_format((float) $data['horas_aprobadas'], 2) . '</p><p>Carné: ' . $this->escape($data['carnet'] ?? '')
                . ' · Carrera: ' . $this->escape($data['carrera'] ?? '')
                . ' · Año académico: ' . $this->escape((string) ($data['anio_academico'] ?? ''))
                . '</p><p>Modalidad de participación: ' . $this->escape($data['modalidad_participacion'] ?? '')
                . ' · Modalidad de actividad: ' . $this->escape($data['modalidad_actividad'] ?? '')
                . '</p><p>Periodo del proyecto: ' . $this->escape($period['inicio'] ?? '')
                . ' – ' . $this->escape($period['fin'] ?? 'Sin fecha final')
                . '</p><p>Emitido el ' . $this->escape($data['fecha_emision'])
                . ' · Plantilla ' . $this->escape((string) $templateCode) . ' · Versión ' . $this->escape((string) $templateVersion)
                . '</p><div class="signatures"><div class="signature">Proyección Social</div><div class="signature">Responsable institucional</div></div></article>';
        }
        echo $html . '</body></html>';
        exit;
    }

    // Este bloque sustituye una región delimitada de una vista sin tocar el resto.
    private function replaceRegion(string $html, string $start, string $end, string $replacement): string
    {
        $startPosition = strpos($html, $start);
        if ($startPosition === false) {
            throw new RuntimeException('No se encontró el marcador de vista ' . $start . '.');
        }
        $contentStart = $startPosition + strlen($start);
        $endPosition = strpos($html, $end, $contentStart);
        if ($endPosition === false) {
            throw new RuntimeException('No se encontró el cierre de región de vista ' . $end . '.');
        }

        return substr($html, 0, $contentStart) . PHP_EOL . $replacement . PHP_EOL . substr($html, $endPosition);
    }

    // Este bloque escapa valores dinámicos antes de insertarlos en HTML.
    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Este bloque traduce el mes para los rótulos del calendario.
    private function spanishMonth(DateTimeImmutable $date): string
    {
        $months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return ucfirst($months[(int) $date->format('n') - 1]) . ' de ' . $date->format('Y');
    }

    // Este bloque presenta una fecha con nombres de día y mes en español.
    private function spanishDate(DateTimeImmutable $date): string
    {
        $days = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
        return $days[(int) $date->format('w')] . ' ' . $date->format('d') . ' de ' . $this->spanishMonth($date);
    }

    // Este bloque genera rutas de emisión manteniendo los filtros seleccionados.
    private function documentRoute(string $type, int $projectId = 0, int $activityId = 0, string $date = ''): string
    {
        $route = $type === 'diploma' ? '/diplomas_generar.php' : '/cartas_participacion.php';
        if ($projectId === 0) {
            return $route;
        }

        return $route . '?' . http_build_query([
            'proyecto_id' => $projectId,
            'actividad_id' => $activityId,
            'fecha_emision' => $date,
        ]);
    }
}
