<?php
declare(strict_types=1);

final class WorkflowController
{
    private Participacion $model;

    // Este bloque prepara el modelo de participación y los controles HTTP comunes.
    public function __construct(private PDO $conexion, private HttpController $http)
    {
        $this->model = new Participacion($conexion);
    }

    // Este bloque administra criterios y presenta evaluaciones según los permisos del usuario.
    public function evaluations(): void
    {
        $this->http->requirePermission('evaluaciones.crear');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $user = $this->http->requirePermission('evaluaciones.criterios');
            $name = trim((string) ($_POST['nombre'] ?? ''));
            try {
                $this->model->saveCriterion(
                    $name,
                    (string) ($_POST['descripcion'] ?? ''),
                    (int) ($_POST['escala_min'] ?? -1),
                    (int) ($_POST['escala_max'] ?? -1),
                    isset($_POST['obligatorio']),
                    (int) $user['usuario_id']
                );
                $this->http->rememberNotice('El criterio quedó activo para nuevas evaluaciones.');
            } catch (InvalidArgumentException $error) {
                $this->http->rememberErrors(['acceso' => $error->getMessage()]);
            } catch (PDOException $error) {
                if ($error->getCode() !== '23000') {
                    throw $error;
                }
                $this->http->rememberErrors(['acceso' => 'Ya existe un criterio con ese nombre.']);
            }
            $this->http->redirect('/evaluaciones.php');
        }

        $this->renderPage('Evaluaciones de participación', $this->evaluationPage());
    }

    // Este bloque inicia una evaluación para una asignación activa del estudiante.
    public function createEvaluation(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('evaluaciones.crear');
        $participant = $_POST['participante'] ?? '';
        $studentId = false;
        $projectId = false;
        try {
            if (!is_string($participant)
                || preg_match('/^([1-9][0-9]*):([1-9][0-9]*)$/D', $participant, $parts) !== 1
                || ($studentId = filter_var($parts[1], FILTER_VALIDATE_INT)) === false
                || ($projectId = filter_var($parts[2], FILTER_VALIDATE_INT)) === false) {
                throw new InvalidArgumentException('Selecciona el estudiante y el proyecto.');
            }
            $id = $this->model->createEvaluation(
                (int) $studentId,
                (int) $projectId,
                (string) ($_POST['periodo'] ?? ''),
                (int) $user['usuario_id']
            );
            $this->http->redirect('/evaluaciones.php?id=' . $id);
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()]);
            $this->http->redirect('/evaluaciones.php');
        }
    }

    // Este bloque activa o desactiva un criterio para evaluaciones futuras.
    public function toggleCriterion(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('evaluaciones.criterios');
        $criterionId = filter_var($_POST['criterio_id'] ?? null, FILTER_VALIDATE_INT);
        $active = ($_POST['activo'] ?? '') === '1';
        if (!$criterionId || !$this->model->setCriterionActive((int) $criterionId, $active)) {
            $this->http->rememberErrors(['acceso' => 'No se pudo actualizar el criterio seleccionado.']);
        } else {
            (new Auditoria($this->conexion))->registrar(
                (int) $user['usuario_id'],
                'evaluacion_criterio',
                (int) $criterionId,
                'CAMBIAR_ESTADO',
                null,
                ['activo' => $active]
            );
            $this->http->rememberNotice('El estado del criterio se actualizó.');
        }
        $this->http->redirect('/evaluaciones.php');
    }

    // Este bloque guarda el borrador y deja constancia de la actualización.
    public function saveEvaluation(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('evaluaciones.crear');
        $evaluationId = filter_var($_POST['evaluacion_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$evaluationId) {
            $this->http->rememberErrors(['acceso' => 'Selecciona una evaluación válida.']);
            $this->http->redirect('/evaluaciones.php');
        }
        try {
            $this->model->saveEvaluation(
                (int) $evaluationId,
                is_array($_POST['puntuaciones'] ?? null) ? $_POST['puntuaciones'] : [],
                (string) ($_POST['comentarios'] ?? ''),
                (int) $user['usuario_id']
            );
            (new Auditoria($this->conexion))->registrar(
                (int) $user['usuario_id'],
                'evaluacion_estudiante',
                (int) $evaluationId,
                'ACTUALIZAR',
                null,
                ['estado' => 'Borrador']
            );
            $this->http->rememberNotice('El borrador de evaluación se guardó.');
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()]);
        }
        $this->http->redirect('/evaluaciones.php?id=' . (int) $evaluationId);
    }

    // Este bloque cierra una evaluación completa y registra su responsable.
    public function closeEvaluation(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requirePermission('evaluaciones.crear');
        $evaluationId = filter_var($_POST['evaluacion_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$evaluationId) {
            $this->http->rememberErrors(['acceso' => 'Selecciona una evaluación válida.']);
            $this->http->redirect('/evaluaciones.php');
        }
        try {
            $this->model->closeEvaluation((int) $evaluationId, (int) $user['usuario_id']);
            (new Auditoria($this->conexion))->registrar(
                (int) $user['usuario_id'],
                'evaluacion_estudiante',
                (int) $evaluationId,
                'ACTUALIZAR',
                ['estado' => 'Borrador'],
                ['estado' => 'Cerrada']
            );
            $this->http->rememberNotice('La evaluación quedó cerrada con fecha y responsable.');
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()]);
        }
        $this->http->redirect('/evaluaciones.php?id=' . (int) $evaluationId);
    }

    // Este bloque permite al estudiante consultar actividades y responder dentro del plazo.
    public function attendance(): void
    {
        $user = $this->http->requirePermission('asistencia.responder');
        $studentId = $this->http->studentIdForUser((int) $user['usuario_id']);
        if ($studentId === null) {
            http_response_code(403);
            exit('La cuenta no tiene un expediente de estudiante asociado.');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->http->requirePost();
            $this->http->verifyCsrfToken();
            $activityId = filter_var($_POST['actividad_id'] ?? null, FILTER_VALIDATE_INT);
            try {
                if (!$activityId) {
                    throw new InvalidArgumentException('Selecciona una actividad válida.');
                }
                $this->model->respondAttendance(
                    (int) $activityId,
                    $studentId,
                    (string) ($_POST['respuesta'] ?? '')
                );
                $this->http->rememberNotice('Tu respuesta de asistencia se guardó.');
            } catch (InvalidArgumentException $error) {
                $this->http->rememberErrors(['acceso' => $error->getMessage()]);
            }
            $this->http->redirect('/asistencia.php');
        }

        $this->renderPage('Mis actividades y asistencia', $this->studentAttendancePage($studentId));
    }

    // Este bloque presenta al personal el resumen y las respuestas por actividad.
    public function attendanceReview(): void
    {
        $this->http->requirePermission('asistencia.consultar');
        $search = trim((string) ($_GET['q'] ?? ''));
        $projectId = filter_var($_GET['proyecto_id'] ?? 0, FILTER_VALIDATE_INT);
        $projectId = $projectId === false ? 0 : max(0, (int) $projectId);
        $dateFrom = trim((string) ($_GET['desde'] ?? ''));
        $dateTo = trim((string) ($_GET['hasta'] ?? ''));
        $perPage = 30;
        $page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        $page = $page === false ? 1 : max(1, (int) $page);
        $total = $this->model->countAttendanceActivities($search, $projectId, $dateFrom, $dateTo);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $activities = $this->model->attendanceActivities(
            $search,
            $projectId,
            $dateFrom,
            $dateTo,
            $perPage,
            ($page - 1) * $perPage
        );
        $filters = ['q' => $search, 'proyecto_id' => $projectId, 'desde' => $dateFrom, 'hasta' => $dateTo];
        $escape = fn ($value): string => $this->escape((string) $value);
        $pagination = static function (int $current, int $pages, array $params): string {
            if ($pages <= 1) {
                return '';
            }
            $html = '<nav class="pagination" aria-label="Paginación de actividades"><span>Página '
                . $current . ' de ' . $pages . '</span><div>';
            foreach ([max(1, $current - 1) => 'Anterior', min($pages, $current + 1) => 'Siguiente'] as $number => $label) {
                if (($label === 'Anterior' && $current === 1) || ($label === 'Siguiente' && $current === $pages)) {
                    continue;
                }
                $params['pagina'] = $number;
                $html .= '<a class="btn-gray" href="/asistencia_resumen.php?' . htmlspecialchars(http_build_query($params), ENT_QUOTES, 'UTF-8')
                    . '">' . $label . '</a>';
            }
            return $html . '</div></nav>';
        };
        $projectOptions = '<option value="">Todos los proyectos</option>';
        foreach ($this->model->attendanceProjectOptions() as $project) {
            $selected = (int) $project['proyecto_id'] === $projectId ? ' selected' : '';
            $projectOptions .= '<option value="' . (int) $project['proyecto_id'] . '"' . $selected . '>'
                . $escape($project['nombre']) . '</option>';
        }
        $activityId = filter_var($_GET['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        $details = '';
        if ($activityId !== false && $activityId !== null && $activityId > 0) {
            $studentSearch = trim((string) ($_GET['buscar_estudiante'] ?? ''));
            $detailPage = filter_var($_GET['detalle_pagina'] ?? 1, FILTER_VALIDATE_INT);
            $detailPage = $detailPage === false ? 1 : max(1, (int) $detailPage);
            $detailPerPage = 30;
            $detailTotal = $this->model->countAttendanceForActivity((int) $activityId, $studentSearch);
            $detailPages = max(1, (int) ceil($detailTotal / $detailPerPage));
            $detailPage = min($detailPage, $detailPages);
            $rows = '';
            foreach ($this->model->attendanceForActivity(
                (int) $activityId,
                $studentSearch,
                $detailPerPage,
                ($detailPage - 1) * $detailPerPage
            ) as $student) {
                $rows .= '<tr><td>' . $this->escape($student['nombre_completo']) . '</td><td>'
                    . $this->escape($student['carnet'] ?? '') . '</td><td>'
                    . $this->escape($student['respuesta'] ?? 'Sin respuesta') . '</td><td>'
                    . $this->escape($student['fecha_respuesta'] ?? '—') . '</td></tr>';
            }
            $detailParams = array_merge($filters, [
                'actividad_id' => (int) $activityId,
                'buscar_estudiante' => $studentSearch,
            ]);
            $detailPagination = '';
            if ($detailPages > 1) {
                $detailPagination = '<nav class="pagination" aria-label="Paginación de estudiantes"><span>Página '
                    . $detailPage . ' de ' . $detailPages . '</span><div>';
                foreach ([max(1, $detailPage - 1) => 'Anterior', min($detailPages, $detailPage + 1) => 'Siguiente'] as $number => $label) {
                    if (($label === 'Anterior' && $detailPage === 1) || ($label === 'Siguiente' && $detailPage === $detailPages)) {
                        continue;
                    }
                    $detailParams['detalle_pagina'] = $number;
                    $detailPagination .= '<a class="btn-gray" href="/asistencia_resumen.php?'
                        . htmlspecialchars(http_build_query($detailParams), ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
                }
                $detailPagination .= '</div></nav>';
            }
            $details = '<section class="form-card"><h2>Respuestas de estudiantes</h2><form method="GET" action="/asistencia_resumen.php">'
                . '<input type="hidden" name="actividad_id" value="' . (int) $activityId . '"><label for="attendance-student-search">Buscar estudiante o carné</label> '
                . '<input id="attendance-student-search" type="search" name="buscar_estudiante" value="' . $this->escape($studentSearch) . '">'
                . '<button class="btn-gray">Buscar</button></form><div class="table-container"><table><thead><tr><th>Estudiante</th><th>Carné</th><th>Respuesta</th><th>Fecha</th></tr></thead><tbody>'
                . ($rows === '' ? '<tr><td colspan="4">No hay estudiantes asignados que coincidan con la búsqueda.</td></tr>' : $rows)
                . '</tbody></table></div>' . $detailPagination . '</section>';
        }
        $rows = '';
        foreach ($activities as $activity) {
            $activityParams = array_merge($filters, ['actividad_id' => (int) $activity['actividad_id']]);
            $rows .= '<tr><td><a href="/asistencia_resumen.php?' . htmlspecialchars(http_build_query($activityParams), ENT_QUOTES, 'UTF-8') . '">'
                . $this->escape($activity['titulo']) . '</a></td><td>' . $this->escape($activity['proyecto'])
                . '</td><td>' . $this->escape($activity['fecha_inicio']) . '</td><td>'
                . (int) $activity['confirmados'] . '</td><td>' . (int) $activity['declinados'] . '</td><td>'
                . (int) $activity['asignados'] . '</td></tr>';
        }
        $from = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
        $to = min($total, $page * $perPage);
        $table = '<section class="form-card"><h2>Asistencia por actividad</h2><form class="filter-grid" method="GET" action="/asistencia_resumen.php">'
            . '<div class="form-group"><label for="attendance-search">Actividad o proyecto</label><input id="attendance-search" type="search" name="q" value="' . $this->escape($search) . '"></div>'
            . '<div class="form-group"><label for="attendance-project">Proyecto</label><select id="attendance-project" name="proyecto_id">' . $projectOptions . '</select></div>'
            . '<div class="form-group"><label for="attendance-from">Desde</label><input id="attendance-from" type="date" name="desde" value="' . $this->escape($dateFrom) . '"></div>'
            . '<div class="form-group"><label for="attendance-to">Hasta</label><input id="attendance-to" type="date" name="hasta" value="' . $this->escape($dateTo) . '"></div>'
            . '<div><button class="btn-red">Filtrar</button> <a class="btn-gray" href="/asistencia_resumen.php">Limpiar</a></div></form>'
            . '<p>Mostrando ' . $from . '–' . $to . ' de ' . $total . ' actividades.</p>' . $pagination($page, $pages, $filters)
            . '<div class="table-container"><table><thead><tr><th>Actividad</th><th>Proyecto</th><th>Fecha</th><th>Asistirán</th><th>No asistirán</th><th>Asignados</th></tr></thead><tbody>'
            . ($rows === '' ? '<tr><td colspan="6">No hay actividades registradas.</td></tr>' : $rows)
            . '</tbody></table></div>' . $pagination($page, $pages, $filters) . '</section>' . $details;
        $this->renderPage('Consulta de asistencia', $table);
    }

    // Este bloque ofrece al estudiante la gestión y consulta de sus propias solicitudes.
    public function myRequests(): void
    {
        $user = $this->http->requirePermission('solicitudes.consultar_propias');
        $studentId = $this->http->studentIdForUser((int) $user['usuario_id']);
        if ($studentId === null) {
            http_response_code(403);
            exit('La cuenta no tiene un expediente de estudiante asociado.');
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->http->requirePost();
            $this->http->verifyCsrfToken();
            $this->saveStudentRequest($studentId, (int) $user['usuario_id']);
        }
        $this->renderPage('Mis solicitudes', $this->myRequestsPage($studentId));
    }

    // Este bloque muestra la bandeja de solicitudes y procesa decisiones de revisión.
    public function reviewRequests(): void
    {
        $this->http->requirePermission('solicitudes.revisar');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->reviewRequestAction();
        }
        $this->renderPage('Revisión de solicitudes', $this->reviewRequestsPage());
    }

    // Este bloque descarga un adjunto privado tras comprobar propietario o permiso revisor.
    public function requestAttachment(): never
    {
        $user = $this->http->requireAuthentication();
        $attachmentId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$attachmentId || $attachmentId < 1) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        $attachment = $this->model->attachment((int) $attachmentId);
        $isReviewer = $this->http->userCan('solicitudes.revisar');
        if ($attachment === null || (!$isReviewer && !$this->http->userOwnsStudent(
            (int) $attachment['estudiante_id'],
            (int) $user['usuario_id']
        ))) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        $path = $this->privateAttachmentPath((string) $attachment['clave_almacenamiento']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('Archivo no encontrado.');
        }
        header('Content-Type: ' . $attachment['tipo_mime']);
        header('Content-Length: ' . (int) $attachment['tamano_bytes']);
        header('Content-Disposition: attachment; filename="' . rawurlencode($attachment['nombre_original']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }

    // Este bloque muestra el aviso vigente y solicita aceptación cuando está configurada.
    public function privacyNotice(): void
    {
        $notice = $this->model->currentPrivacyNotice();
        $content = $notice === null
            ? '<section class="form-card"><h2>Aviso de privacidad</h2><p>No hay un aviso vigente publicado. El área responsable debe configurarlo.</p></section>'
            : '<section class="form-card"><h2>Aviso de privacidad · versión ' . $this->escape($notice['version'])
                . '</h2><div style="white-space:pre-wrap">' . $this->escape($notice['contenido']) . '</div><p>Vigente desde '
                . $this->escape($notice['fecha_publicacion']) . '</p></section>';
        $user = $this->http->currentUser();
        if ($notice !== null && $user !== null && (int) $notice['requiere_aceptacion'] === 1
            && !$this->hasAcceptedNotice((int) $user['usuario_id'], (int) $notice['aviso_id'])) {
            $content .= '<section class="form-card"><h3>Se requiere tu aceptación explícita</h3><form method="POST" action="/privacidad_aceptar.php">'
                . '<input type="hidden" name="aviso_id" value="' . (int) $notice['aviso_id'] . '">'
                . '<label><input type="checkbox" name="aceptar" value="1" required> He leído y acepto el aviso de privacidad vigente.</label>'
                . '<p><button class="btn-red" type="submit">Aceptar aviso</button></p></form></section>';
        }
        $this->renderPage('Aviso de privacidad', $content);
    }

    // Este bloque registra la aceptación explícita de la versión vigente del aviso.
    public function acceptPrivacyNotice(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $user = $this->http->requireAuthentication();
        if (($_POST['aceptar'] ?? '') !== '1') {
            $this->http->rememberErrors(['acceso' => 'Debes marcar la aceptación explícita para continuar.']);
            $this->http->redirect('/aviso-privacidad.php?aceptar=1');
        }
        $noticeId = filter_var($_POST['aviso_id'] ?? null, FILTER_VALIDATE_INT);
        try {
            if (!$noticeId) {
                throw new InvalidArgumentException('No se encontró una versión vigente del aviso.');
            }
            $this->model->acceptPrivacyNotice((int) $user['usuario_id'], (int) $noticeId);
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()]);
            $this->http->redirect('/aviso-privacidad.php?aceptar=1');
        }
        $this->http->rememberNotice('La aceptación quedó registrada junto con la versión y la fecha.');
        $this->http->redirect('/dashboard.php');
    }

    // Este bloque permite a personal autorizado publicar el texto institucional aprobado.
    public function privacySettings(): void
    {
        $this->http->requirePermission('privacidad.gestionar');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->http->requirePost();
            $this->http->verifyCsrfToken();
            $user = $this->http->requirePermission('privacidad.gestionar');
            try {
                $id = $this->model->publishPrivacyNotice(
                    (string) ($_POST['version'] ?? ''),
                    (string) ($_POST['contenido'] ?? ''),
                    isset($_POST['requiere_aceptacion']),
                    (int) $user['usuario_id']
                );
                (new Auditoria($this->conexion))->registrar(
                    (int) $user['usuario_id'],
                    'aviso_privacidad',
                    $id,
                    'CREAR',
                    null,
                    ['version' => trim((string) $_POST['version']), 'requiere_aceptacion' => isset($_POST['requiere_aceptacion'])]
                );
                $this->http->rememberNotice('La nueva versión quedó publicada. Verifica el contenido aprobado antes de activar la aceptación obligatoria.');
                $this->http->redirect('/privacidad.php');
            } catch (InvalidArgumentException|PDOException $error) {
                if ($error instanceof PDOException && $error->getCode() !== '23000') {
                    throw $error;
                }
                $message = $error instanceof PDOException ? 'Esa versión ya existe.' : $error->getMessage();
                $this->http->rememberErrors(['acceso' => $message]);
                $this->http->redirect('/privacidad.php');
            }
        }
        $notice = $this->model->currentPrivacyNotice();
        $content = '<section class="form-card"><h2>Publicar aviso institucional</h2><p>Usa únicamente texto aprobado por la institución. Publicar una nueva versión conserva las aceptaciones anteriores.</p>'
            . '<form method="POST" action="/privacidad.php"><div class="form-group"><label>Versión *</label><input name="version" required maxlength="40" placeholder="Ej. 2026-01"></div>'
            . '<div class="form-group"><label>Contenido aprobado *</label><textarea name="contenido" rows="12" maxlength="50000" required>'
            . $this->escape($notice['contenido'] ?? '') . '</textarea></div>'
            . '<label><input type="checkbox" name="requiere_aceptacion" value="1" checked> Solicitar aceptación explícita a las cuentas de estudiante</label>'
            . '<p><button class="btn-red" type="submit">Publicar versión vigente</button></p></form></section>';
        if ($notice !== null) {
            $content .= '<section class="form-card"><h2>Versión vigente: ' . $this->escape($notice['version']) . '</h2><a href="/aviso-privacidad.php">Consultar aviso</a></section>';
        }
        $this->renderPage('Administrar privacidad', $content);
    }

    // Este bloque valida la solicitud y guarda sus datos y adjunto opcional.
    private function saveStudentRequest(int $studentId, int $userId): never
    {
        $subject = trim((string) ($_POST['asunto'] ?? ''));
        $details = trim((string) ($_POST['detalle'] ?? ''));
        $editId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $projectInput = $_POST['proyecto_id'] ?? '';
        $projectId = $projectInput === '' ? null : filter_var($projectInput, FILTER_VALIDATE_INT);
        if ($subject === '' || mb_strlen($subject) > 180 || $details === '' || mb_strlen($details) > 5000
            || ($projectInput !== '' && !$projectId)) {
            $this->http->rememberErrors(['acceso' => 'Completa asunto, detalle y proyecto válido.']);
            $this->http->redirect('/mis_solicitudes.php' . ($editId ? '?editar=' . $editId : '?nueva=1'));
        }
        if ($projectId !== null && !in_array(
            (int) $projectId,
            array_map(
                static fn(array $project): int => (int) $project['proyecto_id'],
                (new PortalSocial($this->conexion))->activeProjects()
            ),
            true
        )) {
            $this->http->rememberErrors(['acceso' => 'Selecciona un proyecto activo válido.']);
            $this->http->redirect('/mis_solicitudes.php' . ($editId ? '?editar=' . $editId : '?nueva=1'));
        }
        $upload = $this->validateUpload($_FILES['adjunto'] ?? null);
        if (isset($upload['error'])) {
            $this->http->rememberErrors(['acceso' => $upload['error']]);
            $this->http->redirect('/mis_solicitudes.php' . ($editId ? '?editar=' . $editId : '?nueva=1'));
        }
        if ($editId) {
            $updated = $this->model->updateReturnedRequest(
                (int) $editId,
                $studentId,
                $projectId === null ? null : (int) $projectId,
                $subject,
                $details,
                $userId
            );
            if (!$updated) {
                $this->http->rememberErrors(['acceso' => 'Solo puedes corregir solicitudes devueltas que te pertenecen.']);
                $this->http->redirect('/mis_solicitudes.php');
            }
            $requestId = (int) $editId;
        } else {
            $requestId = $this->model->createRequest(
                $studentId,
                $projectId === null ? null : (int) $projectId,
                $subject,
                $details,
                $userId
            );
        }
        if (isset($upload['tmp_name'])) {
            try {
                $this->storeAttachment($requestId, $userId, $upload);
            } catch (RuntimeException|PDOException $error) {
                error_log('No se pudo almacenar el adjunto de la solicitud ' . $requestId . ': ' . $error->getMessage());
                $this->http->rememberNotice(
                    'La solicitud #' . $requestId . ' se guardó, pero el adjunto no pudo almacenarse. Vuelve a intentarlo o contacta al área responsable.',
                    'error'
                );
                $this->http->redirect('/mis_solicitudes.php?solicitud_id=' . $requestId);
            }
        }
        $this->http->rememberNotice('La solicitud #' . $requestId . ' se envió para revisión.');
        $this->http->redirect('/mis_solicitudes.php?solicitud_id=' . $requestId);
    }

    // Este bloque aplica la decisión de revisión, notifica al estudiante y audita el cambio.
    private function reviewRequestAction(): never
    {
        $user = $this->http->requirePermission('solicitudes.revisar');
        $requestId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $decision = (string) ($_POST['decision'] ?? '');
        $comment = trim((string) ($_POST['observaciones'] ?? ''));
        try {
            if (!$requestId) {
                throw new InvalidArgumentException('Selecciona una solicitud válida.');
            }
            $updated = $this->model->reviewRequest((int) $requestId, (int) $user['usuario_id'], $decision, $comment);
            if (!$updated) {
                throw new InvalidArgumentException('La solicitud ya fue atendida o no existe.');
            }
            $this->notifyRequestStudent((int) $requestId);
            (new Auditoria($this->conexion))->registrar(
                (int) $user['usuario_id'],
                'solicitud_estudiante',
                (int) $requestId,
                'ACTUALIZAR',
                null,
                ['estado' => $decision, 'respuesta' => $comment]
            );
            $this->http->rememberNotice('La decisión y el historial de la solicitud se guardaron.');
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()]);
        }
        $this->http->redirect('/solicitudes_revisar.php' . ($requestId ? '?solicitud_id=' . $requestId : ''));
    }

    // Este bloque construye el formulario de criterios, la evaluación y su listado.
    private function evaluationPage(): string
    {
        $html = '';
        $search = trim((string) ($_GET['q'] ?? ''));
        $status = (string) ($_GET['estado'] ?? '');
        $projectId = filter_var($_GET['proyecto_id'] ?? 0, FILTER_VALIDATE_INT);
        $projectId = $projectId === false ? 0 : max(0, (int) $projectId);
        $dateFrom = trim((string) ($_GET['desde'] ?? ''));
        $dateTo = trim((string) ($_GET['hasta'] ?? ''));
        if ($this->http->userCan('evaluaciones.criterios')) {
            $rows = '';
            foreach ($this->model->allCriteria() as $criterion) {
                $toggle = (int) $criterion['activo'] === 1 ? 'Desactivar' : 'Activar';
                $rows .= '<tr><td>' . $this->escape($criterion['nombre']) . '</td><td>' . $this->escape($criterion['descripcion'] ?? '')
                    . '</td><td>' . (int) $criterion['escala_min'] . '–' . (int) $criterion['escala_max'] . '</td><td>'
                    . ((int) $criterion['obligatorio'] ? 'Sí' : 'No') . '</td><td>' . ((int) $criterion['activo'] ? 'Activo' : 'Inactivo') . '</td><td>'
                    . '<form method="POST" action="/evaluacion_criterio_estado.php"><input type="hidden" name="criterio_id" value="'
                    . (int) $criterion['criterio_id'] . '"><input type="hidden" name="activo" value="'
                    . ((int) $criterion['activo'] ? '0' : '1') . '"><button class="btn-gray">' . $toggle . '</button></form></td></tr>';
            }
            $html .= '<details class="form-card workflow-disclosure"><summary>Configurar criterios de evaluación</summary><section><h2>Criterios activos y escalas</h2><p>Las evaluaciones nuevas guardan una copia de los criterios vigentes al iniciarlas.</p>'
                . '<form method="POST" action="/evaluaciones.php"><div class="form-grid"><div class="form-group"><label>Nombre *</label><input name="nombre" required maxlength="160"></div>'
                . '<div class="form-group"><label>Descripción</label><input name="descripcion" maxlength="1000"></div><div class="form-group"><label>Escala mínima</label><input name="escala_min" type="number" min="0" max="9" value="1" required></div>'
                . '<div class="form-group"><label>Escala máxima</label><input name="escala_max" type="number" min="1" max="10" value="5" required></div><label><input type="checkbox" name="obligatorio" checked> Obligatorio</label></div>'
                . '<p><button class="btn-red">Agregar criterio</button></p></form><table><thead><tr><th>Criterio</th><th>Descripción</th><th>Escala</th><th>Obligatorio</th><th>Estado</th><th></th></tr></thead><tbody>'
                . ($rows ?: '<tr><td colspan="6">Configura los criterios definidos por la institución para habilitar evaluaciones.</td></tr>') . '</tbody></table></section></details>';
        }

        $candidateOptions = '<option value="">Selecciona estudiante y proyecto</option>';
        foreach ($this->model->evaluationCandidates($search) as $candidate) {
            $candidateOptions .= '<option value="' . (int) $candidate['estudiante_id'] . ':' . (int) $candidate['proyecto_id'] . '">'
                . $this->escape($candidate['nombre_completo'] . ' · ' . ($candidate['carnet'] ?? '') . ' · ' . $candidate['proyecto']) . '</option>';
        }
        $html .= '<details class="form-card workflow-disclosure"><summary>Iniciar una evaluación</summary><section><h2>Nueva evaluación</h2><form method="POST" action="/evaluacion_crear.php">'
            . '<div class="form-group"><label>Estudiante y proyecto *</label><select name="participante" required>' . $candidateOptions . '</select></div>'
            . '<div class="form-group"><label>Periodo *</label><input name="periodo" required maxlength="80" placeholder="Ej. 2026-2"></div>'
            . '<p><button class="btn-red">Iniciar evaluación</button></p></form></section></details>';

        $evaluationId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if ($evaluationId) {
            $evaluation = $this->model->evaluation((int) $evaluationId);
            if ($evaluation === null) {
                http_response_code(404);
                $html .= '<section class="form-card">La evaluación no existe.</section>';
            } else {
                $criteria = '';
                foreach ($evaluation['criterios'] as $criterion) {
                    $resultId = (int) $criterion['resultado_id'];
                    $criteria .= '<div class="form-group"><label>' . $this->escape($criterion['criterio_nombre'])
                        . ((int) $criterion['obligatorio'] ? ' *' : '') . ' (' . (int) $criterion['escala_min'] . '–' . (int) $criterion['escala_max'] . ')</label>'
                        . '<input type="number" name="puntuaciones[' . $resultId . ']" min="' . (int) $criterion['escala_min']
                        . '" max="' . (int) $criterion['escala_max'] . '" value="' . $this->escape($criterion['puntuacion'] ?? '') . '"'
                        . ((int) $criterion['obligatorio'] ? ' required' : '') . '></div>';
                    $criteria .= '<div class="form-group"><label>Comentario · ' . $this->escape($criterion['criterio_nombre'])
                        . '</label><textarea name="puntuaciones[comentario][' . $resultId . ']" maxlength="1000">'
                        . $this->escape($criterion['comentario'] ?? '') . '</textarea></div>';
                }
                $locked = $evaluation['estado_evaluacion'] !== 'Borrador';
                $backQuery = http_build_query([
                    'q' => $search,
                    'estado' => $status,
                    'proyecto_id' => $projectId,
                    'desde' => $dateFrom,
                    'hasta' => $dateTo,
                ]);
                $html .= '<section class="form-card"><p><a class="btn-gray" href="/evaluaciones.php?' . $this->escape($backQuery) . '">Volver a evaluaciones</a></p><h2>' . $this->escape($evaluation['estudiante']) . ' · ' . $this->escape($evaluation['proyecto'] ?? '')
                    . '</h2><p>Periodo: ' . $this->escape($evaluation['periodo']) . ' · Estado: ' . $this->escape($evaluation['estado_evaluacion'])
                    . ' · Evaluador: ' . $this->escape($evaluation['evaluador'] ?? '—') . ' · Fecha: ' . $this->escape($evaluation['fecha_evaluacion']) . '</p>';
                if (!$locked) {
                    $html .= '<form method="POST" action="/evaluacion_guardar.php"><input type="hidden" name="evaluacion_id" value="' . (int) $evaluationId . '">'
                        . $criteria . '<div class="form-group"><label>Comentarios</label><textarea name="comentarios" maxlength="10000">'
                        . $this->escape($evaluation['comentarios']) . '</textarea></div><button class="btn-gray">Guardar borrador</button></form>'
                        . '<form method="POST" action="/evaluacion_cerrar.php" onsubmit="return confirm(\'¿Cerrar la evaluación? Ya no podrá modificarse.\')">'
                        . '<input type="hidden" name="evaluacion_id" value="' . (int) $evaluationId . '"><button class="btn-red">Cerrar evaluación</button></form>';
                } else {
                    $html .= '<table><thead><tr><th>Criterio</th><th>Puntuación</th><th>Comentario</th></tr></thead><tbody>';
                    foreach ($evaluation['criterios'] as $criterion) {
                        $html .= '<tr><td>' . $this->escape($criterion['criterio_nombre']) . '</td><td>'
                            . $this->escape($criterion['puntuacion'] ?? '—') . '</td><td>' . $this->escape($criterion['comentario'] ?? '') . '</td></tr>';
                    }
                    $html .= '</tbody></table><p>' . $this->escape($evaluation['comentarios']) . '</p>';
                }
                $html .= '</section>';
            }
        }
        $rows = '';
        $perPage = 30;
        $page = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        $page = $page === false ? 1 : max(1, (int) $page);
        $total = $this->model->countEvaluations($search, $status, $projectId, $dateFrom, $dateTo);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $evaluations = $this->model->evaluations(
            $search,
            $status,
            $projectId,
            $dateFrom,
            $dateTo,
            $perPage,
            ($page - 1) * $perPage
        );
        foreach ($evaluations as $evaluation) {
            $evaluationQuery = http_build_query([
                'id' => (int) $evaluation['evaluacion_id'],
                'q' => $search,
                'estado' => $status,
                'proyecto_id' => $projectId,
                'desde' => $dateFrom,
                'hasta' => $dateTo,
                'pagina' => $page,
            ]);
            $rows .= '<tr><td><a href="/evaluaciones.php?' . $this->escape($evaluationQuery) . '">'
                . $this->escape($evaluation['estudiante']) . '</a></td><td>' . $this->escape($evaluation['proyecto'] ?? '')
                . '</td><td>' . $this->escape($evaluation['periodo']) . '</td><td>' . $this->escape($evaluation['estado_evaluacion'])
                . '</td><td>' . $this->escape($evaluation['evaluador'] ?? '') . '</td><td>' . $this->escape($evaluation['fecha_evaluacion']) . '</td></tr>';
        }
        $filters = ['q' => $search, 'estado' => $status, 'proyecto_id' => $projectId, 'desde' => $dateFrom, 'hasta' => $dateTo];
        $pagination = '';
        if ($pages > 1) {
            $pagination = '<nav class="pagination" aria-label="Paginación de evaluaciones"><span>Página ' . $page . ' de ' . $pages . '</span><div>';
            foreach ([max(1, $page - 1) => 'Anterior', min($pages, $page + 1) => 'Siguiente'] as $number => $label) {
                if (($label === 'Anterior' && $page === 1) || ($label === 'Siguiente' && $page === $pages)) {
                    continue;
                }
                $filters['pagina'] = $number;
                $pagination .= '<a class="btn-gray" href="/evaluaciones.php?' . $this->escape(http_build_query($filters)) . '">' . $label . '</a>';
            }
            $pagination .= '</div></nav>';
        }
        $projectOptions = '<option value="">Todos los proyectos</option>';
        foreach ($this->model->evaluationProjectOptions() as $project) {
            $selected = (int) $project['proyecto_id'] === $projectId ? ' selected' : '';
            $projectOptions .= '<option value="' . (int) $project['proyecto_id'] . '"' . $selected . '>'
                . $this->escape($project['nombre']) . '</option>';
        }
        $statusOptions = '<option value="">Todos los estados</option>';
        foreach (['Borrador' => 'En proceso', 'Cerrada' => 'Cerradas'] as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $statusOptions .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
        }
        $from = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
        $to = min($total, $page * $perPage);
        return $html . '<section class="form-card"><h2>Evaluaciones</h2><form class="filter-grid" method="GET" action="/evaluaciones.php">'
            . '<div class="form-group"><label for="evaluation-search">Estudiante, carné, proyecto o periodo</label><input id="evaluation-search" name="q" type="search" value="'
            . $this->escape($search) . '"></div><div class="form-group"><label for="evaluation-project">Proyecto</label><select id="evaluation-project" name="proyecto_id">'
            . $projectOptions . '</select></div><div class="form-group"><label for="evaluation-status">Estado</label><select id="evaluation-status" name="estado">'
            . $statusOptions . '</select></div><div class="form-group"><label for="evaluation-from">Desde</label><input id="evaluation-from" type="date" name="desde" value="'
            . $this->escape($dateFrom) . '"></div><div class="form-group"><label for="evaluation-to">Hasta</label><input id="evaluation-to" type="date" name="hasta" value="'
            . $this->escape($dateTo) . '"></div><div><button class="btn-red">Filtrar</button> <a class="btn-gray" href="/evaluaciones.php">Limpiar</a></div></form>'
            . '<p>Mostrando ' . $from . '–' . $to . ' de ' . $total . ' evaluaciones.</p>' . $pagination
            . '<div class="table-container"><table><thead><tr><th>Estudiante</th><th>Proyecto</th><th>Periodo</th><th>Estado</th><th>Evaluador</th><th>Fecha</th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="6">No hay evaluaciones que coincidan con los filtros.</td></tr>') . '</tbody></table></div>' . $pagination . '</section>';
    }

    // Este bloque dibuja actividades asignadas y habilita respuestas solo mientras el plazo esté abierto.
    private function studentAttendancePage(int $studentId): string
    {
        $html = '<section class="form-card"><h2>Mis actividades asignadas</h2><p>Podrás cambiar tu respuesta hasta la fecha límite indicada; si no se configuró otra, el plazo cierra al iniciar la actividad.</p>';
        $activities = $this->model->studentActivities($studentId);
        if ($activities === []) {
            return $html . '<p>No tienes actividades activas asignadas.</p></section>';
        }
        foreach ($activities as $activity) {
            $deadline = $activity['fecha_limite_confirmacion'] ?: $activity['fecha_inicio'];
            $open = strtotime((string) $deadline) >= time();
            $html .= '<article id="actividad-' . (int) $activity['actividad_id'] . '" style="border-top:1px solid #ddd;padding:1rem 0"><h3>' . $this->escape($activity['titulo']) . '</h3><p>'
                . $this->escape($activity['proyecto']) . ' · ' . $this->escape($activity['fecha_inicio']) . '</p><p>Límite: '
                . $this->escape($deadline) . ' · Respuesta actual: ' . $this->escape($activity['respuesta'] ?? 'Sin responder') . '</p>';
            if ($open) {
                foreach (['Asistiré' => 'Confirmar asistencia', 'No asistiré' => 'Declinar asistencia'] as $value => $label) {
                    $html .= '<form method="POST" action="/asistencia.php" style="display:inline-block;margin-right:.5rem"><input type="hidden" name="actividad_id" value="'
                        . (int) $activity['actividad_id'] . '"><input type="hidden" name="respuesta" value="' . $value
                        . '"><button class="btn-' . ($value === 'Asistiré' ? 'red' : 'gray') . '">' . $label . '</button></form>';
                }
            } else {
                $html .= '<p>El plazo para confirmar asistencia terminó.</p>';
            }
            $html .= '</article>';
        }
        return $html . '</section>';
    }

    // Este bloque reúne el detalle, historial, adjuntos y listado propio de solicitudes.
    private function myRequestsPage(int $studentId): string
    {
        $editId = filter_var($_GET['editar'] ?? null, FILTER_VALIDATE_INT);
        $selectedId = filter_var($_GET['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $selected = $editId ? $this->model->requestForStudent((int) $editId, $studentId)
            : ($selectedId ? $this->model->requestForStudent((int) $selectedId, $studentId) : null);
        $html = '';
        if (($editId || $selectedId) && $selected === null) {
            http_response_code(404);
            $html .= '<section class="form-card">No se encontró la solicitud.</section>';
        }
        if ($selected !== null) {
            $html .= '<section class="form-card"><h2>Solicitud #' . (int) $selected['solicitud_id'] . ' · '
                . $this->escape($selected['estado']) . '</h2><p>' . $this->escape($selected['asunto']) . '</p><p>'
                . nl2br($this->escape($selected['detalle'])) . '</p>';
            if (!empty($selected['respuesta'])) {
                $html .= '<p><strong>Observaciones:</strong> ' . nl2br($this->escape($selected['respuesta'])) . '</p>';
            }
            $html .= '<h3>Historial</h3><ol>';
            foreach ($selected['historial'] as $event) {
                $html .= '<li>' . $this->escape($event['fecha_cambio']) . ' · ' . $this->escape($event['accion'])
                    . ' · ' . $this->escape($event['estado_anterior'] ?? '') . ' → ' . $this->escape($event['estado_nuevo'] ?? '')
                    . ' · ' . $this->escape($event['comentario'] ?? '') . '</li>';
            }
            $html .= '</ol><h3>Adjuntos</h3>';
            foreach ($selected['adjuntos'] as $attachment) {
                $html .= '<p><a href="/solicitud_adjunto.php?id=' . (int) $attachment['adjunto_id'] . '">'
                    . $this->escape($attachment['nombre_original']) . '</a> · ' . (int) $attachment['tamano_bytes'] . ' bytes</p>';
            }
            if ($selected['estado'] === 'Devuelta') {
                $html .= $this->requestForm($selected, (int) $selected['solicitud_id']);
            }
            $html .= '</section>';
        }
        if (!$editId) {
            $html .= $this->requestForm();
        }
        $rows = '';
        foreach ((new PortalSocial($this->conexion))->requestsForStudent(
            $studentId,
            trim((string) ($_GET['q'] ?? ''))
        ) as $request) {
            $rows .= '<tr><td>#' . (int) $request['solicitud_id'] . '</td><td>' . $this->escape($request['asunto'])
                . '</td><td>' . $this->escape($request['proyecto'] ?? 'General') . '</td><td>'
                . $this->escape($request['fecha_solicitud']) . '</td><td>' . $this->escape($request['estado'])
                . '</td><td><a href="/mis_solicitudes.php?solicitud_id=' . (int) $request['solicitud_id'] . '">Ver</a>'
                . ($request['estado'] === 'Devuelta' ? ' · <a href="/mis_solicitudes.php?editar=' . (int) $request['solicitud_id'] . '">Corregir y reenviar</a>' : '')
                . '</td></tr>';
        }
        return $html . '<section class="form-card"><h2>Mis solicitudes</h2><form method="GET"><input name="q" type="search" value="'
            . $this->escape($_GET['q'] ?? '') . '" placeholder="Buscar"><button class="btn-gray">Buscar</button></form><table><thead><tr><th>N.º</th><th>Asunto</th><th>Proyecto</th><th>Fecha</th><th>Estado</th><th></th></tr></thead><tbody>'
            . ($rows ?: '<tr><td colspan="6">No hay solicitudes registradas.</td></tr>') . '</tbody></table></section>';
    }

    // Este bloque genera el formulario para crear una solicitud o reenviar una devuelta.
    private function requestForm(?array $request = null, int $requestId = 0): string
    {
        $title = $requestId > 0 ? 'Corregir solicitud devuelta' : 'Nueva solicitud de participación';
        $projects = '<option value="">Solicitud general</option>';
        foreach ((new PortalSocial($this->conexion))->activeProjects() as $project) {
            $selected = (int) ($request['proyecto_id'] ?? 0) === (int) $project['proyecto_id'] ? ' selected' : '';
            $projects .= '<option value="' . (int) $project['proyecto_id'] . '"' . $selected . '>'
                . $this->escape($project['nombre']) . '</option>';
        }
        return '<section class="form-card"><h2>' . $title . '</h2><form method="POST" enctype="multipart/form-data" action="/mis_solicitudes.php">'
            . ($requestId > 0 ? '<input type="hidden" name="solicitud_id" value="' . $requestId . '">' : '')
            . '<div class="form-group"><label>Asunto *</label><input name="asunto" required maxlength="180" value="'
            . $this->escape($request['asunto'] ?? '') . '"></div><div class="form-group"><label>Proyecto</label><select name="proyecto_id">'
            . $projects . '</select></div><div class="form-group"><label>Detalle *</label><textarea name="detalle" required maxlength="5000">'
            . $this->escape($request['detalle'] ?? '') . '</textarea></div><div class="form-group"><label>Adjuntar respaldo (PDF, JPG o PNG, hasta 8 MB)</label><input type="file" name="adjunto" accept=".pdf,.jpg,.jpeg,.png"></div>'
            . '<button class="btn-red">Enviar para revisión</button></form></section>';
    }

    // Este bloque prepara filtros, detalle y acciones para la bandeja de revisión.
    private function reviewRequestsPage(): string
    {
        $requestId = filter_var($_GET['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $html = '<section class="form-card"><h2>Bandeja de solicitudes</h2><form method="GET" class="form-grid">'
            . '<label>Estado<select name="estado"><option value="">Todos</option>';
        foreach (['Pendiente', 'Devuelta', 'Aprobada', 'Rechazada'] as $state) {
            $html .= '<option' . (($_GET['estado'] ?? '') === $state ? ' selected' : '') . '>' . $state . '</option>';
        }
        $html .= '</select></label><label>Estudiante o carné<input name="estudiante" value="' . $this->escape($_GET['estudiante'] ?? '') . '"></label>'
            . '<label>Desde<input type="date" name="desde" value="' . $this->escape($_GET['desde'] ?? '') . '"></label>'
            . '<label>Hasta<input type="date" name="hasta" value="' . $this->escape($_GET['hasta'] ?? '') . '"></label><button class="btn-gray">Filtrar</button></form>'
            . '<table><thead><tr><th>Solicitud</th><th>Estudiante</th><th>Proyecto</th><th>Fecha</th><th>Estado</th><th></th></tr></thead><tbody>';
        $rows = '';
        foreach ($this->model->requestsForReview($_GET) as $request) {
            $rows .= '<tr><td>#' . (int) $request['solicitud_id'] . ' · ' . $this->escape($request['asunto']) . '</td><td>'
                . $this->escape($request['estudiante']) . ' · ' . $this->escape($request['carnet'] ?? '') . '</td><td>'
                . $this->escape($request['proyecto'] ?? 'General') . '</td><td>' . $this->escape($request['fecha_solicitud'])
                . '</td><td>' . $this->escape($request['estado']) . '</td><td><a href="/solicitudes_revisar.php?solicitud_id='
                . (int) $request['solicitud_id'] . '">Abrir</a></td></tr>';
        }
        $html .= $rows ?: '<tr><td colspan="6">No hay solicitudes que coincidan con los filtros.</td></tr>';
        $html .= '</tbody></table></section>';
        if ($requestId) {
            $request = $this->model->requestDetails((int) $requestId);
            if ($request === null) {
                http_response_code(404);
                return $html . '<section class="form-card">Solicitud no encontrada.</section>';
            }
            $this->model->addRequestHistory((int) $requestId, (int) $this->http->currentUser()['usuario_id'], $request['estado'], $request['estado'], 'Apertura', 'Solicitud consultada para revisión.');
            $html .= '<section class="form-card"><h2>Solicitud #' . (int) $requestId . ' · ' . $this->escape($request['asunto']) . '</h2><p>'
                . $this->escape($request['estudiante']) . ' · ' . $this->escape($request['carnet'] ?? '') . ' · '
                . $this->escape($request['proyecto'] ?? 'General') . '</p><p>' . nl2br($this->escape($request['detalle'])) . '</p><h3>Adjuntos autorizados</h3>';
            foreach ($request['adjuntos'] as $attachment) {
                $html .= '<p><a href="/solicitud_adjunto.php?id=' . (int) $attachment['adjunto_id'] . '">'
                    . $this->escape($attachment['nombre_original']) . '</a> · ' . (int) $attachment['tamano_bytes'] . ' bytes</p>';
            }
            $html .= '<h3>Historial</h3><ol>';
            foreach ($request['historial'] as $event) {
                $html .= '<li>' . $this->escape($event['fecha_cambio']) . ' · ' . $this->escape($event['accion'])
                    . ' · ' . $this->escape($event['usuario'] ?? 'Sistema') . ' · ' . $this->escape($event['comentario'] ?? '') . '</li>';
            }
            $html .= '</ol>';
            if ($request['estado'] === 'Pendiente') {
                $html .= '<form method="POST" action="/solicitudes_revisar.php"><input type="hidden" name="solicitud_id" value="' . (int) $requestId . '">'
                    . '<div class="form-group"><label>Observaciones (obligatorias al rechazar o devolver)</label><textarea name="observaciones" maxlength="5000"></textarea></div>'
                    . '<button class="btn-red" name="decision" value="Aprobada">Aprobar</button> '
                    . '<button class="btn-gray" name="decision" value="Devuelta">Devolver para corrección</button> '
                    . '<button class="btn-gray" name="decision" value="Rechazada">Rechazar</button></form>';
            }
            $html .= '</section>';
        }
        return $html;
    }

    // Este bloque restringe adjuntos a formatos permitidos y tamaño máximo.
    private function validateUpload(mixed $file): array
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [];
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            return ['error' => 'No se pudo recibir el archivo adjunto.'];
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > 8 * 1024 * 1024) {
            return ['error' => 'El archivo debe ser menor o igual a 8 MB.'];
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
        $allowed = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
        if (!is_string($mime) || !isset($allowed[$mime])) {
            return ['error' => 'Solo se permiten archivos PDF, JPG o PNG.'];
        }
        return [
            'tmp_name' => (string) $file['tmp_name'],
            'name' => (string) ($file['name'] ?? 'adjunto'),
            'mime' => $mime,
            'size' => $size,
            'extension' => $allowed[$mime],
        ];
    }

    // Este bloque almacena el archivo fuera del sitio público y guarda sus metadatos.
    private function storeAttachment(int $requestId, int $userId, array $upload): void
    {
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'entorno-desarrollo-private'
            . DIRECTORY_SEPARATOR . 'solicitudes';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el almacenamiento privado de adjuntos.');
        }
        $key = bin2hex(random_bytes(32));
        $path = $directory . DIRECTORY_SEPARATOR . $key . '.' . $upload['extension'];
        if (!move_uploaded_file($upload['tmp_name'], $path)) {
            throw new RuntimeException('No se pudo guardar el archivo adjunto.');
        }
        try {
            $this->model->addRequestAttachment($requestId, $upload['name'], $key, $upload['mime'], $upload['size'], $userId);
            $this->model->addRequestHistory($requestId, $userId, null, null, 'Adjunto', 'Se agregó un archivo de respaldo.');
        } catch (Throwable $error) {
            if (is_file($path)) {
                unlink($path);
            }
            throw $error;
        }
    }

    // Este bloque resuelve una clave segura a una ruta de almacenamiento privada.
    private function privateAttachmentPath(string $key): string
    {
        if (preg_match('/^[a-f0-9]{64}$/', $key) !== 1) {
            throw new InvalidArgumentException('El identificador del archivo no es válido.');
        }
        $directory = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'entorno-desarrollo-private'
            . DIRECTORY_SEPARATOR . 'solicitudes';
        foreach (['pdf', 'jpg', 'png'] as $extension) {
            $path = $directory . DIRECTORY_SEPARATOR . $key . '.' . $extension;
            if (is_file($path)) {
                return $path;
            }
        }
        return $directory . DIRECTORY_SEPARATOR . $key . '.missing';
    }

    // Este bloque consulta si el usuario ya aceptó una versión concreta del aviso.
    private function hasAcceptedNotice(int $userId, int $noticeId): bool
    {
        $query = $this->conexion->prepare(
            'SELECT 1 FROM aceptaciones_privacidad WHERE usuario_id = :usuario AND aviso_id = :aviso'
        );
        $query->execute(['usuario' => $userId, 'aviso' => $noticeId]);
        return $query->fetchColumn() !== false;
    }

    // Este bloque crea una notificación interna con el resultado de la solicitud.
    private function notifyRequestStudent(int $requestId): void
    {
        $query = $this->conexion->prepare(
            'SELECT e.usuario_id, s.asunto, s.estado, s.respuesta
             FROM solicitudes_estudiante s JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
             WHERE s.solicitud_id = :id AND e.usuario_id IS NOT NULL'
        );
        $query->execute(['id' => $requestId]);
        $request = $query->fetch();
        if ($request === false) {
            return;
        }
        $insert = $this->conexion->prepare(
            'INSERT INTO notificaciones (usuario_id, asunto, mensaje, estado_envio, fecha_envio)
             VALUES (:usuario, :asunto, :mensaje, "Enviado", CURRENT_TIMESTAMP)'
        );
        $insert->execute([
            'usuario' => (int) $request['usuario_id'],
            'asunto' => 'Actualización de solicitud: ' . $request['asunto'],
            'mensaje' => 'Estado: ' . $request['estado'] . '. ' . (string) ($request['respuesta'] ?? '')
                . ' /mis_solicitudes.php?solicitud_id=' . $requestId,
        ]);
    }

    // Este bloque inserta el contenido dinámico en la plantilla común de flujos.
    private function renderPage(string $title, string $content): void
    {
        $this->http->render('workflow.php', function (string $html) use ($title, $content): string {
            $safeTitle = $this->escape($title);
            $html = str_replace('<title>Workflow</title>', '<title>' . $safeTitle . ' - ITCA-FEPADE</title>', $html);
            return str_replace('<!-- workflow-content -->', '<h1>' . $safeTitle . '</h1>' . $content, $html);
        });
    }

    // Este bloque escapa valores antes de incluirlos en HTML.
    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
