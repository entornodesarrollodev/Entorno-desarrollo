<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Rol.php';
require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/models/Estudiante.php';
require_once dirname(__DIR__) . '/models/SolicitudRegistro.php';
require_once dirname(__DIR__) . '/models/Validacion.php';
require_once dirname(__DIR__) . '/models/Proyecto.php';
require_once dirname(__DIR__) . '/models/RegistroHoras.php';
require_once dirname(__DIR__) . '/models/Auditoria.php';
require_once dirname(__DIR__) . '/controllers/HttpController.php';

function comprobar(bool $condicion, string $mensaje): void
{
    if (!$condicion) {
        throw new RuntimeException('FALLO: ' . $mensaje);
    }
}

comprobar(Validacion::correoItcaValido('alumno@itca.edu.sv'), 'se debe aceptar correo del dominio ITCA.');
comprobar(Validacion::correoItcaValido('ALUMNO@ITCA.EDU.SV'), 'el dominio debe aceptar mayusculas.');
comprobar(!Validacion::correoItcaValido('alumno@gmail.com'), 'se debe rechazar correo externo.');
comprobar(Validacion::tieneEmoji('Nombre 😀'), 'se deben detectar emojis en nombres.');
comprobar(Validacion::tieneEmoji('Bandera 🇸🇻'), 'se deben detectar emojis de bandera.');
comprobar(!Validacion::tieneEmoji('Nombre sin emoji'), 'el texto normal debe pasar la revision.');
comprobar(Validacion::fechaValida('2028-02-29'), 'una fecha válida de año bisiesto debe aceptarse.');
comprobar(!Validacion::fechaValida('2027-02-29'), 'una fecha inválida no debe normalizarse.');
comprobar(Validacion::fechaHoraLocalValida('2028-02-29T10:30'), 'se debe aceptar una fecha y hora local válida.');
comprobar(!Validacion::fechaHoraLocalValida('2028-02-30T10:30'), 'se debe rechazar una fecha y hora imposible.');
// Usamos correos al azar para no pisar cuentas que ya existan en tu base.
$sufijo = bin2hex(random_bytes(6));
$correoAdmin = 'admin-' . $sufijo . '@itca.edu.sv';
$correoEstudiante = 'student-' . $sufijo . '@itca.edu.sv';
$correoDocente = 'teacher-' . $sufijo . '@itca.edu.sv';
$correoAsistente = 'assistant-' . $sufijo . '@itca.edu.sv';
$correoCoordinador = 'coordinador-' . $sufijo . '@itca.edu.sv';
$correoRechazado = 'rejected-' . $sufijo . '@itca.edu.sv';
$correoOtro = 'other-' . $sufijo . '@itca.edu.sv';
$carnetEstudiante = 'S' . strtoupper(substr($sufijo, 0, 10));
$conexion = null;

try {
    $conexion = conectarBaseDatos();
    $usuarios = new Usuario($conexion);
    $roles = new Rol($conexion);
    $adminId = $usuarios->create(
        'Administrador de prueba',
        $correoAdmin,
        null,
        'clave123',
        'administrador',
        true
    );
    $admin = $usuarios->authenticate($correoAdmin, 'clave123');
    comprobar($admin !== null, 'una cuenta valida debe permitir iniciar sesion.');
    comprobar($usuarios->authenticate($correoAdmin, 'incorrecta') === null, 'una clave incorrecta debe rechazarse.');
    comprobar($usuarios->authenticate('', '') === null, 'campos vacios no deben iniciar sesion.');
    comprobar($roles->userHasPermission($adminId, 'usuarios.gestionar_roles'), 'el admin debe gestionar roles.');
    $coordinadorId = $usuarios->create(
        'Coordinador de prueba',
        $correoCoordinador,
        null,
        'clave123',
        'coordinador',
        true
    );
    comprobar(
        $roles->userHasPermission($coordinadorId, 'panel.administracion')
            && $roles->userHasPermission($coordinadorId, 'solicitudes.revisar')
            && $roles->userHasPermission($coordinadorId, 'horas.registrar_para_estudiantes'),
        'el coordinador debe ver el panel, revisar solicitudes y registrar horas de estudiantes.'
    );

    $estudiantes = new Estudiante($conexion);
    $solicitudes = new SolicitudRegistro($conexion);
    $estudianteId = $usuarios->create(
        'Estudiante de prueba',
        $correoEstudiante,
        $carnetEstudiante,
        'clave123',
        'estudiante',
        false,
        'software',
        true
    );
    comprobar(
        $usuarios->authenticate($carnetEstudiante, 'clave123') === null,
        'una cuenta pendiente no debe entrar antes de ser aprobada.'
    );
    $pendientes = $solicitudes->pendientes();
    comprobar(
        count(array_filter($pendientes, static fn (array $solicitud): bool =>
            (int) $solicitud['usuario_id'] === $estudianteId
        )) === 1,
        'el registro debe aparecer en las solicitudes pendientes.'
    );
    $solicitudesEstudiante = array_values(array_filter(
        $pendientes,
        static fn (array $solicitud): bool => (int) $solicitud['usuario_id'] === $estudianteId
    ));
    comprobar(
        $solicitudes->resolver((int) $solicitudesEstudiante[0]['solicitud_id'], $adminId, 'aceptada'),
        'el admin debe poder aceptar la solicitud.'
    );
    comprobar(
        $usuarios->authenticate($carnetEstudiante, 'clave123') !== null,
        'el alumno debe poder entrar despues de ser aprobado.'
    );
    comprobar(
        !$roles->userHasPermission($estudianteId, 'usuarios.gestionar_roles'),
        'un estudiante no debe gestionar roles.'
    );
    comprobar(
        !$roles->userHasPermission($estudianteId, 'panel.administracion'),
        'un estudiante no debe abrir el panel admin.'
    );
    comprobar(
        $roles->userHasPermission($estudianteId, 'estudiantes.actualizar_propios')
            && !$roles->userHasPermission($estudianteId, 'horas.registrar_para_estudiantes'),
        'el estudiante puede editar su contacto pero no reportar horas para otros.'
    );
    $consultaEstudiante = $conexion->prepare(
        'SELECT usuario_id FROM estudiantes WHERE correo = :correo'
    );
    $consultaEstudiante->execute(['correo' => $correoEstudiante]);
    comprobar(
        (int) $consultaEstudiante->fetchColumn() === $estudianteId,
        'la ficha debe quedar ligada a su cuenta.'
    );
    try {
        $estudiantes->create('Estudiante duplicado', $correoEstudiante, '0000-0000', 'software', 1);
        comprobar(false, 'no se debe informar éxito cuando la ficha del estudiante ya existe.');
    } catch (PDOException $error) {
        comprobar($error->getCode() === '23000', 'la ficha duplicada debe rechazarse por integridad.');
    }
    $consultaFicha = $conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE correo = :correo');
    $consultaFicha->execute(['correo' => $correoEstudiante]);
    $datosFechaInvalida = $estudiantes->find((int) $consultaFicha->fetchColumn());
    $datosFechaInvalida['fecha_nacimiento'] = '2028-02-30';
    try {
        $estudiantes->create($datosFechaInvalida);
        comprobar(false, 'el registro debe rechazar fechas de nacimiento imposibles.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'fecha'), 'la validación debe identificar el campo de fecha.');
    }

    $docenteId = $usuarios->create(
        'Docente de prueba',
        $correoDocente,
        null,
        'clave123',
        'docente',
        true
    );
    comprobar(
        !$roles->userHasPermission($docenteId, 'usuarios.consultar'),
        'un docente no debe entrar a gestionar usuarios.'
    );
    $asistenteId = $usuarios->create(
        'Asistente de prueba',
        $correoAsistente,
        null,
        'clave123',
        'asistente',
        true
    );
    comprobar(
        !$roles->userHasPermission($asistenteId, 'usuarios.gestionar_estado'),
        'un asistente no debe cambiar estados de cuentas.'
    );
    comprobar(
        $roles->userHasPermission($docenteId, 'proyectos.consultar')
            && $roles->userHasPermission($docenteId, 'proyectos.crear')
            && $roles->userHasPermission($docenteId, 'horas.revisar')
            && !$roles->userHasPermission($docenteId, 'estudiantes.actualizar')
            && !$roles->userHasPermission($docenteId, 'proyectos.asignar'),
        'el docente debe limitarse a proyectos y revisión de horas, sin editar expedientes ni asignaciones.'
    );
    comprobar(
        !$roles->userHasPermission($asistenteId, 'estudiantes.consultar')
            && !$roles->userHasPermission($asistenteId, 'proyectos.consultar')
            && !$roles->userHasPermission($asistenteId, 'horas.registrar'),
        'el asistente no debe recibir acceso a módulos sin permisos explícitos.'
    );
    $permisosEsperados = [
        $adminId => [
            'usuarios.consultar', 'usuarios.crear', 'usuarios.gestionar_roles', 'usuarios.gestionar_estado',
            'estudiantes.crear', 'panel.administracion', 'solicitudes.revisar', 'estudiantes.consultar',
            'estudiantes.actualizar', 'estudiantes.actualizar_propios', 'proyectos.consultar',
            'proyectos.catalogo', 'proyectos.crear', 'proyectos.actualizar', 'proyectos.asignar',
            'horas.registrar', 'horas.registrar_para_estudiantes', 'horas.revisar',
        ],
        $coordinadorId => [
            'panel.administracion', 'solicitudes.revisar', 'estudiantes.consultar', 'estudiantes.actualizar',
            'proyectos.consultar', 'proyectos.crear', 'proyectos.actualizar', 'proyectos.asignar',
            'horas.registrar_para_estudiantes', 'horas.revisar',
        ],
        $docenteId => [
            'estudiantes.consultar', 'proyectos.consultar', 'proyectos.crear', 'proyectos.actualizar',
            'proyectos.catalogo', 'horas.revisar',
        ],
        $asistenteId => [],
        $estudianteId => ['horas.registrar', 'proyectos.catalogo', 'estudiantes.actualizar_propios'],
    ];
    $consultaPermisos = $conexion->prepare(
        'SELECT p.nombre_permiso
         FROM usuarios_roles ur
         JOIN roles_permisos rp ON rp.rol_id = ur.rol_id
         JOIN permisos p ON p.permiso_id = rp.permiso_id
         WHERE ur.usuario_id = :usuario_id'
    );
    foreach ($permisosEsperados as $usuarioId => $esperados) {
        $consultaPermisos->execute(['usuario_id' => $usuarioId]);
        $actuales = $consultaPermisos->fetchAll(PDO::FETCH_COLUMN);
        sort($esperados);
        sort($actuales);
        comprobar($actuales === $esperados, 'la matriz de permisos del usuario ' . $usuarioId . ' debe coincidir con su rol.');
    }

    $conexion->beginTransaction();
    $proyectos = new Proyecto($conexion);
    $registroHoras = new RegistroHoras($conexion);
    $consultaEstudiante = $conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE correo = :correo');
    $consultaEstudiante->execute(['correo' => $correoEstudiante]);
    $fichaId = (int) $consultaEstudiante->fetchColumn();
    comprobar($fichaId > 0, 'la cuenta debe tener una ficha de estudiante asociada.');
    $estudianteFiltrado = $estudiantes->all([
        'q' => $carnetEstudiante,
        'carrera' => 'software',
        'anio_academico' => '1',
        'modalidad_participacion' => 'Horas sociales',
    ]);
    comprobar(
        count(array_filter($estudianteFiltrado, static fn (array $row): bool => (int) $row['estudiante_id'] === $fichaId)) === 1,
        'los filtros de carnet, carrera, año y modalidad se deben poder combinar.'
    );
    $estudiantes->setStatus($fichaId, 'Inactivo');
    comprobar(
        count(array_filter($estudiantes->all(), static fn (array $row): bool => (int) $row['estudiante_id'] === $fichaId)) === 0
            && count(array_filter(
                $estudiantes->all(['estado_registro' => 'todos', 'q' => $carnetEstudiante]),
                static fn (array $row): bool => (int) $row['estudiante_id'] === $fichaId
            )) === 1,
        'los expedientes inactivos deben ocultarse del listado normal, pero seguir disponibles al incluirlos.'
    );
    $estudiantes->setStatus($fichaId, 'Activo');
    $datosActualizados = $estudiantes->find($fichaId);
    $datosActualizados['correo'] = 'updated-' . $sufijo . '@itca.edu.sv';
    $datosActualizados['carnet'] = 'U' . strtoupper(substr($sufijo, 0, 10));
    $estudiantes->update($fichaId, $datosActualizados);
    $cuentaActualizada = $conexion->prepare('SELECT correo, carnet FROM usuarios WHERE usuario_id = :usuario');
    $cuentaActualizada->execute(['usuario' => $estudianteId]);
    $cuentaActualizada = $cuentaActualizada->fetch();
    comprobar(
        $cuentaActualizada['correo'] === $datosActualizados['correo']
            && $cuentaActualizada['carnet'] === $datosActualizados['carnet'],
        'al editar el expediente también se deben mantener sincronizados el correo y carnet de acceso.'
    );

    $proyectoId = $proyectos->create([
        'nombre' => 'Proyecto de integración ' . $sufijo,
        'codigo' => 'TEST-' . strtoupper(substr($sufijo, 0, 8)),
        'descripcion' => 'Proyecto creado por la prueba de integración.',
        'objetivo' => 'Validar los flujos funcionales.',
        'carrera' => 'software',
        'anio_academico' => 1,
        'modalidad' => 'Presencial',
        'fecha_inicio' => '2099-01-01',
        'fecha_fin' => '2099-12-31',
        'estado' => 'Activo',
        'responsable_id' => $adminId,
    ], $adminId);
    $proyectoInvalido = $proyectos->find($proyectoId);
    $proyectoInvalido['codigo'] = 'BAD-' . strtoupper(substr($sufijo, 0, 8));
    $proyectoInvalido['fecha_fin'] = '2098-12-31';
    try {
        $proyectos->create($proyectoInvalido, $adminId);
        comprobar(false, 'la fecha final de un proyecto no puede ser anterior a la inicial.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'fecha'), 'la validación del proyecto debe identificar la fecha inválida.');
    }
    $activityData = [
        'titulo' => 'Actividad de prueba ' . $sufijo,
        'descripcion' => 'Actividad de integración.',
        'modalidad' => 'Presencial',
        'proyecto_id' => $proyectoId,
        'fecha_inicio' => '2099-01-02T10:00',
        'fecha_fin' => '2099-01-02T12:00',
        'lugar' => 'Aula de prueba',
        'recurso' => 'Aula-' . $sufijo,
        'responsable_id' => $coordinadorId,
        'horas_estimadas' => '2',
        'cupo' => '',
    ];
    $actividadInvalida = $activityData;
    $actividadInvalida['fecha_fin'] = '2099-01-02T10:00';
    try {
        $proyectos->addActivity($actividadInvalida, $adminId);
        comprobar(false, 'la fecha final de una actividad debe ser posterior al inicio.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'posterior'), 'la validación de actividad debe explicar el orden de fechas.');
    }
    $actividadId = $proyectos->addActivity($activityData, $adminId);
    comprobar(
        $proyectos->findActivity($actividadId)['fecha_inicio'] === '2099-01-02 10:00:00',
        'la fecha local de la actividad se debe guardar en formato DATETIME de SQL.'
    );
    $_SESSION['usuario_id'] = $adminId;
    $_GET = [];
    ob_start();
    (new HttpController($conexion))->render('cronograma_nuevo.php');
    $formularioActividad = (string) ob_get_clean();
    comprobar(
        str_contains($formularioActividad, 'Registrar Actividad del Cronograma')
            && str_contains($formularioActividad, 'Seleccione...'),
        'el formulario para crear actividades debe mostrarse cuando no se proporciona un identificador.'
    );
    $_GET['proyecto_id'] = (string) $proyectoId;
    ob_start();
    (new HttpController($conexion))->render('cronograma_nuevo.php');
    $formularioConActividades = (string) ob_get_clean();
    comprobar(
        str_contains($formularioConActividades, 'Actividades existentes del proyecto')
            && str_contains($formularioConActividades, 'Actividad de prueba'),
        'el formulario debe consultar las actividades existentes del proyecto seleccionado.'
    );
    $_GET['id'] = (string) $actividadId;
    ob_start();
    (new HttpController($conexion))->render('cronograma_nuevo.php');
    $formularioEdicion = (string) ob_get_clean();
    comprobar(
        str_contains($formularioEdicion, 'Editar Actividad del Cronograma')
            && str_contains($formularioEdicion, 'name="actividad_id" value="' . $actividadId . '"')
            && str_contains($formularioEdicion, '2099-01-02T10:00'),
        'la edición debe cargar los datos y enviar el identificador de la actividad.'
    );
    $_GET = [];
    unset($_SESSION['usuario_id']);
    try {
        $overlap = $activityData;
        $overlap['titulo'] = 'Actividad solapada';
        $overlap['fecha_inicio'] = '2099-01-02T11:00';
        $overlap['fecha_fin'] = '2099-01-02T11:30';
        $proyectos->addActivity($overlap, $adminId);
        comprobar(false, 'no se debe guardar una actividad que solape al responsable.');
    } catch (DomainException $error) {
        comprobar(str_contains($error->getMessage(), 'Actividad de prueba'), 'el conflicto debe identificar la actividad incompatible.');
    }

    $asignacionId = $proyectos->assign($proyectoId, $fichaId, $coordinadorId, 'Participante');
    comprobar($asignacionId > 0, 'se debe asignar al estudiante activo al proyecto.');
    try {
        $proyectos->assign($proyectoId, $fichaId, $coordinadorId, 'Participante');
        comprobar(false, 'no se deben duplicar asignaciones activas.');
    } catch (DomainException) {
    }
    $registroId = $registroHoras->create(
        $actividadId,
        $fichaId,
        1.5,
        'Trabajo de campo',
        '2099-01-02',
        $coordinadorId
    );
    try {
        $registroHoras->create($actividadId, $fichaId, 13, 'Límite excedido', '2099-01-02', $coordinadorId);
        comprobar(false, 'el registro de horas debe respetar el máximo configurado.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), '12'), 'el rechazo debe informar el límite de horas.');
    }
    $registroRechazadoId = $registroHoras->create(
        $actividadId,
        $fichaId,
        0.5,
        'Registro duplicado para revisar',
        '2099-01-02',
        $coordinadorId
    );
    comprobar(
        $registroHoras->hasPotentialDuplicate($actividadId, $fichaId, '2099-01-02'),
        'se debe advertir la posible duplicación de horas por actividad y fecha.'
    );
    comprobar(
        $proyectos->hoursSummary($fichaId)['total'] === 0.0
            && $proyectos->hoursSummary($fichaId)['pendientes'] === 2.0,
        'las horas pendientes no se deben sumar al total oficial.'
    );
    comprobar($proyectos->reviewHours($registroId, $coordinadorId, 'Aprobado'), 'la persona revisora debe aprobar horas pendientes.');
    try {
        $proyectos->reviewHours($registroRechazadoId, $coordinadorId, 'Rechazado');
        comprobar(false, 'el rechazo de horas debe requerir un motivo.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'motivo'), 'se debe informar que el motivo es obligatorio.');
    }
    comprobar(
        $proyectos->reviewHours($registroRechazadoId, $coordinadorId, 'Rechazado', 'No cuenta con respaldo.'),
        'se debe poder rechazar el registro informando el motivo.'
    );
    $resumen = $proyectos->hoursSummary($fichaId, '2099-01-02', '2099-01-02');
    comprobar(
        $resumen['total'] === 1.5 && $resumen['pendientes'] === 0.0 && $resumen['rechazadas'] === 0.5
            && count($resumen['desglose']) === 1,
        'el resumen debe separar horas aprobadas y rechazadas y desglosarlas por proyecto y periodo.'
    );
    comprobar(
        count($estudiantes->related($fichaId)['proyectos']) === 1
            && count($estudiantes->related($fichaId)['horas']) === 2,
        'el expediente debe relacionar proyectos y registros de horas.'
    );
    $conexion->prepare(
        'INSERT INTO evaluaciones_estudiante (estudiante_id, proyecto_id, evaluador_id, calificacion, comentarios)
         VALUES (:estudiante, :proyecto, :evaluador, 9.5, "Evaluación de integración")'
    )->execute(['estudiante' => $fichaId, 'proyecto' => $proyectoId, 'evaluador' => $coordinadorId]);
    $conexion->prepare(
        'INSERT INTO documentos_estudiante (estudiante_id, nombre, ruta, tipo, subido_por)
         VALUES (:estudiante, "Constancia de prueba", "privado/constancia.pdf", "PDF", :usuario)'
    )->execute(['estudiante' => $fichaId, 'usuario' => $coordinadorId]);
    $expedienteCompleto = $estudiantes->related($fichaId);
    comprobar(
        count($expedienteCompleto['evaluaciones']) === 1 && count($expedienteCompleto['documentos']) === 1,
        'el expediente debe incluir evaluaciones y documentos.'
    );
    $_SESSION['usuario_id'] = $adminId;
    $_GET = ['id' => (string) $fichaId];
    ob_start();
    (new HttpController($conexion))->render('estudiantes_detalle.php');
    $vistaExpediente = (string) ob_get_clean();
    comprobar(
        str_contains($vistaExpediente, '<div class="main-wrapper">')
            && str_contains($vistaExpediente, '<main class="content-area student-record">')
            && str_contains($vistaExpediente, htmlspecialchars($datosActualizados['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            && str_contains($vistaExpediente, 'Evaluaciones')
            && str_contains($vistaExpediente, 'Documentos')
            && !str_contains($vistaExpediente, '{{'),
        'el detalle debe conservar el layout compartido y mostrar datos relacionados sin marcadores pendientes.'
    );
    $fichaSinActividad = $estudiantes->create(
        'Estudiante sin actividad ' . $sufijo,
        'empty-' . $sufijo . '@itca.edu.sv',
        null,
        'software',
        1
    );
    $expedienteVacio = $estudiantes->related($fichaSinActividad);
    comprobar(
        $expedienteVacio['proyectos'] === []
            && $expedienteVacio['horas'] === []
            && $expedienteVacio['evaluaciones'] === []
            && $expedienteVacio['documentos'] === [],
        'un expediente sin actividad debe poder consultarse sin errores y mostrar colecciones vacías.'
    );
    $_GET = ['id' => (string) $fichaSinActividad];
    ob_start();
    (new HttpController($conexion))->render('estudiantes_detalle.php');
    $vistaExpedienteVacio = (string) ob_get_clean();
    comprobar(
        str_contains($vistaExpedienteVacio, 'Este expediente aún no tiene proyectos asociados.')
            && str_contains($vistaExpedienteVacio, 'No hay registros de horas para este expediente.')
            && str_contains($vistaExpedienteVacio, '<div class="main-wrapper">'),
        'el detalle vacío debe mostrar estados vacíos conservando el shell de navegación.'
    );
    $_GET = [];
    unset($_SESSION['usuario_id']);

    $proyectos->updateActivity($actividadId, array_merge($activityData, [
        'titulo' => 'Actividad actualizada ' . $sufijo,
        'fecha_inicio' => '2099-01-02T10:15',
        'fecha_fin' => '2099-01-02T12:15',
    ]), $coordinadorId);
    comprobar(
        str_starts_with($proyectos->findActivity($actividadId)['titulo'], 'Actividad actualizada'),
        'la actividad existente se debe poder editar.'
    );
    comprobar($proyectos->removeAssignment($asignacionId), 'se debe poder retirar una asignación.');
    comprobar(
        count($proyectos->assignments($proyectoId)) === 1
            && $proyectos->assignments($proyectoId)[0]['estado_participacion'] === 'Retirada',
        'retirar una asignación debe conservar su historial.'
    );
    $proyecto = $proyectos->find($proyectoId);
    $proyectos->update($proyectoId, array_merge($proyecto, ['estado' => 'Finalizado']), $adminId);
    try {
        $proyectos->assign($proyectoId, $fichaId, $coordinadorId);
        comprobar(false, 'un proyecto cerrado no debe aceptar nuevas asignaciones.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'activo'), 'el rechazo debe explicar que el proyecto no está activo.');
    }
    try {
        $proyectos->addActivity($activityData, $adminId);
        comprobar(false, 'un proyecto cerrado no debe aceptar actividades nuevas.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'activo'), 'la actividad debe rechazarse cuando el proyecto está cerrado.');
    }
    $conexion->rollBack();

    $rechazadoId = $usuarios->create(
        'Estudiante rechazado',
        $correoRechazado,
        'R' . $carnetEstudiante,
        'clave123',
        'estudiante',
        false,
        'software',
        true
    );
    $solicitudesRechazadas = $solicitudes->pendientes();
    $solicitudRechazada = array_values(array_filter(
        $solicitudesRechazadas,
        static fn (array $solicitud): bool => (int) $solicitud['usuario_id'] === $rechazadoId
    ))[0];
    comprobar(
        $solicitudes->resolver((int) $solicitudRechazada['solicitud_id'], $adminId, 'rechazada'),
        'el admin tambien debe poder rechazar una solicitud.'
    );
    comprobar(
        $usuarios->authenticate($correoRechazado, 'clave123') === null,
        'una solicitud rechazada no debe dejar entrar al alumno.'
    );
    comprobar(
        !$solicitudes->resolver((int) $solicitudRechazada['solicitud_id'], $adminId, 'aceptada'),
        'una solicitud ya rechazada no se debe poder aprobar despues.'
    );
    comprobar(
        $solicitudes->tieneSolicitudSinAceptar($rechazadoId),
        'una solicitud rechazada no debe poder activarse desde gestion de roles.'
    );

    $roles->assign($estudianteId, (int) $roles->findByName('docente')['rol_id']);
    comprobar($roles->forUser($estudianteId)['nombre_rol'] === 'docente', 'el rol debe poder cambiarse y consultarse.');

    try {
        $usuarios->create(
            'Correo repetido',
            $correoEstudiante,
            'D' . $carnetEstudiante,
            'clave123',
            'estudiante',
            true
        );
        comprobar(false, 'no se debe aceptar un correo repetido.');
    } catch (PDOException $error) {
        comprobar($error->getCode() === '23000', 'el correo repetido debe dar error de integridad.');
    }

    try {
        $usuarios->create(
            'Carnet repetido',
            $correoOtro,
            $carnetEstudiante,
            'clave123',
            'estudiante',
            true
        );
        comprobar(false, 'no se debe aceptar un carnet repetido.');
    } catch (PDOException $error) {
        comprobar($error->getCode() === '23000', 'el carnet repetido debe dar error de integridad.');
    }

    $usuarios->setActive($estudianteId, false);
    comprobar(
        $usuarios->authenticate($carnetEstudiante, 'clave123') === null,
        'una cuenta apagada no debe iniciar sesion.'
    );
    comprobar(
        $usuarios->findActiveById($estudianteId) === null,
        'una cuenta apagada no debe conservar acceso.'
    );

    $controladorHttp = new HttpController($conexion);
    $contenidoHtml = $controladorHttp->insertFieldError('<input name="email">', 'email', 'Correo <invalido>');
    comprobar(str_contains($contenidoHtml, 'role="alert"'), 'el error debe quedar junto al campo.');
    comprobar(str_contains($contenidoHtml, 'Correo &lt;invalido&gt;'), 'el texto del error debe escaparse.');

    echo "OK: roles, expedientes, proyectos, asignaciones, actividades, calendario y horas.\n";
} finally {
    if ($conexion instanceof PDO) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        $conexion->prepare('DELETE FROM estudiantes WHERE correo = :correo')
            ->execute(['correo' => $correoEstudiante]);
        $borrarUsuarios = $conexion->prepare(
            'DELETE FROM usuarios
             WHERE correo IN (:admin, :estudiante, :docente, :asistente, :coordinador, :rechazado, :otro)'
        );
        $borrarUsuarios->execute([
            'admin' => $correoAdmin,
            'estudiante' => $correoEstudiante,
            'docente' => $correoDocente,
            'asistente' => $correoAsistente,
            'coordinador' => $correoCoordinador,
            'rechazado' => $correoRechazado,
            'otro' => $correoOtro,
        ]);
    }
}
