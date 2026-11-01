<?php
declare(strict_types=1);

final class EstudianteController
{
    public function __construct(private PDO $conexion, private HttpController $http) {}

    public function save(): never
    {
        $this->http->requirePost();$usuario=$this->http->requirePermission('estudiantes.crear');$this->http->verifyCsrfToken();$data=$this->data();$errors=$this->validate($data);
        if($errors!==[]){$this->http->rememberErrors($errors,'estudiante_nuevo',$data);$this->http->redirect('/estudiantes_nuevo.php');}
        try{$id=(new Estudiante($this->conexion))->create($data);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'estudiante',$id,'CREAR',null,$data);}
        catch(PDOException $error){$this->duplicate($error,'estudiante_nuevo');}
        $this->http->rememberNotice('El expediente se registró correctamente. Identificador: '.$id.'.');
        $this->http->redirect('/estudiantes_listado.php');
    }

    public function update(): never
    {
        $this->http->requirePost();$usuario=$this->http->requireAuthentication();$this->http->verifyCsrfToken();$id=$this->id();$model=new Estudiante($this->conexion);$old=$model->find($id);
        if($old===null){$this->http->rememberErrors(['acceso'=>'El estudiante no existe.']);$this->http->redirect('/estudiantes_listado.php');}
        if (!$this->http->userCan('estudiantes.actualizar')) {
            if (!$this->http->userCan('estudiantes.actualizar_propios') || !$this->http->userOwnsStudent($id, (int) $usuario['usuario_id'])) {
                http_response_code(403);
                exit('No tienes permisos para modificar este expediente.');
            }
            $contacto = [
                'correo' => strtolower(trim((string) ($_POST['correo'] ?? ''))),
                'telefono' => trim((string) ($_POST['telefono'] ?? '')),
                'direccion' => trim((string) ($_POST['direccion'] ?? '')),
            ];
            $errors = [];
            if (!Validacion::correoItcaValido($contacto['correo'])) {
                $errors['correo'] = 'Ingresa un correo institucional válido.';
            }
            foreach (['telefono', 'direccion'] as $campo) {
                if (Validacion::tieneEmoji($contacto[$campo])) {
                    $errors[$campo] = 'No se permiten emojis en este campo.';
                }
            }
            if ($errors !== []) {
                $this->http->rememberErrors($errors, 'estudiante_editar', $contacto);
                $this->http->redirect('/estudiantes_editar.php?id='.$id);
            }
            try {
                $model->updateContact($id, $contacto['correo'], $contacto['telefono'], $contacto['direccion']);
            } catch (PDOException $error) {
                if ($error->getCode() !== '23000') {
                    throw $error;
                }
                $this->http->rememberErrors(['correo' => 'Este correo ya está registrado.'], 'estudiante_editar', $contacto);
                $this->http->redirect('/estudiantes_editar.php?id='.$id);
            }
            (new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'estudiante',$id,'ACTUALIZAR',$old,$model->find($id)??$contacto);
            $this->http->rememberNotice('Tus datos de contacto se actualizaron correctamente.');
            $this->http->redirect('/dashboard.php');
        }
        $data=$this->data();$errors=$this->validate($data);if($errors!==[]){$this->http->rememberErrors($errors,'estudiante_editar',$data);$this->http->redirect('/estudiantes_editar.php?id='.$id);}
        try{$model->update($id,$data);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'estudiante',$id,'ACTUALIZAR',$old,$model->find($id)??$data);}
        catch(PDOException $error){$this->duplicate($error,'estudiante_editar',$id);}
        $this->http->rememberNotice('Los cambios del expediente se guardaron correctamente.');
        $this->http->redirect('/estudiantes_detalle.php?id='.$id);
    }

    public function toggle(): never
    {
        $this->http->requirePost();$usuario=$this->http->requirePermission('estudiantes.actualizar');$this->http->verifyCsrfToken();$id=$this->id();$model=new Estudiante($this->conexion);$old=$model->find($id);$estado=(string)($_POST['estado']??'');
        if($old===null||!in_array($estado,['Activo','Inactivo'],true)){$this->http->rememberErrors(['acceso'=>'Datos de estado no válidos.']);$this->http->redirect('/estudiantes_listado.php');}
        $model->setStatus($id,$estado);(new Auditoria($this->conexion))->registrar((int)$usuario['usuario_id'],'estudiante',$id,'CAMBIAR_ESTADO',$old,$model->find($id)??[]);$this->http->rememberNotice('El estado del expediente se actualizó.');$this->http->redirect('/estudiantes_detalle.php?id='.$id);
    }

    private function data():array{$n=trim((string)($_POST['nombres']??''));$a=trim((string)($_POST['apellidos']??''));return['nombres'=>$n,'apellidos'=>$a,'nombre_completo'=>trim($n.' '.$a),'carnet'=>trim((string)($_POST['carnet']??'')),'dui'=>trim((string)($_POST['dui']??'')),'fecha_nacimiento'=>trim((string)($_POST['fecha_nacimiento']??'')),'genero'=>trim((string)($_POST['genero']??'')),'correo'=>strtolower(trim((string)($_POST['correo']??''))),'telefono'=>trim((string)($_POST['telefono']??'')),'carrera'=>trim((string)($_POST['carrera']??'')),'direccion'=>trim((string)($_POST['direccion']??'')),'estado_registro'=>(string)($_POST['estado']??'Activo'),'anio_academico'=>(int)($_POST['anio_academico']??0),'modalidad_participacion'=>(string)($_POST['modalidad_participacion']??'')];}
    private function validate(array $d):array{$e=[];foreach(['nombres','apellidos','carnet','correo','carrera']as$c)if($d[$c]==='')$e[$c]='Este campo es obligatorio.';if(!in_array($d['anio_academico'],[1,2],true))$e['anio_academico']='Selecciona un año académico válido.';if(!in_array($d['modalidad_participacion'],['Voluntariado','Horas sociales','Práctica profesional'],true))$e['modalidad_participacion']='Selecciona una modalidad de participación.';if($d['correo']!==''&&!Validacion::correoItcaValido($d['correo']))$e['correo']='Usa un correo institucional válido.';if($d['carnet']!==''&&!preg_match('/^[A-Za-z0-9-]{3,32}$/',$d['carnet']))$e['carnet']='El carnet no tiene un formato válido.';if($d['fecha_nacimiento']!==''&&!Validacion::fechaValida($d['fecha_nacimiento']))$e['fecha_nacimiento']='La fecha no es válida.';if(!in_array($d['estado_registro'],['Activo','Inactivo'],true))$e['estado']='Estado no válido.';foreach($d as $k=>$v)if(is_string($v)&&Validacion::tieneEmoji($v))$e[$k]='No se permiten emojis en este campo.';return$e;}
    private function id():int{$id=filter_var($_POST['estudiante_id']??$_GET['id']??null,FILTER_VALIDATE_INT);if(!$id||$id<1){$this->http->rememberErrors(['acceso'=>'El estudiante seleccionado no es válido.']);$this->http->redirect('/estudiantes_listado.php');}return(int)$id;}
    private function duplicate(PDOException $error,string $form,int $id=0):never{if($error->getCode()!=='23000')throw$error;$this->http->rememberErrors(['correo'=>'El correo o carnet ya está registrado.'],$form);$this->http->redirect($form==='estudiante_editar'?'/estudiantes_editar.php?id='.$id:'/estudiantes_nuevo.php');}
}
