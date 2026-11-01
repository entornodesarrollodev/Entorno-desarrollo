<?php
declare(strict_types=1);

final class RegistroHoras
{
    public function __construct(private PDO $conexion) {}

    public function create(int $actividadId, int $estudianteId, $horas, string $descripcion, string $fechaActividad, int $reportadoPor): int
    {
        if (!is_numeric($horas) || (float)$horas <= 0 || (float)$horas > 12) {
            throw new InvalidArgumentException('Las horas deben ser numéricas, mayores que cero y no superar 12.');
        }
        if (trim($descripcion) === '') throw new InvalidArgumentException('La descripción es obligatoria.');
        $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', $fechaActividad);
        if ($fecha === false || $fecha->format('Y-m-d') !== $fechaActividad) {
            throw new InvalidArgumentException('La fecha de la actividad no es válida.');
        }
        if ($reportadoPor < 1) throw new InvalidArgumentException('No se pudo identificar a la persona que reporta.');
        $q=$this->conexion->prepare('SELECT 1 FROM actividades_proyecto a JOIN proyecto_estudiantes pe ON pe.proyecto_id=a.proyecto_id
            WHERE a.actividad_id=:a AND pe.estudiante_id=:e AND pe.fecha_desasignacion IS NULL');$q->execute(['a'=>$actividadId,'e'=>$estudianteId]);
        if ($q->fetchColumn() === false) throw new InvalidArgumentException('La actividad no pertenece a un proyecto asignado al estudiante.');
        $q=$this->conexion->prepare('INSERT INTO registros_horas (actividad_id,estudiante_id,reportado_por,horas,fecha_actividad,descripcion,estado) VALUES (:a,:e,:reportado_por,:h,:fecha,:d,"Pendiente")');
        $q->execute(['a'=>$actividadId,'e'=>$estudianteId,'reportado_por'=>$reportadoPor,'h'=>(float)$horas,'fecha'=>$fechaActividad,'d'=>trim($descripcion)]); return (int)$this->conexion->lastInsertId();
    }

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

    public function studentIdForUser(int $usuarioId): ?int
    {
        $q=$this->conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE usuario_id=:u AND estado_registro="Activo"');$q->execute(['u'=>$usuarioId]);$id=$q->fetchColumn();return $id===false?null:(int)$id;
    }
}
