<?php
declare(strict_types=1);

final class PortalSocial
{
    // Este bloque recibe la conexión usada por métricas, solicitudes y notificaciones.
    public function __construct(private PDO $conexion)
    {
    }

    // Este bloque calcula las métricas principales usadas por el panel social.
    public function dashboardSummary(): array
    {
        return [
            'estudiantes' => (int) $this->conexion->query(
                "SELECT COUNT(*) FROM estudiantes WHERE estado_registro = 'Activo'"
            )->fetchColumn(),
            'proyectos' => (int) $this->conexion->query(
                "SELECT COUNT(*) FROM proyectos WHERE estado = 'Activo'"
            )->fetchColumn(),
            'actividades' => (int) $this->conexion->query(
                'SELECT COUNT(*) FROM actividades_proyecto
                 WHERE fecha_inicio >= DATE_FORMAT(CURRENT_DATE, "%Y-%m-01")
                   AND fecha_inicio < DATE_ADD(DATE_FORMAT(CURRENT_DATE, "%Y-%m-01"), INTERVAL 1 MONTH)'
            )->fetchColumn(),
            'respuestas' => (int) $this->conexion->query(
                'SELECT COUNT(*) FROM respuestas_cuestionario'
            )->fetchColumn(),
            'solicitudes_pendientes' => (int) $this->conexion->query(
                "SELECT COUNT(*) FROM solicitudes_estudiante WHERE estado = 'Pendiente'"
            )->fetchColumn(),
            'horas_aprobadas' => (float) $this->conexion->query(
                "SELECT COALESCE(SUM(horas), 0) FROM registros_horas WHERE estado = 'Aprobado'"
            )->fetchColumn(),
            'actualizado' => date('Y-m-d H:i:s'),
        ];
    }

    // Este bloque cuenta proyectos agrupados por estado.
    public function projectStatusCounts(): array
    {
        $counts = ['Activo' => 0, 'En Pausa' => 0, 'Finalizado' => 0];
        foreach ($this->conexion->query(
            "SELECT estado, COUNT(*) AS cantidad FROM proyectos
             WHERE estado IN ('Activo', 'En Pausa', 'Finalizado')
             GROUP BY estado"
        )->fetchAll() as $row) {
            $counts[$row['estado']] = (int) $row['cantidad'];
        }

        return $counts;
    }

    // Este bloque agrega actividades por mes para alimentar las gráficas.
    public function monthlyActivityCounts(int $months = 6): array
    {
        $months = max(1, min($months, 12));
        $query = $this->conexion->prepare(
            'SELECT DATE_FORMAT(fecha_inicio, "%Y-%m") AS periodo, COUNT(*) AS cantidad
             FROM actividades_proyecto
             WHERE fecha_inicio >= DATE_SUB(DATE_FORMAT(CURRENT_DATE, "%Y-%m-01"), INTERVAL :meses MONTH)
               AND fecha_inicio < DATE_ADD(DATE_FORMAT(CURRENT_DATE, "%Y-%m-01"), INTERVAL 1 MONTH)
             GROUP BY periodo ORDER BY periodo'
        );
        $query->bindValue('meses', $months - 1, PDO::PARAM_INT);
        $query->execute();
        $counts = [];
        foreach ($query->fetchAll() as $row) {
            $counts[$row['periodo']] = (int) $row['cantidad'];
        }

        $result = [];
        $cursor = new DateTimeImmutable('first day of this month');
        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $month = $cursor->modify('-' . $offset . ' months');
            $key = $month->format('Y-m');
            $result[] = ['periodo' => $key, 'cantidad' => $counts[$key] ?? 0];
        }

        return $result;
    }

    // Este bloque consulta actividades futuras para el panel.
    public function upcomingActivities(int $limit = 5): array
    {
        $query = $this->conexion->prepare(
            'SELECT a.actividad_id, a.titulo, a.fecha_inicio, a.fecha_fin, a.lugar,
                    p.nombre AS proyecto
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             WHERE a.fecha_inicio >= CURRENT_TIMESTAMP
             ORDER BY a.fecha_inicio
             LIMIT :limite'
        );
        $query->bindValue('limite', max(1, min($limit, 20)), PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }

    // Este bloque devuelve notificaciones internas recientes.
    public function recentNotifications(int $limit = 5): array
    {
        $query = $this->conexion->prepare(
            'SELECT n.asunto, n.mensaje, n.fecha_creacion, u.nombre_completo
             FROM notificaciones n
             JOIN usuarios u ON u.usuario_id = n.usuario_id
             ORDER BY n.fecha_creacion DESC
             LIMIT :limite'
        );
        $query->bindValue('limite', max(1, min($limit, 20)), PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }

    // Este bloque lista solicitudes que esperan revisión.
    public function pendingRequests(int $limit = 20): array
    {
        $query = $this->conexion->prepare(
            "SELECT s.solicitud_id, s.asunto, s.detalle, s.fecha_solicitud,
                    e.estudiante_id, e.nombre_completo AS estudiante,
                    p.nombre AS proyecto
             FROM solicitudes_estudiante s
             JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
             LEFT JOIN proyectos p ON p.proyecto_id = s.proyecto_id
             WHERE s.estado = 'Pendiente'
             ORDER BY s.fecha_solicitud
             LIMIT :limite"
        );
        $query->bindValue('limite', max(1, min($limit, 100)), PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }

    // Este bloque registra una decisión sobre una solicitud pendiente.
    public function reviewRequest(int $requestId, int $reviewerId, string $decision, string $response): bool
    {
        if (!in_array($decision, ['Aprobada', 'Rechazada', 'Devuelta'], true)) {
            throw new InvalidArgumentException('La decisión de la solicitud no es válida.');
        }
        if ($decision !== 'Aprobada' && trim($response) === '') {
            throw new InvalidArgumentException('Indica las observaciones o el motivo de la decisión.');
        }

        $query = $this->conexion->prepare(
            "UPDATE solicitudes_estudiante
             SET estado = :estado, respuesta = :respuesta, revisada_por = :revisada_por,
                 fecha_revision = CURRENT_TIMESTAMP
             WHERE solicitud_id = :solicitud_id AND estado = 'Pendiente'"
        );
        $query->execute([
            'estado' => $decision,
            'respuesta' => trim($response),
            'revisada_por' => $reviewerId,
            'solicitud_id' => $requestId,
        ]);

        return $query->rowCount() === 1;
    }

    // Este bloque genera una notificación interna después de resolver una solicitud.
    public function notifyRequestStudent(int $requestId): void
    {
        $query = $this->conexion->prepare(
            'SELECT e.usuario_id, s.asunto, s.estado, s.respuesta
             FROM solicitudes_estudiante s
             JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
             WHERE s.solicitud_id = :solicitud_id AND e.usuario_id IS NOT NULL'
        );
        $query->execute(['solicitud_id' => $requestId]);
        $request = $query->fetch();
        if ($request === false) {
            return;
        }
        $insert = $this->conexion->prepare(
            'INSERT INTO notificaciones (usuario_id, asunto, mensaje, estado_envio, fecha_envio)
             VALUES (:usuario_id, :asunto, :mensaje, "Enviado", CURRENT_TIMESTAMP)'
        );
        $insert->execute([
            'usuario_id' => (int) $request['usuario_id'],
            'asunto' => 'Actualización de solicitud: ' . $request['asunto'],
            'mensaje' => 'Estado: ' . $request['estado'] . '. ' . (string) ($request['respuesta'] ?? ''),
        ]);
    }

    // Este bloque lista las solicitudes del estudiante con búsqueda opcional.
    public function requestsForStudent(int $studentId, string $search = ''): array
    {
        $sql = 'SELECT s.solicitud_id, s.asunto, s.estado, s.fecha_solicitud, s.respuesta,
                       p.nombre AS proyecto
                FROM solicitudes_estudiante s
                LEFT JOIN proyectos p ON p.proyecto_id = s.proyecto_id
                WHERE s.estudiante_id = :estudiante_id';
        $parameters = ['estudiante_id' => $studentId];
        if ($search !== '') {
            $sql .= ' AND (s.asunto LIKE :busqueda_asunto OR p.nombre LIKE :busqueda_proyecto)';
            $term = '%' . $search . '%';
            $parameters['busqueda_asunto'] = $term;
            $parameters['busqueda_proyecto'] = $term;
        }
        $sql .= ' ORDER BY s.fecha_solicitud DESC';
        $query = $this->conexion->prepare($sql);
        $query->execute($parameters);

        return $query->fetchAll();
    }

    // Este bloque devuelve detalle de una solicitud solo a su estudiante propietario.
    public function requestDetailsForStudent(int $requestId, int $studentId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT s.solicitud_id, s.asunto, s.detalle, s.estado, s.respuesta,
                    s.fecha_solicitud, s.fecha_revision, p.nombre AS proyecto
             FROM solicitudes_estudiante s
             LEFT JOIN proyectos p ON p.proyecto_id = s.proyecto_id
             WHERE s.solicitud_id = :solicitud_id AND s.estudiante_id = :estudiante_id'
        );
        $query->execute(['solicitud_id' => $requestId, 'estudiante_id' => $studentId]);
        $request = $query->fetch();

        return $request === false ? null : $request;
    }

    // Este bloque cuenta las solicitudes del estudiante por estado.
    public function requestCountsForStudent(int $studentId): array
    {
        $counts = ['total' => 0, 'Aprobada' => 0, 'Pendiente' => 0, 'Rechazada' => 0];
        $query = $this->conexion->prepare(
            'SELECT estado, COUNT(*) AS cantidad
             FROM solicitudes_estudiante WHERE estudiante_id = :estudiante_id GROUP BY estado'
        );
        $query->execute(['estudiante_id' => $studentId]);
        foreach ($query->fetchAll() as $row) {
            $counts[$row['estado']] = (int) $row['cantidad'];
            $counts['total'] += (int) $row['cantidad'];
        }

        return $counts;
    }

    // Este bloque crea una solicitud de participación con estado pendiente.
    public function createRequest(int $studentId, ?int $projectId, string $subject, string $details): int
    {
        $query = $this->conexion->prepare(
            'INSERT INTO solicitudes_estudiante (estudiante_id, proyecto_id, asunto, detalle)
             VALUES (:estudiante_id, :proyecto_id, :asunto, :detalle)'
        );
        $query->execute([
            'estudiante_id' => $studentId,
            'proyecto_id' => $projectId,
            'asunto' => $subject,
            'detalle' => $details,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    // Este bloque devuelve proyectos activos para los formularios del portal.
    public function activeProjects(): array
    {
        return $this->conexion->query(
            "SELECT proyecto_id, nombre FROM proyectos WHERE estado = 'Activo' ORDER BY nombre"
        )->fetchAll();
    }

    // Este bloque lista plantillas activas de notificación interna.
    public function templates(): array
    {
        return $this->conexion->query(
            'SELECT plantilla_id, nombre, tipo, asunto, mensaje
             FROM plantillas_notificacion WHERE activa = 1
             ORDER BY fecha_actualizacion DESC, fecha_creacion DESC'
        )->fetchAll();
    }

    // Este bloque persiste una plantilla nueva con su autor.
    public function saveTemplate(string $name, string $type, string $subject, string $message, int $userId): int
    {
        $query = $this->conexion->prepare(
            'INSERT INTO plantillas_notificacion (nombre, tipo, asunto, mensaje, creada_por)
             VALUES (:nombre, :tipo, :asunto, :mensaje, :creada_por)'
        );
        $query->execute([
            'nombre' => $name,
            'tipo' => $type,
            'asunto' => $subject,
            'mensaje' => $message,
            'creada_por' => $userId,
        ]);

        return (int) $this->conexion->lastInsertId();
    }

    // Este bloque genera notificaciones internas para estudiantes asignados a una actividad.
    public function notifyAssignedStudents(int $activityId): int
    {
        $activityQuery = $this->conexion->prepare(
            'SELECT a.titulo, a.fecha_inicio, a.lugar, p.nombre AS proyecto
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             WHERE a.actividad_id = :actividad_id'
        );
        $activityQuery->execute(['actividad_id' => $activityId]);
        $activity = $activityQuery->fetch();
        if ($activity === false) {
            throw new InvalidArgumentException('La actividad no existe para notificar.');
        }

        $query = $this->conexion->prepare(
            "SELECT plantilla_id, asunto, mensaje
             FROM plantillas_notificacion
             WHERE activa = 1 AND tipo = 'Actividad'
             ORDER BY fecha_actualizacion DESC, fecha_creacion DESC
             LIMIT 1"
        );
        $query->execute();
        $template = $query->fetch();
        if ($template === false) {
            $this->logActivityNotification($activityId, null, 'Sin plantilla', 0, 'No hay plantilla activa de tipo Actividad.');
            return 0;
        }

        $values = [
            '{{nombre_proyecto}}' => $activity['proyecto'],
            '{{nombre_actividad}}' => $activity['titulo'],
            '{{fecha_actividad}}' => date('d/m/Y', strtotime($activity['fecha_inicio'])),
            '{{hora_actividad}}' => date('H:i', strtotime($activity['fecha_inicio'])),
            '{{lugar_actividad}}' => $activity['lugar'],
        ];
        $subject = strtr($template['asunto'], $values);
        $calendarUrl = '/calendario_actividades.php?' . http_build_query([
            'month' => date('Y-m', strtotime($activity['fecha_inicio'])),
            'day' => date('Y-m-d', strtotime($activity['fecha_inicio'])),
            'view' => 'day',
        ]);
        $message = strtr($template['mensaje'], $values) . "\n\nDetalle y calendario: " . $calendarUrl;

        $recipients = $this->conexion->prepare(
            "SELECT DISTINCT e.usuario_id
             FROM actividades_proyecto a
             JOIN proyecto_estudiantes pe ON pe.proyecto_id = a.proyecto_id
             JOIN estudiantes e ON e.estudiante_id = pe.estudiante_id
             JOIN usuarios u ON u.usuario_id = e.usuario_id AND u.estado_activo = 1
             WHERE a.actividad_id = :actividad_id
               AND pe.estado_participacion = 'Activa'
               AND e.usuario_id IS NOT NULL"
        );
        $recipients->execute(['actividad_id' => $activityId]);
        $recipientIds = $recipients->fetchAll(PDO::FETCH_COLUMN);
        if ($recipientIds === []) {
            $this->logActivityNotification($activityId, (int) $template['plantilla_id'], 'Sin destinatarios', 0, null);
            return 0;
        }
        $insert = $this->conexion->prepare(
            'INSERT INTO notificaciones
                (usuario_id, plantilla_id, asunto, mensaje, estado_envio, fecha_envio)
             VALUES (:usuario_id, :plantilla_id, :asunto, :mensaje, "Enviado", CURRENT_TIMESTAMP)'
        );
        $count = 0;
        try {
            foreach ($recipientIds as $recipientId) {
                $insert->execute([
                    'usuario_id' => (int) $recipientId,
                    'plantilla_id' => (int) $template['plantilla_id'],
                    'asunto' => $subject,
                    'mensaje' => $message,
                ]);
                $count += $insert->rowCount();
            }
        } catch (Throwable $error) {
            $this->logActivityNotification(
                $activityId,
                (int) $template['plantilla_id'],
                'Error',
                $count,
                mb_substr($error->getMessage(), 0, 500)
            );
            throw new RuntimeException('La actividad se guardó, pero falló el envío de notificaciones internas.', 0, $error);
        }

        $this->logActivityNotification($activityId, (int) $template['plantilla_id'], 'Enviado', $count, null);
        return $count;
    }

    // Este bloque conserva el resultado del intento de notificación de una actividad.
    private function logActivityNotification(int $activityId, ?int $templateId, string $status, int $recipients, ?string $error): void
    {
        $query = $this->conexion->prepare(
            'INSERT INTO actividad_notificaciones_envio
                (actividad_id, plantilla_id, canal, estado, destinatarios, detalle_error)
             VALUES (:actividad, :plantilla, "Interno", :estado, :destinatarios, :error)'
        );
        $query->execute([
            'actividad' => $activityId,
            'plantilla' => $templateId,
            'estado' => $status,
            'destinatarios' => $recipients,
            'error' => $error,
        ]);
    }

    // Este bloque consulta actividades dentro del intervalo y filtros seleccionados.
    public function calendarActivities(array $filters): array
    {
        $sql = 'SELECT a.actividad_id, a.titulo, a.descripcion, a.modalidad,
                       a.fecha_inicio, a.fecha_fin, a.lugar,
                       p.proyecto_id, p.nombre AS proyecto, p.estado
                FROM actividades_proyecto a
                JOIN proyectos p ON p.proyecto_id = a.proyecto_id
                WHERE a.fecha_inicio >= :inicio AND a.fecha_inicio < :fin';
        $parameters = ['inicio' => $filters['inicio'], 'fin' => $filters['fin']];
        if ((int) ($filters['proyecto_id'] ?? 0) > 0) {
            $sql .= ' AND p.proyecto_id = :proyecto_id';
            $parameters['proyecto_id'] = (int) $filters['proyecto_id'];
        }
        if (in_array($filters['estado'] ?? '', ['Activo', 'En Pausa', 'Finalizado', 'Inactivo'], true)) {
            $sql .= ' AND p.estado = :estado';
            $parameters['estado'] = $filters['estado'];
        }
        $sql .= ' ORDER BY a.fecha_inicio';
        $query = $this->conexion->prepare($sql);
        $query->execute($parameters);

        return $query->fetchAll();
    }

    // Este bloque prepara opciones de cuestionario, proyecto y actividad con respuestas.
    public function responseFilters(): array
    {
        return [
            'cuestionarios' => $this->conexion->query(
                'SELECT DISTINCT titulo_cuestionario FROM respuestas_cuestionario ORDER BY titulo_cuestionario'
            )->fetchAll(PDO::FETCH_COLUMN),
            'proyectos' => $this->conexion->query(
                'SELECT DISTINCT p.proyecto_id, p.nombre
                 FROM respuestas_cuestionario r JOIN proyectos p ON p.proyecto_id = r.proyecto_id
                 ORDER BY p.nombre'
            )->fetchAll(),
            'actividades' => $this->conexion->query(
                'SELECT DISTINCT a.actividad_id, a.titulo
                 FROM respuestas_cuestionario r JOIN actividades_proyecto a ON a.actividad_id = r.actividad_id
                 ORDER BY a.titulo'
            )->fetchAll(),
        ];
    }

    // Este bloque devuelve respuestas que coinciden con los filtros de consulta.
    public function responses(array $filters): array
    {
        $sql = 'SELECT r.respuesta_id, r.titulo_cuestionario, r.calificacion_general,
                       r.fecha_respuesta, e.estudiante_id, e.nombre_completo AS estudiante,
                       e.carnet, p.nombre AS proyecto, a.titulo AS actividad
                FROM respuestas_cuestionario r
                JOIN estudiantes e ON e.estudiante_id = r.estudiante_id
                LEFT JOIN proyectos p ON p.proyecto_id = r.proyecto_id
                LEFT JOIN actividades_proyecto a ON a.actividad_id = r.actividad_id
                WHERE 1 = 1';
        $parameters = [];
        foreach (['cuestionario' => 'r.titulo_cuestionario', 'proyecto_id' => 'r.proyecto_id', 'actividad_id' => 'r.actividad_id'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $sql .= ' AND ' . $column . ' = :' . $key;
                $parameters[$key] = $filters[$key];
            }
        }
        $sql .= ' ORDER BY r.fecha_respuesta DESC';
        $query = $this->conexion->prepare($sql);
        $query->execute($parameters);

        return $query->fetchAll();
    }

    // Este bloque combina respuestas y detalles para calcular métricas del cuestionario.
    public function responseSummary(array $filters): array
    {
        $responses = $this->responses($filters);
        $responseIds = array_map(static fn(array $response): int => (int) $response['respuesta_id'], $responses);
        $answers = $this->answersForResponses($responseIds);
        $questionCount = count(array_unique(array_column($answers, 'pregunta')));

        return [
            'total' => count($responses),
            'preguntas' => $questionCount,
            'fecha' => $responses[0]['fecha_respuesta'] ?? null,
            'respuestas' => $responses,
            'answers' => $answers,
        ];
    }

    // Este bloque cuenta estudiantes elegibles para comparar la tasa de respuesta.
    public function eligibleResponseCount(array $filters): int
    {
        if (($filters['actividad_id'] ?? '') !== '') {
            $query = $this->conexion->prepare(
                "SELECT COUNT(DISTINCT pe.estudiante_id)
                 FROM actividades_proyecto a
                 JOIN proyecto_estudiantes pe ON pe.proyecto_id = a.proyecto_id
                 WHERE a.actividad_id = :actividad_id AND pe.estado_participacion = 'Activa'"
            );
            $query->execute(['actividad_id' => (int) $filters['actividad_id']]);

            return (int) $query->fetchColumn();
        }
        if (($filters['proyecto_id'] ?? '') !== '') {
            $query = $this->conexion->prepare(
                "SELECT COUNT(DISTINCT estudiante_id)
                 FROM proyecto_estudiantes
                 WHERE proyecto_id = :proyecto_id AND estado_participacion = 'Activa'"
            );
            $query->execute(['proyecto_id' => (int) $filters['proyecto_id']]);

            return (int) $query->fetchColumn();
        }

        return (int) $this->conexion->query(
            "SELECT COUNT(*) FROM estudiantes WHERE estado_registro = 'Activo'"
        )->fetchColumn();
    }

    // Este bloque obtiene respuestas de preguntas para un conjunto de cuestionarios.
    public function answersForResponses(array $responseIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $responseIds), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = [];
        $parameters = [];
        foreach ($ids as $index => $id) {
            $key = 'respuesta_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $id;
        }
        $query = $this->conexion->prepare(
            'SELECT respuesta_id, pregunta, respuesta
             FROM respuestas_cuestionario_detalle
             WHERE respuesta_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY respuesta_id, detalle_id'
        );
        $query->execute($parameters);

        return $query->fetchAll();
    }

    // Este bloque recupera respuestas detalladas de un cuestionario individual.
    public function responseDetails(int $responseId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT r.respuesta_id, r.titulo_cuestionario, r.calificacion_general,
                    r.fecha_respuesta, e.nombre_completo AS estudiante,
                    p.nombre AS proyecto, a.titulo AS actividad
             FROM respuestas_cuestionario r
             JOIN estudiantes e ON e.estudiante_id = r.estudiante_id
             LEFT JOIN proyectos p ON p.proyecto_id = r.proyecto_id
             LEFT JOIN actividades_proyecto a ON a.actividad_id = r.actividad_id
             WHERE r.respuesta_id = :respuesta_id'
        );
        $query->execute(['respuesta_id' => $responseId]);
        $response = $query->fetch();
        if ($response === false) {
            return null;
        }
        $response['answers'] = $this->answersForResponses([$responseId]);

        return $response;
    }

    // Este bloque selecciona estudiantes elegibles para emitir documentos.
    public function documentCandidates(int $projectId, int $activityId): array
    {
        $query = $this->conexion->prepare(
            "SELECT e.estudiante_id, e.nombre_completo, e.carnet, p.proyecto_id,
                    p.nombre AS proyecto, a.actividad_id, a.titulo AS actividad,
                    SUM(rh.horas) AS horas_aprobadas
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             JOIN proyecto_estudiantes pe ON pe.proyecto_id = p.proyecto_id
             JOIN estudiantes e ON e.estudiante_id = pe.estudiante_id
             JOIN registros_horas rh ON rh.estudiante_id = e.estudiante_id
                 AND rh.actividad_id = a.actividad_id AND rh.estado = 'Aprobado'
             WHERE p.proyecto_id = :proyecto_id
               AND a.actividad_id = :actividad_id
               AND pe.estado_participacion = 'Activa'
               AND e.estado_registro = 'Activo'
             GROUP BY e.estudiante_id, e.nombre_completo, e.carnet, p.proyecto_id,
                      p.nombre, a.actividad_id, a.titulo
             ORDER BY e.nombre_completo"
        );
        $query->execute(['proyecto_id' => $projectId, 'actividad_id' => $activityId]);

        return $query->fetchAll();
    }

    // Este bloque valida elegibilidad y guarda copias históricas de documentos emitidos.
    public function generateDocuments(
        string $type,
        int $projectId,
        int $activityId,
        array $studentIds,
        string $issueDate,
        int $generatedBy
    ): array {
        if (!in_array($type, ['diploma', 'carta'], true)) {
            throw new InvalidArgumentException('El tipo de documento no es válido.');
        }
        $validDate = DateTimeImmutable::createFromFormat('!Y-m-d', $issueDate);
        if ($validDate === false || $validDate->format('Y-m-d') !== $issueDate) {
            throw new InvalidArgumentException('La fecha de emisión no es válida.');
        }
        $selected = array_values(array_unique(array_filter(
            array_map('intval', $studentIds),
            static fn(int $id): bool => $id > 0
        )));
        if ($selected === []) {
            throw new InvalidArgumentException('Selecciona al menos un estudiante.');
        }

        $candidates = $this->documentCandidates($projectId, $activityId);
        $allowed = [];
        foreach ($candidates as $candidate) {
            $allowed[(int) $candidate['estudiante_id']] = $candidate;
        }

        $insert = $this->conexion->prepare(
            'INSERT IGNORE INTO documentos_emitidos
                (tipo, estudiante_id, proyecto_id, actividad_id, fecha_emision, contenido, generado_por)
             VALUES (:tipo, :estudiante_id, :proyecto_id, :actividad_id, :fecha_emision, :contenido, :generado_por)'
        );
        $documentIds = [];
        foreach ($selected as $studentId) {
            if (!isset($allowed[$studentId])) {
                continue;
            }
            $candidate = $allowed[$studentId];
            $snapshot = [
                'tipo' => $type,
                'estudiante' => $candidate['nombre_completo'],
                'carnet' => $candidate['carnet'],
                'proyecto' => $candidate['proyecto'],
                'actividad' => $candidate['actividad'],
                'fecha_emision' => $issueDate,
                'horas_aprobadas' => (float) $candidate['horas_aprobadas'],
            ];
            $insert->execute([
                'tipo' => $type,
                'estudiante_id' => $studentId,
                'proyecto_id' => $projectId,
                'actividad_id' => $activityId,
                'fecha_emision' => $issueDate,
                'contenido' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'generado_por' => $generatedBy,
            ]);
            if ($insert->rowCount() === 1) {
                $documentIds[] = (int) $this->conexion->lastInsertId();
            }
        }

        if ($documentIds === []) {
            throw new InvalidArgumentException('No hay estudiantes elegibles nuevos para emitir ese documento.');
        }

        return $documentIds;
    }

    // Este bloque carga documentos emitidos y decodifica sus datos conservados.
    public function issuedDocuments(array $documentIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $documentIds), static fn(int $id): bool => $id > 0)));
        if ($ids === []) {
            return [];
        }
        $placeholders = [];
        $parameters = [];
        foreach ($ids as $index => $id) {
            $key = 'documento_' . $index;
            $placeholders[] = ':' . $key;
            $parameters[$key] = $id;
        }
        $query = $this->conexion->prepare(
            'SELECT documento_id, tipo, estudiante_id, generado_por, contenido
             FROM documentos_emitidos WHERE documento_id IN (' . implode(', ', $placeholders) . ')
             ORDER BY documento_id'
        );
        $query->execute($parameters);
        $documents = [];
        foreach ($query->fetchAll() as $row) {
            $row['contenido'] = json_decode($row['contenido'], true, 512, JSON_THROW_ON_ERROR);
            $documents[] = $row;
        }

        return $documents;
    }

    // Este bloque obtiene el propietario de un documento para autorizar su consulta.
    public function documentAccess(int $documentId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT documento_id, estudiante_id, generado_por
             FROM documentos_emitidos WHERE documento_id = :documento_id'
        );
        $query->execute(['documento_id' => $documentId]);
        $document = $query->fetch();

        return $document === false ? null : $document;
    }
}
