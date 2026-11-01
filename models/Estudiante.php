<?php
declare(strict_types=1);
require_once __DIR__ . '/Validacion.php';

final class Estudiante
{
    public function __construct(private PDO $conexion) {}
    public function create(array|string $d, ?string $correo = null, ?string $telefono = null, ?string $carrera = null, ?int $anioAcademico = null): int
    {
        if (is_string($d)) {
            $partes = preg_split('/\s+/', trim($d), 2) ?: [$d, ''];
            $d = ['nombre_completo' => trim($d), 'nombres' => $partes[0], 'apellidos' => $partes[1] ?? '',
                'carnet' => '', 'correo' => (string)$correo, 'telefono' => $telefono, 'carrera' => (string)$carrera,
                'anio_academico' => $anioAcademico ?? 1, 'dui' => '', 'fecha_nacimiento' => '', 'genero' => '',
                'direccion' => '', 'estado_registro' => 'Activo', 'modalidad_participacion' => 'Horas sociales'];
        }
        $d['modalidad_participacion'] = $d['modalidad_participacion'] ?? 'Horas sociales';
        $this->validateData($d);
        if (($d['usuario_id'] ?? null) === null) {
            $u = $this->conexion->prepare('SELECT usuario_id, carnet FROM usuarios WHERE correo=:correo LIMIT 1');
            $u->execute(['correo' => $d['correo']]);
            $usuario = $u->fetch();
            if ($usuario !== false) { $d['usuario_id'] = (int)$usuario['usuario_id']; if (($d['carnet'] ?? '') === '') $d['carnet'] = (string)$usuario['carnet']; }
        }
        $q=$this->conexion->prepare('INSERT INTO estudiantes (usuario_id,nombre_completo,nombres,apellidos,correo,carnet,telefono,carrera,anio_academico,modalidad_participacion,dui,fecha_nacimiento,genero,direccion,estado_registro) VALUES (:usuario,:nombre,:nombres,:apellidos,:correo,:carnet,:telefono,:carrera,:anio,:modalidad_participacion,:dui,:nacimiento,:genero,:direccion,:estado)');
        $q->execute($this->params($d)); return (int)$this->conexion->lastInsertId();
    }
    public function update(int $id,array $d): void
    {
        $this->validateData($d);
        $student = $this->find($id);
        if ($student === null) {
            throw new InvalidArgumentException('El estudiante no existe.');
        }

        $params = $this->params($d);
        unset($params['usuario']);
        $params['id'] = $id;
        $ownsTransaction = !$this->conexion->inTransaction();
        if ($ownsTransaction) {
            $this->conexion->beginTransaction();
        } else {
            $this->conexion->exec('SAVEPOINT actualizar_estudiante');
        }
        try {
            $update = $this->conexion->prepare(
                'UPDATE estudiantes
                 SET nombre_completo = :nombre, nombres = :nombres, apellidos = :apellidos,
                     correo = :correo, carnet = :carnet, telefono = :telefono, carrera = :carrera,
                     anio_academico = :anio, modalidad_participacion = :modalidad_participacion,
                     dui = :dui, fecha_nacimiento = :nacimiento, genero = :genero,
                     direccion = :direccion, estado_registro = :estado
                 WHERE estudiante_id = :id'
            );
            $update->execute($params);

            if ($student['usuario_id'] !== null) {
                $updateAccount = $this->conexion->prepare(
                    'UPDATE usuarios
                     SET nombre_completo = :nombre, correo = :correo, carnet = :carnet,
                         carrera = :carrera, anio_academico = :anio,
                         modalidad_participacion = :modalidad_participacion
                     WHERE usuario_id = :usuario_id'
                );
                $updateAccount->execute([
                    'nombre' => $params['nombre'],
                    'correo' => $params['correo'],
                    'carnet' => $params['carnet'],
                    'carrera' => $params['carrera'],
                    'anio' => $params['anio'],
                    'modalidad_participacion' => $params['modalidad_participacion'],
                    'usuario_id' => (int) $student['usuario_id'],
                ]);
            }

            if ($ownsTransaction) {
                $this->conexion->commit();
            } else {
                $this->conexion->exec('RELEASE SAVEPOINT actualizar_estudiante');
            }
        } catch (Throwable $error) {
            if ($ownsTransaction && $this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            } elseif ($this->conexion->inTransaction()) {
                $this->conexion->exec('ROLLBACK TO SAVEPOINT actualizar_estudiante');
                $this->conexion->exec('RELEASE SAVEPOINT actualizar_estudiante');
            }
            throw $error;
        }
    }
    public function setStatus(int $id,string $estado): void
    {
        if(!in_array($estado,['Activo','Inactivo'],true))throw new InvalidArgumentException('Estado no válido.');
        $q=$this->conexion->prepare('UPDATE estudiantes SET estado_registro=:estado WHERE estudiante_id=:id');$q->execute(['estado'=>$estado,'id'=>$id]);
        if($q->rowCount()===0&&$this->find($id)===null)throw new InvalidArgumentException('El estudiante no existe.');
    }

    public function updateContact(int $id, string $correo, string $telefono, string $direccion): void
    {
        $correo = strtolower(trim($correo));
        if (!Validacion::correoItcaValido($correo)) {
            throw new InvalidArgumentException('Ingresa un correo institucional válido.');
        }
        foreach ([$telefono, $direccion] as $value) {
            if (Validacion::tieneEmoji($value)) {
                throw new InvalidArgumentException('No se permiten emojis en los datos de contacto.');
            }
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare('SELECT usuario_id FROM estudiantes WHERE estudiante_id = :id');
            $consulta->execute(['id' => $id]);
            $usuarioId = $consulta->fetchColumn();
            if ($usuarioId === false) {
                throw new InvalidArgumentException('El estudiante no existe.');
            }
            $actualizar = $this->conexion->prepare(
                'UPDATE estudiantes SET correo = :correo, telefono = :telefono, direccion = :direccion WHERE estudiante_id = :id'
            );
            $actualizar->execute([
                'correo' => $correo,
                'telefono' => $telefono !== '' ? $telefono : null,
                'direccion' => $direccion !== '' ? $direccion : null,
                'id' => $id,
            ]);
            if ($usuarioId !== null) {
                $actualizarCuenta = $this->conexion->prepare('UPDATE usuarios SET correo = :correo WHERE usuario_id = :usuario_id');
                $actualizarCuenta->execute(['correo' => $correo, 'usuario_id' => $usuarioId]);
            }
            $this->conexion->commit();
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
    }

    public function createForUser(int $usuarioId): int
    {
        $q=$this->conexion->prepare('SELECT u.usuario_id,u.nombre_completo,u.correo,u.carnet,u.carrera,u.anio_academico,u.modalidad_participacion
            FROM usuarios u WHERE u.usuario_id=:id');
        $q->execute(['id'=>$usuarioId]); $u=$q->fetch();
        if($u===false) throw new InvalidArgumentException('La cuenta del estudiante no existe.');
        $existing=$this->conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE usuario_id=:id');$existing->execute(['id'=>$usuarioId]);$id=$existing->fetchColumn();
        if($id!==false)return(int)$id;
        $parts=preg_split('/\s+/',trim((string)$u['nombre_completo']),2)?:[$u['nombre_completo'],''];
        return $this->create(['usuario_id'=>(int)$u['usuario_id'],'nombre_completo'=>$u['nombre_completo'],'nombres'=>$parts[0],
            'apellidos'=>$parts[1]??'','carnet'=>$u['carnet']??'','correo'=>$u['correo'],'telefono'=>'',
            'carrera'=>$u['carrera']??'Sin especificar','anio_academico'=>(int)($u['anio_academico']??1),
            'modalidad_participacion'=>$u['modalidad_participacion']??'Horas sociales','dui'=>'','fecha_nacimiento'=>'',
            'genero'=>'','direccion'=>'','estado_registro'=>'Activo']);
    }
    public function find(int $id): ?array
    {
        $q=$this->conexion->prepare('SELECT e.*,COALESCE(e.carnet,u.carnet,"") AS carnet FROM estudiantes e LEFT JOIN usuarios u ON u.usuario_id=e.usuario_id WHERE e.estudiante_id=:id');$q->execute(['id'=>$id]);$r=$q->fetch();return $r===false?null:$r;
    }
    public function all(array $f=[]): array
    {
        $w=[];$p=[];if(($f['q']??'')!==''){$w[]='(e.nombre_completo LIKE :q1 OR e.correo LIKE :q2 OR COALESCE(e.carnet,u.carnet) LIKE :q3 OR e.carrera LIKE :q4 OR CAST(e.estudiante_id AS CHAR) = :q5)';$term='%'.trim((string)$f['q']).'%';$p['q1']=$term;$p['q2']=$term;$p['q3']=$term;$p['q4']=$term;$p['q5']=trim((string)$f['q']);}
        if (($f['estado_registro'] ?? '') === '') {
            $w[] = "e.estado_registro = 'Activo'";
        } elseif (($f['estado_registro'] ?? '') !== 'todos') {
            $w[] = 'e.estado_registro = :estado_registro';
            $p['estado_registro'] = $f['estado_registro'];
        }
        foreach(['carrera','anio_academico','modalidad_participacion'] as $c)if(($f[$c]??'')!==''){$w[]="e.$c=:$c";$p[$c]=$c==='anio_academico'?(int)$f[$c]:$f[$c];}
        $sql='SELECT e.*,COALESCE(e.carnet,u.carnet,"") AS carnet FROM estudiantes e LEFT JOIN usuarios u ON u.usuario_id=e.usuario_id'.($w?' WHERE '.implode(' AND ',$w):'').' ORDER BY e.nombre_completo';$q=$this->conexion->prepare($sql);$q->execute($p);return $q->fetchAll();
    }

    public function careers(): array
    {
        return $this->conexion->query('SELECT DISTINCT carrera FROM estudiantes WHERE carrera <> "" ORDER BY carrera')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function related(int $id): array
    {
        $projects=$this->conexion->prepare('SELECT p.proyecto_id,p.nombre,p.codigo,p.modalidad,p.estado,pe.fecha_asignacion,pe.fecha_desasignacion FROM proyecto_estudiantes pe JOIN proyectos p ON p.proyecto_id=pe.proyecto_id WHERE pe.estudiante_id=:id ORDER BY pe.fecha_asignacion DESC');
        $projects->execute(['id'=>$id]);
        $hours=$this->conexion->prepare('SELECT rh.registro_id,rh.horas,rh.estado,rh.descripcion,rh.fecha_registro,rh.motivo_revision,a.titulo,p.nombre AS proyecto FROM registros_horas rh JOIN actividades_proyecto a ON a.actividad_id=rh.actividad_id JOIN proyectos p ON p.proyecto_id=a.proyecto_id WHERE rh.estudiante_id=:id ORDER BY rh.fecha_registro DESC');
        $hours->execute(['id'=>$id]);
        $evaluations=$this->conexion->prepare('SELECT ev.*,p.nombre AS proyecto,u.nombre_completo AS evaluador FROM evaluaciones_estudiante ev LEFT JOIN proyectos p ON p.proyecto_id=ev.proyecto_id LEFT JOIN usuarios u ON u.usuario_id=ev.evaluador_id WHERE ev.estudiante_id=:id ORDER BY ev.fecha_evaluacion DESC');
        $evaluations->execute(['id'=>$id]);
        $documents=$this->conexion->prepare('SELECT d.*,u.nombre_completo AS subido_por_nombre FROM documentos_estudiante d LEFT JOIN usuarios u ON u.usuario_id=d.subido_por WHERE d.estudiante_id=:id ORDER BY d.fecha_subida DESC');
        $documents->execute(['id'=>$id]);
        return ['proyectos'=>$projects->fetchAll(),'horas'=>$hours->fetchAll(),'evaluaciones'=>$evaluations->fetchAll(),'documentos'=>$documents->fetchAll()];
    }
    private function params(array $d): array{return ['usuario'=>$d['usuario_id']??null,'nombre'=>$d['nombre_completo'],'nombres'=>$d['nombres'],'apellidos'=>$d['apellidos'],'correo'=>$d['correo'],'carnet'=>$d['carnet']?:null,'telefono'=>$d['telefono']?:null,'carrera'=>$d['carrera'],'anio'=>$d['anio_academico']??1,'modalidad_participacion'=>$d['modalidad_participacion']??'Horas sociales','dui'=>$d['dui']?:null,'nacimiento'=>$d['fecha_nacimiento']?:null,'genero'=>$d['genero']?:null,'direccion'=>$d['direccion']?:null,'estado'=>$d['estado_registro']??'Activo'];}
    private function validateData(array $d): void
    {
        foreach(['nombre_completo','nombres','apellidos','correo','carrera'] as $field) if(trim((string)($d[$field]??''))==='') throw new InvalidArgumentException('Falta el campo obligatorio: '.$field.'.');
        if(!Validacion::correoItcaValido((string)$d['correo'])) throw new InvalidArgumentException('El correo institucional no es válido.');
        if(($d['carnet']??'')!==''&&!preg_match('/^[A-Za-z0-9-]{3,32}$/',(string)$d['carnet'])) throw new InvalidArgumentException('El carnet no es válido.');
        if(!in_array((int)($d['anio_academico']??0),[1,2],true)) throw new InvalidArgumentException('El año académico no es válido.');
        if(!in_array($d['modalidad_participacion']??'Horas sociales',['Voluntariado','Horas sociales','Práctica profesional'],true)) throw new InvalidArgumentException('La modalidad de participación no es válida.');
        if (($d['fecha_nacimiento'] ?? '') !== '' && !Validacion::fechaValida((string) $d['fecha_nacimiento'])) {
            throw new InvalidArgumentException('La fecha de nacimiento no es válida.');
        }
        foreach($d as $value) if(is_string($value)&&Validacion::tieneEmoji($value)) throw new InvalidArgumentException('No se permiten emojis en los datos del estudiante.');
    }
}
