<?php
declare(strict_types=1);

final class Proyecto
{
    public function __construct(private PDO $conexion) {}

    public function create(array $d, int $usuarioId): int
    {
        $d = $this->normalizeProject($d);
        $this->validateProject($d);
        $q = $this->conexion->prepare('INSERT INTO proyectos (nombre,codigo,descripcion,objetivo,requisitos,carrera,anio_academico,modalidad,fecha_inicio,fecha_fin,estado,creado_por,responsable_id) VALUES (:nombre,:codigo,:descripcion,:objetivo,:requisitos,:carrera,:anio,:modalidad,:inicio,:fin,:estado,:creado_por,:responsable)');
        $q->execute(['nombre'=>$d['nombre'],'codigo'=>$d['codigo'],'descripcion'=>$d['descripcion'],'objetivo'=>$d['objetivo'],'requisitos'=>$d['requisitos']?:null,'carrera'=>$d['carrera'],'anio'=>$d['anio_academico'],'modalidad'=>$d['modalidad'],'inicio'=>$d['fecha_inicio'],'fin'=>$d['fecha_fin']?:null,'estado'=>$d['estado'],'creado_por'=>$usuarioId,'responsable'=>$d['responsable_id']?:$usuarioId]);
        return (int)$this->conexion->lastInsertId();
    }

    public function update(int $id, array $d, int $usuarioId): void
    {
        $d=$this->normalizeProject($d);$this->validateProject($d);
        $q=$this->conexion->prepare('UPDATE proyectos SET nombre=:nombre,codigo=:codigo,descripcion=:descripcion,objetivo=:objetivo,requisitos=:requisitos,carrera=:carrera,anio_academico=:anio,modalidad=:modalidad,fecha_inicio=:inicio,fecha_fin=:fin,estado=:estado,responsable_id=:responsable,modificado_por=:modificado_por WHERE proyecto_id=:id');
        $q->execute(['nombre'=>$d['nombre'],'codigo'=>$d['codigo'],'descripcion'=>$d['descripcion'],'objetivo'=>$d['objetivo'],'requisitos'=>$d['requisitos']?:null,'carrera'=>$d['carrera'],'anio'=>$d['anio_academico'],'modalidad'=>$d['modalidad'],'inicio'=>$d['fecha_inicio'],'fin'=>$d['fecha_fin']?:null,'estado'=>$d['estado'],'responsable'=>$d['responsable_id']?:null,'modificado_por'=>$usuarioId,'id'=>$id]);
        if($q->rowCount()===0&&$this->find($id)===null)throw new InvalidArgumentException('El proyecto no existe.');
    }

    public function find(int $id): ?array
    {
        $q=$this->conexion->prepare('SELECT p.*,u.nombre_completo AS responsable_nombre,u.correo AS responsable_correo FROM proyectos p LEFT JOIN usuarios u ON u.usuario_id=p.responsable_id WHERE p.proyecto_id=:id');$q->execute(['id'=>$id]);$r=$q->fetch();return $r===false?null:$r;
    }

    public function all(array $f=[]): array
    {
        $w=[];$p=[];
        if(($f['q']??'')!==''){$w[]='(p.nombre LIKE :q1 OR p.codigo LIKE :q2)';$term='%'.trim((string)$f['q']).'%';$p['q1']=$term;$p['q2']=$term;}
        foreach(['carrera','modalidad','estado'] as $c)if(($f[$c]??'')!==''){$w[]="p.$c=:$c";$p[$c]=$f[$c];}
        if(($f['anio_academico']??'')!==''){$w[]='p.anio_academico=:anio_academico';$p['anio_academico']=(int)$f['anio_academico'];}
        $q=$this->conexion->prepare('SELECT p.*,u.nombre_completo AS responsable_nombre,u.correo AS responsable_correo FROM proyectos p LEFT JOIN usuarios u ON u.usuario_id=p.responsable_id'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY p.fecha_inicio DESC,p.nombre');$q->execute($p);return $q->fetchAll();
    }

    public function addActivity(array $d,int $usuarioId): int
    {
        $d=$this->normalizeActivity($d,$usuarioId);$this->validateActivity($d);$project=$this->find((int)$d['proyecto_id']);
        if($project===null||$project['estado']!=='Activo')throw new InvalidArgumentException('El proyecto no existe o no está activo.');
        $this->assertNoOverlap($d);
        $q=$this->conexion->prepare('INSERT INTO actividades_proyecto (proyecto_id,titulo,descripcion,modalidad,fecha_inicio,fecha_fin,lugar,horas_estimadas,cupo,responsable_id,recurso,creado_por) VALUES (:proyecto,:titulo,:descripcion,:modalidad,:inicio,:fin,:lugar,:horas,:cupo,:responsable,:recurso,:creado_por)');
        $q->execute(['proyecto'=>$d['proyecto_id'],'titulo'=>$d['titulo'],'descripcion'=>$d['descripcion'],'modalidad'=>$d['modalidad'],'inicio'=>$d['fecha_inicio'],'fin'=>$d['fecha_fin'],'lugar'=>$d['lugar'],'horas'=>$d['horas_estimadas'],'cupo'=>$d['cupo']?:null,'responsable'=>$d['responsable_id'],'recurso'=>$d['recurso']?:$d['lugar'],'creado_por'=>$usuarioId]);return(int)$this->conexion->lastInsertId();
    }

    public function findActivity(int $activityId): ?array
    {
        $query = $this->conexion->prepare(
            'SELECT a.*, p.nombre AS proyecto
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             WHERE a.actividad_id = :id'
        );
        $query->execute(['id' => $activityId]);
        $activity = $query->fetch();

        return $activity === false ? null : $activity;
    }

    public function updateActivity(int $activityId, array $data, int $usuarioId): void
    {
        $data = $this->normalizeActivity($data, $usuarioId);
        $this->validateActivity($data);
        $project = $this->find((int) $data['proyecto_id']);
        if ($project === null || $project['estado'] !== 'Activo') {
            throw new InvalidArgumentException('El proyecto no existe o no está activo.');
        }
        if ($this->findActivity($activityId) === null) {
            throw new InvalidArgumentException('La actividad no existe.');
        }
        $this->assertNoOverlap($data, $activityId);

        $query = $this->conexion->prepare(
            'UPDATE actividades_proyecto
             SET proyecto_id = :proyecto, titulo = :titulo, descripcion = :descripcion,
                 modalidad = :modalidad, fecha_inicio = :inicio, fecha_fin = :fin,
                 lugar = :lugar, horas_estimadas = :horas, cupo = :cupo,
                 responsable_id = :responsable, recurso = :recurso
             WHERE actividad_id = :id'
        );
        $query->execute([
            'proyecto' => $data['proyecto_id'],
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'modalidad' => $data['modalidad'],
            'inicio' => $data['fecha_inicio'],
            'fin' => $data['fecha_fin'],
            'lugar' => $data['lugar'],
            'horas' => $data['horas_estimadas'],
            'cupo' => $data['cupo'],
            'responsable' => $data['responsable_id'],
            'recurso' => $data['recurso'] !== '' ? $data['recurso'] : $data['lugar'],
            'id' => $activityId,
        ]);
    }

    public function activities(int $projectId=0): array
    {
        $sql='SELECT a.*,p.nombre AS proyecto,u.nombre_completo AS responsable_nombre FROM actividades_proyecto a JOIN proyectos p ON p.proyecto_id=a.proyecto_id LEFT JOIN usuarios u ON u.usuario_id=a.responsable_id';$p=[];
        if($projectId>0){$sql.=' WHERE a.proyecto_id=:proyecto_id';$p['proyecto_id']=$projectId;}$sql.=' ORDER BY a.fecha_inicio';$q=$this->conexion->prepare($sql);$q->execute($p);return$q->fetchAll();
    }

    public function assignments(int $projectId): array
    {
        $q=$this->conexion->prepare('SELECT pe.*,e.nombre_completo,COALESCE(e.carnet,u.carnet) AS carnet,e.correo,e.estado_registro FROM proyecto_estudiantes pe JOIN estudiantes e ON e.estudiante_id=pe.estudiante_id LEFT JOIN usuarios u ON u.usuario_id=e.usuario_id WHERE pe.proyecto_id=:proyecto_id ORDER BY pe.fecha_asignacion DESC,e.nombre_completo');$q->execute(['proyecto_id'=>$projectId]);return$q->fetchAll();
    }

    public function projectsForStudent(int $studentId): array
    {
        $q=$this->conexion->prepare('SELECT p.*,pe.fecha_asignacion,pe.fecha_desasignacion FROM proyecto_estudiantes pe JOIN proyectos p ON p.proyecto_id=pe.proyecto_id WHERE pe.estudiante_id=:estudiante_id ORDER BY pe.fecha_asignacion DESC');$q->execute(['estudiante_id'=>$studentId]);return$q->fetchAll();
    }

    public function activeStudents(string $search=''): array
    {
        $q=$this->conexion->prepare('SELECT e.estudiante_id,e.nombre_completo,COALESCE(e.carnet,u.carnet) AS carnet,e.correo FROM estudiantes e LEFT JOIN usuarios u ON u.usuario_id=e.usuario_id WHERE e.estado_registro="Activo" AND COALESCE(u.estado_activo,1)=1 AND (e.nombre_completo LIKE :search1 OR COALESCE(e.carnet,u.carnet) LIKE :search2 OR e.correo LIKE :search3) ORDER BY e.nombre_completo LIMIT 50');$term='%'.trim($search).'%';$q->execute(['search1'=>$term,'search2'=>$term,'search3'=>$term]);return$q->fetchAll();
    }

    public function assign(int $proyectoId, int $estudianteId, int $usuarioId, string $funcion = 'Participante'): int
    {
        $funcion = trim($funcion);
        if (preg_match('/^.{1,120}$/us', $funcion) !== 1) {
            throw new InvalidArgumentException('Indica una función de participación de hasta 120 caracteres.');
        }
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }

        try {
            $query = $this->conexion->prepare('SELECT estado FROM proyectos WHERE proyecto_id = :id FOR UPDATE');
            $query->execute(['id' => $proyectoId]);
            if ($query->fetchColumn() !== 'Activo') {
                throw new InvalidArgumentException('El proyecto no existe o no está activo.');
            }

            $query = $this->conexion->prepare(
                'SELECT e.estado_registro, COALESCE(u.estado_activo, 1) AS cuenta_activa
                 FROM estudiantes e
                 LEFT JOIN usuarios u ON u.usuario_id = e.usuario_id
                 WHERE e.estudiante_id = :id
                 FOR UPDATE'
            );
            $query->execute(['id' => $estudianteId]);
            $student = $query->fetch();
            if ($student === false || $student['estado_registro'] !== 'Activo' || (int) $student['cuenta_activa'] !== 1) {
                throw new InvalidArgumentException('El estudiante no existe o no está activo.');
            }

            $query = $this->conexion->prepare(
                'SELECT 1 FROM proyecto_estudiantes
                 WHERE proyecto_id = :proyecto AND estudiante_id = :estudiante AND fecha_desasignacion IS NULL
                 LIMIT 1 FOR UPDATE'
            );
            $query->execute(['proyecto' => $proyectoId, 'estudiante' => $estudianteId]);
            if ($query->fetchColumn() !== false) {
                throw new DomainException('El estudiante ya tiene una asignación activa en este proyecto.');
            }

            $insert = $this->conexion->prepare(
                'INSERT INTO proyecto_estudiantes (proyecto_id, estudiante_id, asignado_por, funcion, estado_participacion)
                 VALUES (:proyecto, :estudiante, :usuario, :funcion, "Activa")'
            );
            $insert->execute([
                'proyecto' => $proyectoId,
                'estudiante' => $estudianteId,
                'usuario' => $usuarioId,
                'funcion' => $funcion,
            ]);
            $assignmentId = (int) $this->conexion->lastInsertId();

            if ($ownsTransaction) {
                $this->conexion->commit();
            }

            return $assignmentId;
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function removeAssignment(int $assignmentId): bool
    {
        $query = $this->conexion->prepare(
            'UPDATE proyecto_estudiantes
             SET fecha_desasignacion = CURRENT_TIMESTAMP, estado_participacion = "Retirada"
             WHERE asignacion_id = :id AND fecha_desasignacion IS NULL'
        );
        $query->execute(['id' => $assignmentId]);
        return $query->rowCount() === 1;
    }

    public function activitiesForStudent(int $usuarioId): array
    {
        $q=$this->conexion->prepare('SELECT a.actividad_id,a.titulo,a.descripcion,a.fecha_inicio,a.fecha_fin,a.lugar,a.horas_estimadas,p.nombre AS proyecto FROM actividades_proyecto a JOIN proyectos p ON p.proyecto_id=a.proyecto_id JOIN proyecto_estudiantes pe ON pe.proyecto_id=p.proyecto_id JOIN estudiantes e ON e.estudiante_id=pe.estudiante_id WHERE e.usuario_id=:usuario AND e.estado_registro="Activo" AND p.estado="Activo" AND pe.fecha_desasignacion IS NULL ORDER BY a.fecha_inicio');$q->execute(['usuario'=>$usuarioId]);return$q->fetchAll();
    }

    public function activitiesForStudentRecord(int $studentId): array
    {
        $query = $this->conexion->prepare(
            'SELECT a.actividad_id, a.titulo, a.fecha_inicio, a.fecha_fin, p.nombre AS proyecto
             FROM actividades_proyecto a
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             JOIN proyecto_estudiantes pe ON pe.proyecto_id = p.proyecto_id
             JOIN estudiantes e ON e.estudiante_id = pe.estudiante_id
             LEFT JOIN usuarios u ON u.usuario_id = e.usuario_id
             WHERE e.estudiante_id = :estudiante_id
               AND e.estado_registro = "Activo"
               AND COALESCE(u.estado_activo, 1) = 1
               AND p.estado = "Activo"
               AND pe.fecha_desasignacion IS NULL
             ORDER BY a.fecha_inicio'
        );
        $query->execute(['estudiante_id' => $studentId]);
        return $query->fetchAll();
    }

    public function pendingHours(): array
    {
        return $this->conexion->query('SELECT rh.*,e.nombre_completo,e.carnet,a.titulo,p.nombre AS proyecto,u.nombre_completo AS reportado_por_nombre
            FROM registros_horas rh
            JOIN estudiantes e ON e.estudiante_id=rh.estudiante_id
            JOIN actividades_proyecto a ON a.actividad_id=rh.actividad_id
            JOIN proyectos p ON p.proyecto_id=a.proyecto_id
            LEFT JOIN usuarios u ON u.usuario_id=rh.reportado_por
            WHERE rh.estado="Pendiente" ORDER BY rh.fecha_actividad,rh.fecha_registro')->fetchAll();
    }

    public function reviewHours(int $registroId,int $reviewerId,string $decision,string $motivo=''): bool
    {
        if(!in_array($decision,['Aprobado','Rechazado'],true))throw new InvalidArgumentException('Decisión inválida.');if($decision==='Rechazado'&&trim($motivo)==='')throw new InvalidArgumentException('El motivo de rechazo es obligatorio.');$q=$this->conexion->prepare('UPDATE registros_horas SET estado=:estado,revisado_por=:revisor,motivo_revision=:motivo,fecha_revision=CURRENT_TIMESTAMP WHERE registro_id=:id AND estado="Pendiente"');$q->execute(['estado'=>$decision,'revisor'=>$reviewerId,'motivo'=>$motivo!==''?trim($motivo):null,'id'=>$registroId]);return$q->rowCount()===1;
    }

    public function hoursSummary(int $estudianteId,?string $desde=null,?string $hasta=null): array
    {
        $where = ['rh.estudiante_id = :estudiante_id'];
        $params = ['estudiante_id' => $estudianteId];
        foreach (['desde' => '>=', 'hasta' => '<='] as $key => $operator) {
            if ($$key !== null && $$key !== '') {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $$key);
                if ($date === false || $date->format('Y-m-d') !== $$key) {
                    throw new InvalidArgumentException('El periodo de consulta no es válido.');
                }
                $where[] = 'rh.fecha_actividad ' . $operator . ' :' . $key;
                $params[$key] = $$key;
            }
        }
        $condition = implode(' AND ', $where);
        $query = $this->conexion->prepare(
            'SELECT COALESCE(SUM(CASE WHEN rh.estado = "Aprobado" THEN rh.horas ELSE 0 END), 0) AS aprobadas,
                    COALESCE(SUM(CASE WHEN rh.estado = "Pendiente" THEN rh.horas ELSE 0 END), 0) AS pendientes,
                    COALESCE(SUM(CASE WHEN rh.estado = "Rechazado" THEN rh.horas ELSE 0 END), 0) AS rechazadas
             FROM registros_horas rh WHERE ' . $condition
        );
        $query->execute($params);
        $totales = $query->fetch();
        $detalle = $this->conexion->prepare(
            'SELECT p.proyecto_id, p.nombre AS proyecto, DATE_FORMAT(rh.fecha_actividad, "%Y-%m") AS periodo,
                    SUM(CASE WHEN rh.estado = "Aprobado" THEN rh.horas ELSE 0 END) AS horas_aprobadas,
                    SUM(CASE WHEN rh.estado = "Pendiente" THEN rh.horas ELSE 0 END) AS horas_pendientes,
                    SUM(CASE WHEN rh.estado = "Rechazado" THEN rh.horas ELSE 0 END) AS horas_rechazadas
             FROM registros_horas rh
             JOIN actividades_proyecto a ON a.actividad_id = rh.actividad_id
             JOIN proyectos p ON p.proyecto_id = a.proyecto_id
             WHERE ' . $condition . '
             GROUP BY p.proyecto_id, p.nombre, DATE_FORMAT(rh.fecha_actividad, "%Y-%m")
             ORDER BY periodo DESC, p.nombre'
        );
        $detalle->execute($params);
        return [
            'total' => (float) $totales['aprobadas'],
            'pendientes' => (float) $totales['pendientes'],
            'rechazadas' => (float) $totales['rechazadas'],
            'desglose' => $detalle->fetchAll(),
        ];
    }

    private function normalizeProject(array $d): array
    {
        $d['objetivo'] = trim((string) ($d['objetivo'] ?? $d['descripcion'] ?? ''));
        $d['requisitos'] = trim((string) ($d['requisitos'] ?? ''));
        $d['estado'] = (string) ($d['estado'] ?? 'Activo');
        $d['responsable_id'] = (int) ($d['responsable_id'] ?? 0);

        return $d;
    }

    private function validateProject(array $d): void
    {
        foreach (['nombre', 'codigo', 'descripcion', 'objetivo', 'carrera', 'modalidad', 'fecha_inicio'] as $field) {
            if (trim((string) ($d[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Falta el campo obligatorio: ' . $field . '.');
            }
        }
        if (!in_array($d['modalidad'], ['Presencial', 'Virtual', 'Mixta'], true)) {
            throw new InvalidArgumentException('La modalidad no es válida.');
        }
        if (!in_array((int) $d['anio_academico'], [1, 2], true)) {
            throw new InvalidArgumentException('El año académico no es válido.');
        }
        if (!in_array($d['estado'], ['Activo', 'Inactivo', 'Finalizado', 'En Pausa'], true)) {
            throw new InvalidArgumentException('El estado del proyecto no es válido.');
        }
        if ((int) $d['responsable_id'] < 1) {
            throw new InvalidArgumentException('El responsable del proyecto es obligatorio.');
        }

        $start = (string) $d['fecha_inicio'];
        $end = (string) ($d['fecha_fin'] ?? '');
        if (!Validacion::fechaValida($start) || ($end !== '' && (!Validacion::fechaValida($end) || $end < $start))) {
            throw new InvalidArgumentException('La fecha final no puede ser anterior a la inicial y ambas fechas deben ser válidas.');
        }
    }

    private function normalizeActivity(array $d, int $usuarioId): array
    {
        $d['descripcion'] = trim((string) ($d['descripcion'] ?? $d['titulo'] ?? ''));
        $d['modalidad'] = (string) ($d['modalidad'] ?? 'Presencial');
        $d['horas_estimadas'] = $d['horas_estimadas'] ?? 1;
        $d['cupo'] = ($d['cupo'] ?? '') === '' ? null : (int) $d['cupo'];
        $d['responsable_id'] = (int) ($d['responsable_id'] ?? $usuarioId);
        $d['recurso'] = trim((string) ($d['recurso'] ?? $d['lugar'] ?? ''));
        foreach (['fecha_inicio', 'fecha_fin'] as $field) {
            $value = (string) ($d[$field] ?? '');
            $d[$field] = Validacion::fechaHoraLocalValida($value)
                ? Validacion::normalizarFechaHoraLocal($value)
                : $value;
        }

        return $d;
    }

    private function validateActivity(array $d): void
    {
        foreach (['titulo', 'descripcion', 'proyecto_id', 'fecha_inicio', 'fecha_fin', 'lugar', 'responsable_id'] as $field) {
            if (trim((string) ($d[$field] ?? '')) === '') {
                throw new InvalidArgumentException('Falta el campo obligatorio: ' . $field . '.');
            }
        }
        if (!in_array($d['modalidad'], ['Presencial', 'Virtual', 'Mixta'], true)) {
            throw new InvalidArgumentException('La modalidad de la actividad no es válida.');
        }
        if (!Validacion::fechaValida((string) $d['fecha_inicio'], '!Y-m-d H:i:s')
            || !Validacion::fechaValida((string) $d['fecha_fin'], '!Y-m-d H:i:s')
            || $d['fecha_fin'] <= $d['fecha_inicio']) {
            throw new InvalidArgumentException('La fecha y hora final debe ser posterior al inicio.');
        }
        if (!is_numeric($d['horas_estimadas']) || (float) $d['horas_estimadas'] <= 0 || (float) $d['horas_estimadas'] > 24) {
            throw new InvalidArgumentException('Las horas estimadas deben ser numéricas y estar entre 0 y 24.');
        }
        if ($d['cupo'] !== null && (!is_int($d['cupo']) || $d['cupo'] <= 0)) {
            throw new InvalidArgumentException('El cupo debe ser un número entero mayor que cero.');
        }
    }

    private function assertNoOverlap(array $d, int $excludeId = 0): void
    {
        $sql = 'SELECT a.titulo, a.fecha_inicio, a.fecha_fin, p.nombre AS proyecto
                FROM actividades_proyecto a
                JOIN proyectos p ON p.proyecto_id = a.proyecto_id
                WHERE a.fecha_inicio < :fin AND a.fecha_fin > :inicio
                  AND (a.responsable_id = :responsable
                       OR (a.recurso IS NOT NULL AND a.recurso <> "" AND a.recurso = :recurso))';
        $params = [
            'fin' => $d['fecha_fin'],
            'inicio' => $d['fecha_inicio'],
            'responsable' => $d['responsable_id'],
            'recurso' => $d['recurso'],
        ];
        if ($excludeId > 0) {
            $sql .= ' AND a.actividad_id <> :excluir';
            $params['excluir'] = $excludeId;
        }

        $query = $this->conexion->prepare($sql . ' ORDER BY a.fecha_inicio LIMIT 5');
        $query->execute($params);
        $conflicts = $query->fetchAll();
        if ($conflicts !== []) {
            $titles = array_map(
                static fn (array $activity): string => $activity['titulo'] . ' (' . $activity['proyecto'] . ', '
                    . $activity['fecha_inicio'] . '–' . $activity['fecha_fin'] . ')',
                $conflicts
            );
            throw new DomainException('Conflicto de horario con: ' . implode('; ', $titles));
        }
    }
}
