<?php
declare(strict_types=1);

final class HorasController
{
    // Este bloque recibe dependencias compartidas para registrar y revisar horas.
    public function __construct(private PDO $conexion, private HttpController $http) {}

    // Este bloque valida y registra horas en nombre del usuario o de otro estudiante autorizado.
    public function save(): never
    {
        $this->http->requirePost();
        $usuario = $this->http->requireAuthentication();
        $this->http->verifyCsrfToken();
        $modelo = new RegistroHoras($this->conexion);
        $puedeReportarParaEstudiantes = $this->http->userCan('horas.registrar_para_estudiantes');
        if (!$puedeReportarParaEstudiantes && !$this->http->userCan('horas.registrar')) {
            http_response_code(403);
            exit('No tienes permisos para registrar horas.');
        }
        $estudianteId = $puedeReportarParaEstudiantes
            ? filter_var($_POST['estudiante_id'] ?? null, FILTER_VALIDATE_INT)
            : $modelo->studentIdForUser((int) $usuario['usuario_id']);
        if ($estudianteId === false || $estudianteId === null || $estudianteId < 1) {
            $estudianteId = null;
        }
        $actividadId = filter_var($_POST['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        $horas = $_POST['horas'] ?? null;
        $fecha = trim((string) ($_POST['fecha_actividad'] ?? ''));
        $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
        $errors = [];
        if ($estudianteId === null) {
            $errors['acceso'] = 'No existe un expediente activo asociado a tu cuenta.';
        }
        if ($actividadId === false || $actividadId === null || $actividadId < 1) {
            $errors['actividad_id'] = 'Selecciona una actividad válida.';
        }
        if (!Validacion::horasRegistroValida($horas)) {
            $errors['horas'] = 'Las horas deben tener hasta dos decimales, ser mayores que cero y no superar 8 por día.';
        }
        $fechaValida = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        if ($fechaValida === false || $fechaValida->format('Y-m-d') !== $fecha) {
            $errors['fecha_actividad'] = 'Selecciona una fecha válida.';
        }
        if ($descripcion === '' || mb_strlen($descripcion) > 1000) {
            $errors['descripcion'] = 'La descripción es obligatoria y no debe exceder 1,000 caracteres.';
        }
        if ($errors === [] && $estudianteId !== null && $modelo->hasPotentialDuplicate((int) $actividadId, $estudianteId, $fecha)
            && ($_POST['confirmar_duplicado'] ?? '') !== '1') {
            $errors['confirmar_duplicado'] = 'Ya existe un registro para esta actividad y fecha. Confirma que deseas enviar otro.';
        }
        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'horas_registrar', $_POST);
            $redirect = '/horas_registrar.php';
            if ($puedeReportarParaEstudiantes && $estudianteId !== null) {
                $redirect .= '?estudiante_id=' . $estudianteId . '&q=' . rawurlencode(trim((string) ($_POST['q'] ?? '')));
            }
            $this->http->redirect($redirect);
        }
        try {
            $id = $modelo->create((int) $actividadId, (int) $estudianteId, $horas, $descripcion, $fecha, (int) $usuario['usuario_id']);
            (new Auditoria($this->conexion))->registrar((int) $usuario['usuario_id'], 'registro_horas', $id, 'CREAR', null, [
                'actividad_id' => $actividadId,
                'fecha_actividad' => $fecha,
                'horas' => (float) $horas,
                'reportado_por' => (int) $usuario['usuario_id'],
                'estado' => 'Pendiente',
            ]);
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'horas_registrar', $_POST);
            $redirect = '/horas_registrar.php';
            if ($puedeReportarParaEstudiantes && $estudianteId !== null) {
                $redirect .= '?estudiante_id=' . $estudianteId . '&q=' . rawurlencode(trim((string) ($_POST['q'] ?? '')));
            }
            $this->http->redirect($redirect);
        }
        $this->http->rememberNotice('El registro de horas se envió para validación.');
        $this->http->redirect($puedeReportarParaEstudiantes ? '/horas_bandeja.php' : '/estudiantes_resumen_horas.php');
    }

    // Este bloque valida la decisión del revisor y actualiza el estado de un registro pendiente.
    public function review(): never
    {
        $this->http->requirePost();$u=$this->http->requirePermission('horas.revisar');$this->http->verifyCsrfToken();$id=filter_var($_POST['registro_id']??null,FILTER_VALIDATE_INT);
        $decision=(string)($_POST['decision']??'');$motivo=trim((string)($_POST['motivo']??''));$errors=[];
        if(!$id)$errors['acceso']='El registro seleccionado no es válido.';
        if(!in_array($decision,['Aprobado','Rechazado'],true))$errors['acceso']='La decisión no es válida.';
        if($decision==='Rechazado'&&$motivo==='')$errors['acceso']='Debes indicar el motivo del rechazo.';
        if($errors!==[]){$this->http->rememberErrors($errors,'horas_bandeja',$_POST);$this->http->redirect('/horas_bandeja.php');}
        try {
            $ok = (new Proyecto($this->conexion))->reviewHours((int) $id, (int) $u['usuario_id'], $decision, $motivo);
        } catch (InvalidArgumentException $error) {
            $this->http->rememberErrors(['acceso' => $error->getMessage()], 'horas_bandeja', $_POST);
            $this->http->redirect('/horas_bandeja.php');
        }
        if (!$ok) {
            $this->http->rememberErrors(['acceso' => 'El registro ya fue revisado o no existe.'], 'horas_bandeja');
        } else {
            $this->http->rememberNotice('El registro de horas se actualizó a ' . strtolower($decision) . '.');
        }
        $this->http->redirect('/horas_bandeja.php');
    }
}
