<?php
declare(strict_types=1);

final class RegistroHoras
{
    // Este bloque recibe la conexión usada para reportes de horas.
    public function __construct(private PDO $conexion) {}

    // Este bloque valida y crea un registro de horas pendiente de revisión.
    public function create(int $actividadId, int $estudianteId, $horas, string $descripcion, string $fechaActividad, int $reportadoPor): int
    {
        if (!Validacion::horasRegistroValida($horas)) {
            throw new InvalidArgumentException('Las horas deben tener hasta dos decimales, ser mayores que cero y no superar 8 por día.');
        }
        if (trim($descripcion) === '' || mb_strlen(trim($descripcion)) > 1000) {
            throw new InvalidArgumentException('La descripción es obligatoria y no debe exceder 1,000 caracteres.');
        }
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaActividad);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaActividad) {
            throw new InvalidArgumentException('La fecha de la actividad no es válida.');
        }
        if ($reportadoPor < 1) throw new InvalidArgumentException('No se pudo identificar a la persona que reporta.');
        $hours = round((float) $horas, 2);
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        }

        try {
            $studentLock = $this->conexion->prepare(
                'SELECT estudiante_id FROM estudiantes
                 WHERE estudiante_id = :estudiante AND estado_registro = "Activo"
                 FOR UPDATE'
            );
            $studentLock->execute(['estudiante' => $estudianteId]);
            if ($studentLock->fetchColumn() === false) {
                throw new InvalidArgumentException('El expediente del estudiante no existe o no está activo.');
            }

            $activity = $this->conexion->prepare(
                'SELECT a.fecha_inicio, a.fecha_fin
                 FROM actividades_proyecto a
                 JOIN proyecto_estudiantes pe ON pe.proyecto_id = a.proyecto_id
                 WHERE a.actividad_id = :actividad
                   AND pe.estudiante_id = :estudiante
                   AND pe.estado_participacion = "Activa"
                   AND pe.fecha_desasignacion IS NULL'
            );
            $activity->execute(['actividad' => $actividadId, 'estudiante' => $estudianteId]);
            $activityDates = $activity->fetch();
            if ($activityDates === false) {
                throw new InvalidArgumentException('La actividad no pertenece a un proyecto asignado al estudiante.');
            }
            if (
                $fechaActividad < substr((string) $activityDates['fecha_inicio'], 0, 10)
                || $fechaActividad > substr((string) $activityDates['fecha_fin'], 0, 10)
            ) {
                throw new InvalidArgumentException('La fecha registrada debe estar dentro de las fechas de la actividad.');
            }

            $dailyHours = $this->conexion->prepare(
                'SELECT horas
                 FROM registros_horas
                 WHERE estudiante_id = :estudiante
                   AND fecha_actividad = :fecha
                   AND estado <> "Rechazado"'
                   . ' FOR UPDATE'
            );
            $dailyHours->execute(['estudiante' => $estudianteId, 'fecha' => $fechaActividad]);
            $registeredHours = array_sum(array_map('floatval', $dailyHours->fetchAll(PDO::FETCH_COLUMN)));
            if (round($registeredHours + $hours, 2) > 8) {
                throw new InvalidArgumentException('El total registrado no puede superar 8 horas por estudiante al día.');
            }

            $insert = $this->conexion->prepare(
                'INSERT INTO registros_horas
                    (actividad_id, estudiante_id, reportado_por, horas, fecha_actividad, descripcion, estado)
                 VALUES (:actividad, :estudiante, :reportado_por, :horas, :fecha, :descripcion, "Pendiente")'
            );
            $insert->execute([
                'actividad' => $actividadId,
                'estudiante' => $estudianteId,
                'reportado_por' => $reportadoPor,
                'horas' => $hours,
                'fecha' => $fechaActividad,
                'descripcion' => trim($descripcion),
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

    // Este bloque detecta posibles registros repetidos para solicitar confirmación.
    public function hasPotentialDuplicate(int $actividadId, int $estudianteId, string $fechaActividad): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT 1 FROM registros_horas WHERE actividad_id = :actividad_id AND estudiante_id = :estudiante_id
             AND fecha_actividad = :fecha_actividad LIMIT 1'
        );
        $consulta->execute([
            'actividad_id' => $actividadId,
            'estudiante_id' => $estudianteId,
            'fecha_actividad' => $fechaActividad,
        ]);
        return $consulta->fetchColumn() !== false;
    }

    // Este bloque resuelve el expediente activo asociado a una cuenta.
    public function studentIdForUser(int $usuarioId): ?int
    {
        $q=$this->conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE usuario_id=:u AND estado_registro="Activo"');$q->execute(['u'=>$usuarioId]);$id=$q->fetchColumn();return $id===false?null:(int)$id;
    }
}
