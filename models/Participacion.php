<?php
declare(strict_types=1);

final class Participacion
{
    // Este bloque recibe la conexión usada en los flujos de participación.
    public function __construct(private PDO $conexion)
    {
    }

    // Este bloque devuelve los criterios vigentes que se copian al iniciar evaluaciones.
    public function activeCriteria(): array
    {
        return $this->conexion->query(
            'SELECT criterio_id, nombre, descripcion, escala_min, escala_max, obligatorio
             FROM evaluacion_criterios WHERE activo = 1 ORDER BY criterio_id'
        )->fetchAll();
    }

    // Este bloque lista criterios activos e inactivos para su administración.
    public function allCriteria(): array
    {
        return $this->conexion->query(
            'SELECT criterio_id, nombre, descripcion, escala_min, escala_max, obligatorio, activo
             FROM evaluacion_criterios ORDER BY activo DESC, nombre'
        )->fetchAll();
    }

    // Este bloque valida y guarda un criterio con su escala y obligatoriedad.
    public function saveCriterion(
        string $name,
        string $description,
        int $minimum,
        int $maximum,
        bool $required,
        int $userId
    ): int {
        $name = trim($name);
        if ($name === '' || mb_strlen($name) > 160 || $minimum < 0 || $maximum > 10 || $maximum <= $minimum) {
            throw new InvalidArgumentException('Define un criterio y una escala válida entre 0 y 10.');
        }
        if (mb_strlen($description) > 1000) {
            throw new InvalidArgumentException('La descripción del criterio excede 1,000 caracteres.');
        }
        $query = $this->conexion->prepare(
            'INSERT INTO evaluacion_criterios
                (nombre, descripcion, escala_min, escala_max, obligatorio, creado_por)
             VALUES (:nombre, :descripcion, :minimo, :maximo, :obligatorio, :usuario)'
        );
        $query->execute([
            'nombre' => $name,
            'descripcion' => trim($description) === '' ? null : trim($description),
            'minimo' => $minimum,
            'maximo' => $maximum,
            'obligatorio' => $required ? 1 : 0,
            'usuario' => $userId,
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    // Este bloque activa o desactiva un criterio sin alterar evaluaciones existentes.
    public function setCriterionActive(int $criterionId, bool $active): bool
    {
        $query = $this->conexion->prepare(
            'UPDATE evaluacion_criterios SET activo = :activo WHERE criterio_id = :criterio'
        );
        $query->execute(['activo' => $active ? 1 : 0, 'criterio' => $criterionId]);
        return $query->rowCount() === 1;
    }

    // Este bloque obtiene estudiantes con asignaciones activas disponibles para evaluar.
    public function evaluationCandidates(string $search = ''): array
    {
        $sql = "SELECT DISTINCT e.estudiante_id, e.nombre_completo, e.carnet,
                       p.proyecto_id, p.nombre AS proyecto
                FROM estudiantes e
                JOIN proyecto_estudiantes pe ON pe.estudiante_id = e.estudiante_id
                JOIN proyectos p ON p.proyecto_id = pe.proyecto_id
                WHERE e.estado_registro = 'Activo' AND pe.estado_participacion = 'Activa'";
        $params = [];
        if ($search !== '') {
            $sql .= ' AND (e.nombre_completo LIKE :nombre OR e.carnet LIKE :carnet)';
            $params['nombre'] = '%' . $search . '%';
            $params['carnet'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY e.nombre_completo, p.nombre';
        $query = $this->conexion->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    // Este bloque crea una evaluación y guarda una copia de los criterios vigentes.
    public function createEvaluation(int $studentId, int $projectId, string $period, int $evaluatorId): int
    {
        $period = trim($period);
        if ($period === '' || mb_strlen($period) > 80) {
            throw new InvalidArgumentException('Indica el periodo de evaluación (máximo 80 caracteres).');
        }
        $criteria = $this->activeCriteria();
        if ($criteria === []) {
            throw new InvalidArgumentException('Antes de evaluar, configura al menos un criterio activo.');
        }
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }
        try {
            $participant = $this->conexion->prepare(
                "SELECT 1 FROM proyecto_estudiantes
                 WHERE estudiante_id = :estudiante AND proyecto_id = :proyecto
                   AND estado_participacion = 'Activa' LIMIT 1 FOR UPDATE"
            );
            $participant->execute(['estudiante' => $studentId, 'proyecto' => $projectId]);
            if ($participant->fetchColumn() === false) {
                throw new InvalidArgumentException('El estudiante no tiene una asignación activa en ese proyecto.');
            }

            $existing = $this->conexion->prepare(
                'SELECT evaluacion_id FROM evaluaciones_estudiante
                 WHERE estudiante_id = :estudiante AND proyecto_id = :proyecto AND periodo = :periodo'
            );
            $existing->execute(['estudiante' => $studentId, 'proyecto' => $projectId, 'periodo' => $period]);
            $evaluationId = $existing->fetchColumn();
            if ($evaluationId !== false) {
                if ($ownsTransaction) {
                    $this->conexion->commit();
                }
                return (int) $evaluationId;
            }

            $insert = $this->conexion->prepare(
                "INSERT INTO evaluaciones_estudiante
                    (estudiante_id, proyecto_id, evaluador_id, comentarios, periodo, estado_evaluacion)
                 VALUES (:estudiante, :proyecto, :evaluador, '', :periodo, 'Borrador')"
            );
            $insert->execute([
                'estudiante' => $studentId,
                'proyecto' => $projectId,
                'evaluador' => $evaluatorId,
                'periodo' => $period,
            ]);
            $evaluationId = (int) $this->conexion->lastInsertId();
            $snapshot = $this->conexion->prepare(
                'INSERT INTO evaluacion_criterios_resultados
                    (evaluacion_id, criterio_id, criterio_nombre, escala_min, escala_max, obligatorio)
                 VALUES (:evaluacion, :criterio, :nombre, :minimo, :maximo, :obligatorio)'
            );
            foreach ($criteria as $criterion) {
                $snapshot->execute([
                    'evaluacion' => $evaluationId,
                    'criterio' => (int) $criterion['criterio_id'],
                    'nombre' => $criterion['nombre'],
                    'minimo' => (int) $criterion['escala_min'],
                    'maximo' => (int) $criterion['escala_max'],
                    'obligatorio' => (int) $criterion['obligatorio'],
                ]);
            }
            if ($ownsTransaction) {
                $this->conexion->commit();
            }
            return $evaluationId;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    // Este bloque valida y persiste puntuaciones y comentarios de un borrador abierto.
    public function saveEvaluation(int $evaluationId, array $scores, string $comments, int $evaluatorId): void
    {
        if (mb_strlen($comments) > 10000) {
            throw new InvalidArgumentException('Los comentarios generales exceden 10,000 caracteres.');
        }
        $criterionComments = $scores['comentario'] ?? [];
        if (!is_array($criterionComments)) {
            throw new InvalidArgumentException('Los comentarios por criterio no tienen un formato válido.');
        }
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }
        try {
            $evaluationQuery = $this->conexion->prepare(
                "SELECT estado_evaluacion FROM evaluaciones_estudiante WHERE evaluacion_id = :id FOR UPDATE"
            );
            $evaluationQuery->execute(['id' => $evaluationId]);
            if ($evaluationQuery->fetchColumn() !== 'Borrador') {
                throw new InvalidArgumentException('La evaluación no existe o ya fue cerrada.');
            }
            $criteriaQuery = $this->conexion->prepare(
                'SELECT resultado_id, criterio_nombre, escala_min, escala_max
                 FROM evaluacion_criterios_resultados WHERE evaluacion_id = :id'
            );
            $criteriaQuery->execute(['id' => $evaluationId]);
            $criteria = $criteriaQuery->fetchAll();
            if ($criteria === []) {
                throw new InvalidArgumentException('La evaluación no tiene criterios configurados.');
            }
            $validatedScores = [];
            foreach ($criteria as $criterion) {
                $key = (string) $criterion['resultado_id'];
                $raw = $scores[$key] ?? '';
                if (!is_string($raw) && !is_int($raw)) {
                    throw new InvalidArgumentException('La puntuación de "' . $criterion['criterio_nombre'] . '" no es válida.');
                }
                $score = $raw === '' ? null : filter_var($raw, FILTER_VALIDATE_INT);
                if ($score !== null && ($score === false || $score < (int) $criterion['escala_min'] || $score > (int) $criterion['escala_max'])) {
                    throw new InvalidArgumentException('La puntuación de "' . $criterion['criterio_nombre'] . '" no está dentro de la escala.');
                }
                $criterionComment = $criterionComments[$key] ?? '';
                if (!is_string($criterionComment) || mb_strlen($criterionComment) > 1000) {
                    throw new InvalidArgumentException('El comentario de "' . $criterion['criterio_nombre'] . '" excede 1,000 caracteres o no es válido.');
                }
                $validatedScores[] = [
                    'score' => $score,
                    'comment' => trim($criterionComment) === '' ? null : trim($criterionComment),
                    'id' => (int) $criterion['resultado_id'],
                ];
            }
            $update = $this->conexion->prepare(
                'UPDATE evaluacion_criterios_resultados
                 SET puntuacion = :puntuacion, comentario = :comentario WHERE resultado_id = :id'
            );
            foreach ($validatedScores as $validatedScore) {
                $update->execute([
                    'puntuacion' => $validatedScore['score'],
                    'comentario' => $validatedScore['comment'],
                    'id' => $validatedScore['id'],
                ]);
            }
            $updateEvaluation = $this->conexion->prepare(
                'UPDATE evaluaciones_estudiante
                 SET comentarios = :comentarios, evaluador_id = :evaluador WHERE evaluacion_id = :id'
            );
            $updateEvaluation->execute([
                'comentarios' => trim($comments),
                'evaluador' => $evaluatorId,
                'id' => $evaluationId,
            ]);
            if ($ownsTransaction) {
                $this->conexion->commit();
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    // Este bloque calcula la calificación y cierra la evaluación si cumple los criterios.
    public function closeEvaluation(int $evaluationId, int $evaluatorId): void
    {
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }
        try {
            $lock = $this->conexion->prepare(
                'SELECT estado_evaluacion FROM evaluaciones_estudiante WHERE evaluacion_id = :id FOR UPDATE'
            );
            $lock->execute(['id' => $evaluationId]);
            if ($lock->fetchColumn() !== 'Borrador') {
                throw new InvalidArgumentException('La evaluación no existe o ya fue cerrada.');
            }

            $average = $this->conexion->prepare(
                'SELECT AVG(puntuacion) FROM evaluacion_criterios_resultados
                 WHERE evaluacion_id = :id AND puntuacion IS NOT NULL'
            );
            $average->execute(['id' => $evaluationId]);
            $score = $average->fetchColumn();
            if ($score === false || $score === null) {
                throw new InvalidArgumentException('Registra al menos una puntuación antes de cerrar la evaluación.');
            }
            $query = $this->conexion->prepare(
                'SELECT criterio_nombre
                 FROM evaluacion_criterios_resultados
                 WHERE evaluacion_id = :id AND obligatorio = 1 AND puntuacion IS NULL'
            );
            $query->execute(['id' => $evaluationId]);
            $missing = $query->fetchAll(PDO::FETCH_COLUMN);
            if ($missing !== []) {
                throw new InvalidArgumentException('Completa los criterios obligatorios: ' . implode(', ', $missing) . '.');
            }
            $close = $this->conexion->prepare(
                "UPDATE evaluaciones_estudiante
                 SET calificacion = :calificacion, estado_evaluacion = 'Cerrada',
                     cerrada_por = :usuario, fecha_cierre = CURRENT_TIMESTAMP
                 WHERE evaluacion_id = :id AND estado_evaluacion = 'Borrador'"
            );
            $close->execute(['calificacion' => round((float) $score, 2), 'usuario' => $evaluatorId, 'id' => $evaluationId]);
            if ($close->rowCount() !== 1) {
                throw new InvalidArgumentException('La evaluación no existe o ya fue cerrada.');
            }
            if ($ownsTransaction) {
                $this->conexion->commit();
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    // Este bloque lista evaluaciones con datos de estudiante, proyecto y evaluador.
    public function evaluations(
        string $search = '',
        string $status = '',
        int $projectId = 0,
        string $dateFrom = '',
        string $dateTo = '',
        int $limit = 50,
        int $offset = 0
    ): array
    {
        $sql = 'SELECT ev.evaluacion_id, ev.estudiante_id, ev.proyecto_id, ev.periodo,
                       ev.calificacion, ev.comentarios, ev.estado_evaluacion, ev.fecha_evaluacion,
                       ev.fecha_cierre, e.nombre_completo AS estudiante, e.carnet,
                       p.nombre AS proyecto, u.nombre_completo AS evaluador
                FROM evaluaciones_estudiante ev
                JOIN estudiantes e ON e.estudiante_id = ev.estudiante_id
                LEFT JOIN proyectos p ON p.proyecto_id = ev.proyecto_id
                LEFT JOIN usuarios u ON u.usuario_id = ev.evaluador_id';
        [$where, $params] = $this->evaluationFilters($search, $status, $projectId, $dateFrom, $dateTo);
        $sql .= $where . ' ORDER BY ev.fecha_evaluacion DESC, ev.evaluacion_id DESC LIMIT :limite OFFSET :desplazamiento';
        $query = $this->conexion->prepare($sql);
        foreach ($params as $name => $value) {
            $query->bindValue(':' . $name, $value, PDO::PARAM_STR);
        }
        $query->bindValue(':limite', max(1, min(100, $limit)), PDO::PARAM_INT);
        $query->bindValue(':desplazamiento', max(0, $offset), PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countEvaluations(
        string $search = '',
        string $status = '',
        int $projectId = 0,
        string $dateFrom = '',
        string $dateTo = ''
    ): int {
        [$where, $params] = $this->evaluationFilters($search, $status, $projectId, $dateFrom, $dateTo);
        $query = $this->conexion->prepare(
            'SELECT COUNT(*) FROM evaluaciones_estudiante ev
             JOIN estudiantes e ON e.estudiante_id = ev.estudiante_id
             LEFT JOIN proyectos p ON p.proyecto_id = ev.proyecto_id' . $where
        );
        $query->execute($params);
        return (int) $query->fetchColumn();
    }

    public function evaluationProjectOptions(): array
    {
        return $this->conexion->query(
            'SELECT DISTINCT p.proyecto_id, p.nombre
             FROM evaluaciones_estudiante ev
             JOIN proyectos p ON p.proyecto_id = ev.proyecto_id
             ORDER BY p.nombre'
        )->fetchAll();
    }

    private function evaluationFilters(
        string $search,
        string $status,
        int $projectId,
        string $dateFrom,
        string $dateTo
    ): array {
        $conditions = [];
        $params = [];
        $search = trim($search);
        if ($search !== '') {
            $conditions[] = '(e.nombre_completo LIKE :nombre OR e.carnet LIKE :carnet OR p.nombre LIKE :proyecto OR ev.periodo LIKE :periodo)';
            $pattern = '%' . $search . '%';
            $params += ['nombre' => $pattern, 'carnet' => $pattern, 'proyecto' => $pattern, 'periodo' => $pattern];
        }
        if (in_array($status, ['Borrador', 'Cerrada'], true)) {
            $conditions[] = 'ev.estado_evaluacion = :estado';
            $params['estado'] = $status;
        }
        if ($projectId > 0) {
            $conditions[] = 'ev.proyecto_id = :proyecto_id';
            $params['proyecto_id'] = $projectId;
        }
        if ($this->isValidDateFilter($dateFrom)) {
            $conditions[] = 'DATE(ev.fecha_evaluacion) >= :fecha_desde';
            $params['fecha_desde'] = $dateFrom;
        }
        if ($this->isValidDateFilter($dateTo)) {
            $conditions[] = 'DATE(ev.fecha_evaluacion) <= :fecha_hasta';
            $params['fecha_hasta'] = $dateTo;
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    private function isValidDateFilter(string $date): bool
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return false;
        }
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year);
    }

    // Este bloque obtiene una evaluación junto con sus resultados por criterio.
    public function evaluation(int $evaluationId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT ev.*, e.nombre_completo AS estudiante, e.carnet, p.nombre AS proyecto
             FROM evaluaciones_estudiante ev
             JOIN estudiantes e ON e.estudiante_id = ev.estudiante_id
             LEFT JOIN proyectos p ON p.proyecto_id = ev.proyecto_id
             WHERE ev.evaluacion_id = :id'
        );
        $query->execute(['id' => $evaluationId]);
        $evaluation = $query->fetch();
        if ($evaluation === false) {
            return null;
        }
        $details = $this->conexion->prepare(
            'SELECT resultado_id, criterio_nombre, escala_min, escala_max, obligatorio, puntuacion, comentario
             FROM evaluacion_criterios_resultados WHERE evaluacion_id = :id ORDER BY resultado_id'
        );
        $details->execute(['id' => $evaluationId]);
        $evaluation['criterios'] = $details->fetchAll();
        return $evaluation;
    }

    // Este bloque lista actividades activas asignadas al estudiante y su respuesta actual.
    public function studentActivities(int $studentId): array
    {
        $query = $this->conexion->prepare(
            "SELECT a.actividad_id, a.titulo, a.fecha_inicio, a.fecha_fin, a.fecha_limite_confirmacion,
                    p.nombre AS proyecto, r.respuesta, r.fecha_respuesta,
                    ac.fecha_cancelacion, ac.motivo AS motivo_cancelacion
             FROM proyecto_estudiantes pe
             JOIN proyectos p ON p.proyecto_id = pe.proyecto_id AND p.estado = 'Activo'
             JOIN actividades_proyecto a ON a.proyecto_id = p.proyecto_id
             LEFT JOIN actividad_cancelaciones ac ON ac.actividad_id = a.actividad_id
             LEFT JOIN respuestas_asistencia r
               ON r.actividad_id = a.actividad_id AND r.estudiante_id = pe.estudiante_id
             WHERE pe.estudiante_id = :estudiante AND pe.estado_participacion = 'Activa'
             ORDER BY a.fecha_inicio"
        );
        $query->execute(['estudiante' => $studentId]);
        return $query->fetchAll();
    }

    // Este bloque guarda o actualiza la respuesta de asistencia si el plazo sigue abierto.
    public function respondAttendance(int $activityId, int $studentId, string $response): void
    {
        if (!in_array($response, ['Asistiré', 'No asistiré'], true)) {
            throw new InvalidArgumentException('Selecciona si asistirás o no asistirás.');
        }
        $activity = $this->conexion->prepare(
            "SELECT a.fecha_inicio, a.fecha_limite_confirmacion
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id AND p.estado = 'Activo'
             JOIN proyecto_estudiantes pe ON pe.proyecto_id = p.proyecto_id
               AND pe.estudiante_id = :estudiante AND pe.estado_participacion = 'Activa'
             WHERE a.actividad_id = :actividad"
        );
        $activity->execute(['estudiante' => $studentId, 'actividad' => $activityId]);
        $row = $activity->fetch();
        if ($row === false) {
            throw new InvalidArgumentException('La actividad no está disponible para este estudiante.');
        }
        $deadline = $row['fecha_limite_confirmacion'] ?: $row['fecha_inicio'];
        if (strtotime((string) $deadline) < time()) {
            throw new InvalidArgumentException('El plazo para responder esta actividad ya terminó.');
        }
        $save = $this->conexion->prepare(
            'INSERT INTO respuestas_asistencia (actividad_id, estudiante_id, respuesta)
             VALUES (:actividad, :estudiante, :respuesta)
             ON DUPLICATE KEY UPDATE respuesta = VALUES(respuesta), fecha_respuesta = CURRENT_TIMESTAMP'
        );
        $save->execute(['actividad' => $activityId, 'estudiante' => $studentId, 'respuesta' => $response]);
    }

    // Este bloque lista respuestas y ausencias de estudiantes asignados a una actividad.
    public function attendanceForActivity(int $activityId, string $search = '', int $limit = 50, int $offset = 0): array
    {
        $query = $this->conexion->prepare(
            'SELECT e.estudiante_id, e.nombre_completo, e.carnet, r.respuesta, r.fecha_respuesta
             FROM proyecto_estudiantes pe
             JOIN estudiantes e ON e.estudiante_id = pe.estudiante_id
             LEFT JOIN respuestas_asistencia r
               ON r.estudiante_id = e.estudiante_id AND r.actividad_id = :actividad
             WHERE pe.proyecto_id = (SELECT proyecto_id FROM actividades_proyecto WHERE actividad_id = :actividad_proyecto)
               AND pe.estado_participacion = "Activa"
               AND (:buscar = "" OR e.nombre_completo LIKE :nombre OR e.carnet LIKE :carnet)
             ORDER BY e.nombre_completo LIMIT :limite OFFSET :desplazamiento'
        );
        $pattern = '%' . trim($search) . '%';
        $query->bindValue(':actividad', $activityId, PDO::PARAM_INT);
        $query->bindValue(':actividad_proyecto', $activityId, PDO::PARAM_INT);
        $query->bindValue(':buscar', trim($search), PDO::PARAM_STR);
        $query->bindValue(':nombre', $pattern, PDO::PARAM_STR);
        $query->bindValue(':carnet', $pattern, PDO::PARAM_STR);
        $query->bindValue(':limite', max(1, min(100, $limit)), PDO::PARAM_INT);
        $query->bindValue(':desplazamiento', max(0, $offset), PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countAttendanceForActivity(int $activityId, string $search = ''): int
    {
        $query = $this->conexion->prepare(
            'SELECT COUNT(*)
             FROM proyecto_estudiantes pe
             JOIN estudiantes e ON e.estudiante_id = pe.estudiante_id
             WHERE pe.proyecto_id = (SELECT proyecto_id FROM actividades_proyecto WHERE actividad_id = :actividad)
               AND pe.estado_participacion = "Activa"
               AND (:buscar = "" OR e.nombre_completo LIKE :nombre OR e.carnet LIKE :carnet)'
        );
        $search = trim($search);
        $pattern = '%' . $search . '%';
        $query->execute([
            'actividad' => $activityId,
            'buscar' => $search,
            'nombre' => $pattern,
            'carnet' => $pattern,
        ]);
        return (int) $query->fetchColumn();
    }

    // Este bloque agrega confirmaciones, declinaciones y asignaciones por actividad.
    public function attendanceActivities(
        string $search = '',
        int $projectId = 0,
        string $dateFrom = '',
        string $dateTo = '',
        int $limit = 50,
        int $offset = 0
    ): array
    {
        [$where, $params] = $this->attendanceFilters($search, $projectId, $dateFrom, $dateTo);
        $query = $this->conexion->prepare(
            'SELECT a.actividad_id, a.titulo, a.fecha_inicio, p.nombre AS proyecto,
                    SUM(r.respuesta = "Asistiré") AS confirmados,
                    SUM(r.respuesta = "No asistiré") AS declinados,
                    COUNT(DISTINCT pe.estudiante_id) AS asignados,
                    ac.fecha_cancelacion, ac.motivo AS motivo_cancelacion
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             LEFT JOIN actividad_cancelaciones ac ON ac.actividad_id = a.actividad_id
             LEFT JOIN proyecto_estudiantes pe
               ON pe.proyecto_id = p.proyecto_id AND pe.estado_participacion = "Activa"
             LEFT JOIN respuestas_asistencia r
               ON r.actividad_id = a.actividad_id AND r.estudiante_id = pe.estudiante_id
             ' . $where . '
             GROUP BY a.actividad_id, a.titulo, a.fecha_inicio, p.nombre,
                      ac.fecha_cancelacion, ac.motivo
             ORDER BY a.fecha_inicio DESC LIMIT :limite OFFSET :desplazamiento'
        );
        foreach ($params as $name => $value) {
            $query->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $query->bindValue(':limite', max(1, min(100, $limit)), PDO::PARAM_INT);
        $query->bindValue(':desplazamiento', max(0, $offset), PDO::PARAM_INT);
        $query->execute();
        return $query->fetchAll();
    }

    public function countAttendanceActivities(
        string $search = '',
        int $projectId = 0,
        string $dateFrom = '',
        string $dateTo = ''
    ): int {
        [$where, $params] = $this->attendanceFilters($search, $projectId, $dateFrom, $dateTo);
        $query = $this->conexion->prepare(
            'SELECT COUNT(*)
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id' . $where
        );
        $query->execute($params);
        return (int) $query->fetchColumn();
    }

    public function attendanceProjectOptions(): array
    {
        return $this->conexion->query(
            'SELECT DISTINCT p.proyecto_id, p.nombre
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             ORDER BY p.nombre'
        )->fetchAll();
    }

    private function attendanceFilters(string $search, int $projectId, string $dateFrom, string $dateTo): array
    {
        $conditions = [];
        $params = [];
        $search = trim($search);
        if ($search !== '') {
            $conditions[] = '(a.titulo LIKE :titulo OR p.nombre LIKE :nombre_proyecto)';
            $pattern = '%' . $search . '%';
            $params += ['titulo' => $pattern, 'nombre_proyecto' => $pattern];
        }
        if ($projectId > 0) {
            $conditions[] = 'a.proyecto_id = :proyecto_id';
            $params['proyecto_id'] = $projectId;
        }
        if ($this->isValidDateFilter($dateFrom)) {
            $conditions[] = 'DATE(a.fecha_inicio) >= :fecha_desde';
            $params['fecha_desde'] = $dateFrom;
        }
        if ($this->isValidDateFilter($dateTo)) {
            $conditions[] = 'DATE(a.fecha_inicio) <= :fecha_hasta';
            $params['fecha_hasta'] = $dateTo;
        }
        return [$conditions === [] ? '' : ' WHERE ' . implode(' AND ', $conditions), $params];
    }

    // Este bloque crea una solicitud pendiente y registra su primer evento de historial.
    public function createRequest(int $studentId, ?int $projectId, string $subject, string $details, int $userId): int
    {
        $insert = $this->conexion->prepare(
            "INSERT INTO solicitudes_estudiante (estudiante_id, proyecto_id, asunto, detalle, estado)
             VALUES (:estudiante, :proyecto, :asunto, :detalle, 'Pendiente')"
        );
        $insert->execute([
            'estudiante' => $studentId,
            'proyecto' => $projectId,
            'asunto' => $subject,
            'detalle' => $details,
        ]);
        $id = (int) $this->conexion->lastInsertId();
        $this->addRequestHistory($id, $userId, null, 'Pendiente', 'Enviada', 'Solicitud recibida.');
        return $id;
    }

    // Este bloque permite corregir y reenviar solo solicitudes devueltas por su propietario.
    public function updateReturnedRequest(
        int $requestId,
        int $studentId,
        ?int $projectId,
        string $subject,
        string $details,
        int $userId
    ): bool
    {
        $query = $this->conexion->prepare(
            "UPDATE solicitudes_estudiante
             SET proyecto_id = :proyecto, asunto = :asunto, detalle = :detalle, estado = 'Pendiente',
                 respuesta = NULL, revisada_por = NULL, fecha_revision = NULL
             WHERE solicitud_id = :id AND estudiante_id = :estudiante AND estado = 'Devuelta'"
        );
        $query->execute([
            'proyecto' => $projectId,
            'asunto' => $subject,
            'detalle' => $details,
            'id' => $requestId,
            'estudiante' => $studentId,
        ]);
        if ($query->rowCount() !== 1) {
            return false;
        }
        $this->addRequestHistory($requestId, $userId, 'Devuelta', 'Pendiente', 'Reenviada', 'La persona estudiante corrigió y reenvió la solicitud.');
        return true;
    }

    // Este bloque decide una solicitud pendiente y registra el cambio dentro de una transacción.
    public function reviewRequest(int $requestId, int $reviewerId, string $decision, string $response): bool
    {
        if (!in_array($decision, ['Aprobada', 'Rechazada', 'Devuelta'], true)) {
            throw new InvalidArgumentException('La decisión de la solicitud no es válida.');
        }
        if ($decision !== 'Aprobada' && trim($response) === '') {
            throw new InvalidArgumentException('Indica las observaciones o el motivo de la decisión.');
        }
        if (mb_strlen($response) > 5000) {
            throw new InvalidArgumentException('Las observaciones de la solicitud exceden 5,000 caracteres.');
        }
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }
        try {
            $find = $this->conexion->prepare(
                'SELECT estado FROM solicitudes_estudiante WHERE solicitud_id = :id FOR UPDATE'
            );
            $find->execute(['id' => $requestId]);
            $oldState = $find->fetchColumn();
            if ($oldState !== 'Pendiente') {
                if ($ownsTransaction) {
                    $this->conexion->rollBack();
                }
                return false;
            }
            $update = $this->conexion->prepare(
                'UPDATE solicitudes_estudiante
                 SET estado = :estado, respuesta = :respuesta, revisada_por = :usuario,
                     fecha_revision = CURRENT_TIMESTAMP
                 WHERE solicitud_id = :id'
            );
            $update->execute([
                'estado' => $decision,
                'respuesta' => trim($response) === '' ? null : trim($response),
                'usuario' => $reviewerId,
                'id' => $requestId,
            ]);
            $this->addRequestHistory($requestId, $reviewerId, (string) $oldState, $decision, 'Decisión', $response);
            if ($ownsTransaction) {
                $this->conexion->commit();
            }
            return true;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    // Este bloque filtra la bandeja de solicitudes por estado, estudiante y fecha.
    public function requestsForReview(array $filters = []): array
    {
        $sql = 'SELECT s.solicitud_id, s.estudiante_id, s.asunto, s.detalle, s.estado,
                       s.fecha_solicitud, e.nombre_completo AS estudiante, e.carnet,
                       p.nombre AS proyecto
                FROM solicitudes_estudiante s
                JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
                LEFT JOIN proyectos p ON p.proyecto_id = s.proyecto_id
                WHERE 1 = 1';
        $params = [];
        if (in_array($filters['estado'] ?? '', ['Pendiente', 'Aprobada', 'Rechazada', 'Devuelta'], true)) {
            $sql .= ' AND s.estado = :estado';
            $params['estado'] = $filters['estado'];
        }
        if (trim((string) ($filters['estudiante'] ?? '')) !== '') {
            $sql .= ' AND (e.nombre_completo LIKE :nombre OR e.carnet LIKE :carnet)';
            $term = '%' . trim((string) $filters['estudiante']) . '%';
            $params['nombre'] = $term;
            $params['carnet'] = $term;
        }
        foreach (['desde', 'hasta'] as $dateKey) {
            if (isset($filters[$dateKey]) && Validacion::fechaValida((string) $filters[$dateKey])) {
                $sql .= $dateKey === 'desde' ? ' AND DATE(s.fecha_solicitud) >= :desde' : ' AND DATE(s.fecha_solicitud) <= :hasta';
                $params[$dateKey] = $filters[$dateKey];
            }
        }
        $sql .= ' ORDER BY s.fecha_solicitud DESC LIMIT 200';
        $query = $this->conexion->prepare($sql);
        $query->execute($params);
        return $query->fetchAll();
    }

    // Este bloque reúne detalle, historial y adjuntos para personal revisor.
    public function requestDetails(int $requestId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT s.*, e.nombre_completo AS estudiante, e.carnet, p.nombre AS proyecto
             FROM solicitudes_estudiante s
             JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
             LEFT JOIN proyectos p ON p.proyecto_id = s.proyecto_id
             WHERE s.solicitud_id = :id'
        );
        $query->execute(['id' => $requestId]);
        $request = $query->fetch();
        if ($request === false) {
            return null;
        }
        $request['historial'] = $this->requestHistory($requestId);
        $request['adjuntos'] = $this->requestAttachments($requestId);
        return $request;
    }

    // Este bloque recupera detalle de solicitud solo si pertenece al estudiante indicado.
    public function requestForStudent(int $requestId, int $studentId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT solicitud_id, proyecto_id, asunto, detalle, estado, respuesta, fecha_solicitud, fecha_revision
             FROM solicitudes_estudiante WHERE solicitud_id = :id AND estudiante_id = :estudiante'
        );
        $query->execute(['id' => $requestId, 'estudiante' => $studentId]);
        $request = $query->fetch();
        if ($request === false) {
            return null;
        }
        $request['historial'] = $this->requestHistory($requestId);
        $request['adjuntos'] = $this->requestAttachments($requestId);
        return $request;
    }

    // Este bloque agrega un evento inmutable al historial de la solicitud.
    public function addRequestHistory(int $requestId, ?int $userId, ?string $before, ?string $after, string $action, ?string $comment): void
    {
        $query = $this->conexion->prepare(
            'INSERT INTO solicitud_estudiante_historial
                (solicitud_id, usuario_id, estado_anterior, estado_nuevo, comentario, accion)
             VALUES (:solicitud, :usuario, :anterior, :nuevo, :comentario, :accion)'
        );
        $query->execute([
            'solicitud' => $requestId,
            'usuario' => $userId,
            'anterior' => $before,
            'nuevo' => $after,
            'comentario' => $comment,
            'accion' => $action,
        ]);
    }

    // Este bloque devuelve la secuencia de acciones y responsables de la solicitud.
    public function requestHistory(int $requestId): array
    {
        $query = $this->conexion->prepare(
            'SELECT h.estado_anterior, h.estado_nuevo, h.comentario, h.accion, h.fecha_cambio,
                    u.nombre_completo AS usuario
             FROM solicitud_estudiante_historial h
             LEFT JOIN usuarios u ON u.usuario_id = h.usuario_id
             WHERE h.solicitud_id = :id ORDER BY h.fecha_cambio, h.historial_id'
        );
        $query->execute(['id' => $requestId]);
        return $query->fetchAll();
    }

    // Este bloque lista metadatos de adjuntos sin exponer claves de almacenamiento.
    public function requestAttachments(int $requestId): array
    {
        $query = $this->conexion->prepare(
            'SELECT adjunto_id, nombre_original, tipo_mime, tamano_bytes, fecha_subida
             FROM solicitud_estudiante_adjuntos WHERE solicitud_id = :id ORDER BY fecha_subida'
        );
        $query->execute(['id' => $requestId]);
        return $query->fetchAll();
    }

    // Este bloque guarda metadatos de un archivo privado asociado a una solicitud.
    public function addRequestAttachment(
        int $requestId,
        string $originalName,
        string $storageKey,
        string $mime,
        int $size,
        int $userId
    ): void {
        $query = $this->conexion->prepare(
            'INSERT INTO solicitud_estudiante_adjuntos
                (solicitud_id, nombre_original, clave_almacenamiento, tipo_mime, tamano_bytes, subido_por)
             VALUES (:solicitud, :nombre, :clave, :mime, :tamano, :usuario)'
        );
        $query->execute([
            'solicitud' => $requestId,
            'nombre' => mb_substr(basename($originalName), 0, 255),
            'clave' => $storageKey,
            'mime' => $mime,
            'tamano' => $size,
            'usuario' => $userId,
        ]);
    }

    // Este bloque consulta un adjunto con el propietario del expediente relacionado.
    public function attachment(int $attachmentId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT a.*, s.estudiante_id FROM solicitud_estudiante_adjuntos a
             JOIN solicitudes_estudiante s ON s.solicitud_id = a.solicitud_id
             WHERE a.adjunto_id = :id'
        );
        $query->execute(['id' => $attachmentId]);
        $row = $query->fetch();
        return $row === false ? null : $row;
    }

    // Este bloque devuelve la versión vigente del aviso de privacidad.
    public function currentPrivacyNotice(): ?array
    {
        $query = $this->conexion->query(
            'SELECT aviso_id, version, contenido, requiere_aceptacion, fecha_publicacion
             FROM avisos_privacidad WHERE vigente = 1 ORDER BY fecha_publicacion DESC LIMIT 1'
        );
        $notice = $query->fetch();
        return $notice === false ? null : $notice;
    }

    // Este bloque publica una nueva versión y conserva las versiones anteriores.
    public function publishPrivacyNotice(string $version, string $content, bool $required, int $userId): int
    {
        $version = trim($version);
        $content = trim($content);
        if ($version === '' || mb_strlen($version) > 40 || $content === '' || mb_strlen($content) > 50000) {
            throw new InvalidArgumentException('Indica versión y contenido del aviso (máximo 50,000 caracteres).');
        }
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }
        try {
            $this->conexion->exec('UPDATE avisos_privacidad SET vigente = 0 WHERE vigente = 1');
            $insert = $this->conexion->prepare(
                'INSERT INTO avisos_privacidad (version, contenido, vigente, requiere_aceptacion, creado_por)
                 VALUES (:version, :contenido, 1, :requerida, :usuario)'
            );
            $insert->execute([
                'version' => $version,
                'contenido' => $content,
                'requerida' => $required ? 1 : 0,
                'usuario' => $userId,
            ]);
            $id = (int) $this->conexion->lastInsertId();
            if ($ownsTransaction) {
                $this->conexion->commit();
            }
            return $id;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    // Este bloque registra una aceptación solo si la versión continúa vigente.
    public function acceptPrivacyNotice(int $userId, int $noticeId): void
    {
        $current = $this->currentPrivacyNotice();
        if ($current === null || (int) $current['aviso_id'] !== $noticeId) {
            throw new InvalidArgumentException('El aviso de privacidad cambió. Recarga la página y revisa la versión vigente.');
        }
        $insert = $this->conexion->prepare(
            'INSERT IGNORE INTO aceptaciones_privacidad (aviso_id, usuario_id) VALUES (:aviso, :usuario)'
        );
        $insert->execute(['aviso' => $noticeId, 'usuario' => $userId]);
    }

    // Este bloque lista versiones de privacidad aceptadas por una cuenta.
    public function privacyAcceptances(int $userId): array
    {
        $query = $this->conexion->prepare(
            'SELECT n.version, a.fecha_aceptacion
             FROM aceptaciones_privacidad a JOIN avisos_privacidad n ON n.aviso_id = a.aviso_id
             WHERE a.usuario_id = :usuario ORDER BY a.fecha_aceptacion DESC'
        );
        $query->execute(['usuario' => $userId]);
        return $query->fetchAll();
    }
}
