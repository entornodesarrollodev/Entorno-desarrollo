<?php
declare(strict_types=1);

final class ProyectoController
{
    // Este bloque recibe la conexión y los controles HTTP para operaciones de proyectos.
    public function __construct(private PDO $conexion, private HttpController $http) {}

    // Este bloque crea o actualiza una actividad y envía notificaciones internas si corresponde.
    public function activity(): never
    {
        $this->http->requirePost();
        $this->http->verifyCsrfToken();
        $activityInput = $_POST['actividad_id'] ?? null;
        $activityId = $activityInput === null ? 0 : filter_var($activityInput, FILTER_VALIDATE_INT);
        if ($activityId === false || $activityInput !== null && $activityId < 1) {
            $this->http->requirePermission('proyectos.crear');
            $this->http->rememberErrors(['acceso' => 'La actividad seleccionada no es válida.'], 'actividad', $_POST);
            $this->http->redirect('/cronograma_nuevo.php');
        }
        $activityId = (int) $activityId;
        $editing = $activityId > 0;
        $usuario = $this->http->requirePermission($editing ? 'proyectos.actualizar' : 'proyectos.crear');
        $model = new Proyecto($this->conexion);
        $old = $editing ? $model->findActivity($activityId) : null;
        if ($editing && $old === null) {
            $this->http->rememberErrors(['acceso' => 'La actividad seleccionada no existe.']);
            $this->http->redirect('/proyectos.php');
        }

        $data = [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'modalidad' => (string) ($_POST['modalidad'] ?? ''),
            'proyecto_id' => (int) ($_POST['proyecto_id'] ?? 0),
            'fecha_inicio' => trim((string) ($_POST['fecha_inicio'] ?? '')),
            'fecha_fin' => trim((string) ($_POST['fecha_fin'] ?? '')),
            'fecha_limite_confirmacion' => trim((string) ($_POST['fecha_limite_confirmacion'] ?? '')),
            'lugar' => trim((string) ($_POST['lugar'] ?? '')),
            'recurso' => trim((string) ($_POST['recurso'] ?? '')),
            'responsable_id' => (int) ($_POST['responsable_id'] ?? $usuario['usuario_id']),
            'horas_estimadas' => $_POST['horas_estimadas'] ?? '',
            'cupo' => trim((string) ($_POST['cupo'] ?? '')),
        ];
        $errores = [];
        foreach (['titulo', 'descripcion', 'modalidad', 'proyecto_id', 'fecha_inicio', 'fecha_fin', 'lugar', 'responsable_id', 'horas_estimadas'] as $campo) {
            if ((string) $data[$campo] === '' || (int) $data[$campo] === 0 && in_array($campo, ['proyecto_id', 'responsable_id'], true)) {
                $errores[$campo] = 'Este campo es obligatorio.';
            }
        }
        if (!in_array($data['modalidad'], ['Presencial', 'Virtual', 'Mixta'], true)) {
            $errores['modalidad'] = 'Selecciona una modalidad válida.';
        }
        $inicioValido = Validacion::fechaHoraLocalValida($data['fecha_inicio']);
        $finValido = Validacion::fechaHoraLocalValida($data['fecha_fin']);
        if (!$inicioValido) {
            $errores['fecha_inicio'] = 'La fecha y hora de inicio no es válida.';
        }
        if (!$finValido) {
            $errores['fecha_fin'] = 'La fecha y hora final no es válida.';
        } elseif ($inicioValido
            && Validacion::normalizarFechaHoraLocal($data['fecha_fin']) <= Validacion::normalizarFechaHoraLocal($data['fecha_inicio'])) {
            $errores['fecha_fin'] = 'La fecha y hora final debe ser posterior al inicio.';
        }
        if ($data['horas_estimadas'] !== '' && (!is_numeric($data['horas_estimadas']) || (float) $data['horas_estimadas'] <= 0 || (float) $data['horas_estimadas'] > 24)) {
            $errores['horas_estimadas'] = 'Indica horas numéricas entre 0 y 24.';
        }
        if ($data['cupo'] !== '' && (!ctype_digit($data['cupo']) || (int) $data['cupo'] <= 0)) {
            $errores['cupo'] = 'El cupo debe ser un entero mayor que cero.';
        }
        if ($errores !== []) {
            $this->http->rememberErrors($errores, 'actividad', $data);
            $this->http->redirect('/cronograma_nuevo.php?proyecto_id=' . (int) $data['proyecto_id'] . ($activityId > 0 ? '&id=' . $activityId : ''));
        }

        $notificationCount = null;
        $notificationFailure = false;
        try {
            if ($editing) {
                $model->updateActivity($activityId, $data, (int) $usuario['usuario_id']);
                (new Auditoria($this->conexion))->registrar(
                    (int) $usuario['usuario_id'],
                    'actividad',
                    $activityId,
                    'ACTUALIZAR',
                    $old,
                    $model->findActivity($activityId)
                );
            } else {
                $activityId = $model->addActivity($data, (int) $usuario['usuario_id']);
                (new Auditoria($this->conexion))->registrar((int) $usuario['usuario_id'], 'actividad', $activityId, 'CREAR', null, $model->findActivity($activityId));
                try {
                    $notificationCount = (new PortalSocial($this->conexion))->notifyAssignedStudents($activityId);
                } catch (RuntimeException $error) {
                    $notificationFailure = true;
                }
            }
        } catch (DomainException|InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'actividad', $data);
            $this->http->redirect('/cronograma_nuevo.php?proyecto_id=' . (int) $data['proyecto_id'] . ($editing ? '&id=' . $activityId : ''));
        }
        if ($notificationFailure) {
            $this->http->rememberNotice('La actividad se guardó, pero falló el envío de notificaciones. Revisa el registro de envíos.', 'error');
        } elseif (!$editing && $notificationCount === 0) {
            $this->http->rememberNotice('La actividad se guardó, pero no se generaron avisos. Revisa que exista una plantilla activa y participantes asignados.', 'error');
        } else {
            $message = $editing
                ? 'Los cambios de la actividad se guardaron.'
                : 'La actividad se guardó y se notificó a ' . (int) $notificationCount . ' estudiante(s).';
            $this->http->rememberNotice($message);
        }
        $this->http->redirect('/proyectos.php?id=' . (int) $data['proyecto_id']);
    }

    // Este bloque valida y crea un proyecto nuevo con su registro de auditoría.
    public function create(): never
    {
        $this->http->requirePost();$usuario=$this->http->requirePermission('proyectos.crear');$this->http->verifyCsrfToken();$data=$this->projectData();$errors=$this->projectErrors($data);
        if($errors!==[]){$this->http->rememberErrors($errors,'proyecto',$_POST);$this->http->redirect('/proyectos.php');}
        try{$id=(new Proyecto($this->conexion))->create($data,(int)$usuario['usuario_id']);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'proyecto',$id,'CREAR',null,$data);}
        catch(PDOException $error){if($error->getCode()!=='23000')throw$error;$this->http->rememberErrors(['codigo'=>'El código del proyecto ya existe.'],'proyecto',$_POST);$this->http->redirect('/proyectos.php');}
        $this->http->rememberNotice('El proyecto se creó correctamente.');
        $this->http->redirect('/proyectos.php');
    }

    // Este bloque guarda cambios de proyecto después de validar permiso e identificador.
    public function update(): never
    {
        $this->http->requirePost();$usuario=$this->http->requirePermission('proyectos.actualizar');$this->http->verifyCsrfToken();$id=$this->id();$model=new Proyecto($this->conexion);$old=$model->find($id);
        if($old===null){$this->http->rememberErrors(['acceso'=>'El proyecto no existe.'],'proyecto');$this->http->redirect('/proyectos.php');}
        $data=$this->projectData();$errors=$this->projectErrors($data);if($errors!==[]){$this->http->rememberErrors($errors,'proyecto',$_POST);$this->http->redirect('/proyectos.php?id='.$id);}
        try{$model->update($id,$data,(int)$usuario['usuario_id']);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'proyecto',$id,'ACTUALIZAR',$old,$model->find($id)??$data);}
        catch(PDOException $error){if($error->getCode()!=='23000')throw$error;$this->http->rememberErrors(['codigo'=>'El código del proyecto ya existe.'],'proyecto',$_POST);$this->http->redirect('/proyectos.php?id='.$id);}
        $this->http->rememberNotice('Los cambios del proyecto se guardaron.');
        $this->http->redirect('/proyectos.php?id='.$id);
    }

    // Este bloque cambia el estado del proyecto y conserva el cambio en auditoría.
    public function toggle(): never
    {
        $this->http->requirePost();$usuario=$this->http->requirePermission('proyectos.actualizar');$this->http->verifyCsrfToken();$id=$this->id();$model=new Proyecto($this->conexion);$old=$model->find($id);
        if($old===null){$this->http->rememberErrors(['acceso'=>'El proyecto no existe.']);$this->http->redirect('/proyectos.php');}
        $data=$old;$data['estado']=$old['estado']==='Activo'?'Finalizado':'Activo';$model->update($id,$data,(int)$usuario['usuario_id']);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'proyecto',$id,'CAMBIAR_ESTADO',$old,$model->find($id)??$data);$this->http->rememberNotice('El estado del proyecto se actualizó.');$this->http->redirect('/proyectos.php?id='.$id);
    }

    // Este bloque vincula un estudiante autorizado con un proyecto.
    public function assign(): never
    {
        $this->http->requirePost();
        $usuario = $this->http->requirePermission('proyectos.asignar');
        $this->http->verifyCsrfToken();
        $projectId = filter_var($_POST['proyecto_id'] ?? null, FILTER_VALIDATE_INT);
        $studentIds = $_POST['estudiante_ids'] ?? [];
        $function = trim((string) ($_POST['funcion'] ?? 'Participante'));
        if ($projectId === false || $projectId === null || $projectId < 1 || !is_array($studentIds) || $studentIds === []) {
            $this->http->rememberErrors(['acceso' => 'Selecciona un proyecto y al menos un estudiante activo.'], 'asignacion', $_POST);
            $this->http->redirect('/proyectos_asignar.php');
        }
        $ids = [];
        foreach ($studentIds as $studentId) {
            $validId = filter_var($studentId, FILTER_VALIDATE_INT);
            if ($validId === false || $validId < 1) {
                $this->http->rememberErrors(['acceso' => 'La selección de estudiantes no es válida.'], 'asignacion', $_POST);
                $this->http->redirect('/proyectos_asignar.php?proyecto_id=' . (int) $projectId);
            }
            $ids[(int) $validId] = (int) $validId;
        }
        $model = new Proyecto($this->conexion);
        $this->conexion->beginTransaction();
        try {
            foreach ($ids as $studentId) {
                $assignmentId = $model->assign((int) $projectId, $studentId, (int) $usuario['usuario_id'], $function);
                (new Auditoria($this->conexion))->registrar((int) $usuario['usuario_id'], 'proyecto_estudiante', $assignmentId, 'ASIGNAR', null, [
                    'proyecto_id' => (int) $projectId,
                    'estudiante_id' => $studentId,
                    'funcion' => $function,
                ]);
            }
            $this->conexion->commit();
        } catch (DomainException|InvalidArgumentException $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'asignacion', $_POST);
            $this->http->redirect('/proyectos_asignar.php?proyecto_id=' . (int) $projectId);
        } catch (Throwable $error) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $error;
        }
        $this->http->rememberNotice(count($ids) . ' estudiante(s) asignado(s) al proyecto.');
        $this->http->redirect('/proyectos.php?id=' . (int) $projectId);
    }

    // Este bloque retira una asignación conservando su historial.
    public function unassign(): never
    {
        $this->http->requirePost();
        $usuario = $this->http->requirePermission('proyectos.asignar');
        $this->http->verifyCsrfToken();
        $assignmentId = filter_var($_POST['asignacion_id'] ?? null, FILTER_VALIDATE_INT);
        if ($assignmentId === false || $assignmentId === null || $assignmentId < 1) {
            $this->http->rememberErrors(['acceso' => 'La asignación seleccionada no es válida.']);
            $this->http->redirect('/proyectos.php');
        }
        $query = $this->conexion->prepare('SELECT proyecto_id, estudiante_id, estado_participacion FROM proyecto_estudiantes WHERE asignacion_id = :id');
        $query->execute(['id' => $assignmentId]);
        $old = $query->fetch();
        if ($old === false) {
            $this->http->rememberErrors(['acceso' => 'No se encontró la asignación seleccionada.']);
            $this->http->redirect('/proyectos.php');
        }
        if (!(new Proyecto($this->conexion))->removeAssignment((int) $assignmentId)) {
            $this->http->rememberErrors(['acceso' => 'La asignación ya fue retirada.']);
            $this->http->redirect('/proyectos.php?id=' . (int) $old['proyecto_id']);
        }
        (new Auditoria($this->conexion))->registrar((int) $usuario['usuario_id'], 'proyecto_estudiante', (int) $assignmentId, 'ASIGNAR', $old, [
            'proyecto_id' => (int) $old['proyecto_id'],
            'estudiante_id' => (int) $old['estudiante_id'],
            'estado_participacion' => 'Retirada',
        ]);
        $this->http->rememberNotice('La asignación se retiró y su historial se conservó.');
        $this->http->redirect('/proyectos.php?id=' . (int) $old['proyecto_id']);
    }

    // Este bloque reúne y normaliza los campos del formulario de proyecto.
    private function projectData(): array
    {
        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'codigo' => trim((string) ($_POST['codigo'] ?? '')),
            'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
            'objetivo' => trim((string) ($_POST['objetivo'] ?? '')),
            'requisitos' => trim((string) ($_POST['requisitos'] ?? '')),
            'carrera' => trim((string) ($_POST['carrera'] ?? '')),
            'anio_academico' => (int) ($_POST['anio_academico'] ?? 0),
            'modalidad' => (string) ($_POST['modalidad'] ?? ''),
            'fecha_inicio' => (string) ($_POST['fecha_inicio'] ?? ''),
            'fecha_fin' => (string) ($_POST['fecha_fin'] ?? ''),
            'estado' => (string) ($_POST['estado'] ?? 'Activo'),
            'responsable_id' => (int) ($_POST['responsable_id'] ?? 0),
        ];
    }

    // Este bloque valida los campos y las fechas del proyecto antes de guardarlo.
    private function projectErrors(array $data): array
    {
        $errors = [];
        foreach (['nombre', 'codigo', 'descripcion', 'objetivo', 'carrera', 'modalidad', 'fecha_inicio'] as $field) {
            if ($data[$field] === '') {
                $errors[$field] = 'Este campo es obligatorio.';
            }
        }
        if (!in_array($data['modalidad'], ['Presencial', 'Virtual', 'Mixta'], true)) {
            $errors['modalidad'] = 'Modalidad no válida.';
        }
        if (!in_array($data['anio_academico'], [1, 2], true)) {
            $errors['anio_academico'] = 'Año académico no válido.';
        }
        if ($data['responsable_id'] < 1) {
            $errors['responsable_id'] = 'Selecciona a la persona responsable.';
        }
        if (!in_array($data['estado'], ['Activo', 'Inactivo', 'Finalizado', 'En Pausa'], true)) {
            $errors['estado'] = 'Estado no válido.';
        }

        $startValid = Validacion::fechaValida($data['fecha_inicio']);
        $endValid = $data['fecha_fin'] === '' || Validacion::fechaValida($data['fecha_fin']);
        if (!$startValid) {
            $errors['fecha_inicio'] = 'La fecha inicial no es válida.';
        }
        if (!$endValid) {
            $errors['fecha_fin'] = 'La fecha final no es válida.';
        } elseif ($startValid && $data['fecha_fin'] !== '' && $data['fecha_fin'] < $data['fecha_inicio']) {
            $errors['fecha_fin'] = 'La fecha final no puede ser anterior a la inicial.';
        }

        return $errors;
    }
    // Este bloque recupera un identificador de proyecto válido desde la petición.
    private function id():int{$id=filter_var($_POST['proyecto_id']??$_GET['id']??null,FILTER_VALIDATE_INT);if(!$id){$this->http->rememberErrors(['acceso'=>'El proyecto seleccionado no es válido.']);$this->http->redirect('/proyectos.php');}return(int)$id;}
}
