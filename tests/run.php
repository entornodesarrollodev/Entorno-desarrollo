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
require_once dirname(__DIR__) . '/models/PortalSocial.php';
require_once dirname(__DIR__) . '/models/Participacion.php';
require_once dirname(__DIR__) . '/models/Auditoria.php';
require_once dirname(__DIR__) . '/controllers/HttpController.php';
require_once dirname(__DIR__) . '/controllers/PortalController.php';
require_once dirname(__DIR__) . '/controllers/WorkflowController.php';

// Este bloque común hace que cualquier condición falsa falle la suite con su contexto.
function comprobar(bool $condicion, string $mensaje): void
{
    // Este bloque detiene la suite en cuanto una condición de integración falla.
    if (!$condicion) {
        throw new RuntimeException('FALLO: ' . $mensaje);
    }
}

// Este bloque verifica reglas reutilizables de correo, emoji y fecha antes de usar la base.
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
// Este bloque genera identidades aisladas para no modificar cuentas reales existentes.
$sufijo = bin2hex(random_bytes(6));
$correoAdmin = 'admin-' . $sufijo . '@itca.edu.sv';
$correoEstudiante = 'student-' . $sufijo . '@itca.edu.sv';
$correoDocente = 'teacher-' . $sufijo . '@itca.edu.sv';
$correoAsistente = 'assistant-' . $sufijo . '@itca.edu.sv';
$correoCoordinador = 'coordinador-' . $sufijo . '@itca.edu.sv';
$correoRechazado = 'rejected-' . $sufijo . '@itca.edu.sv';
$correoOtro = 'other-' . $sufijo . '@itca.edu.sv';
$carnetEstudiante = 'S' . strtoupper(substr($sufijo, 0, 10));
$clavePrueba = bin2hex(random_bytes(24));
$conservarDatos = in_array('--keep-data', $argv, true);
$suiteCompletada = false;
$datosPersistidos = false;
$conexion = null;

try {
    // Este bloque prepara usuarios de varios roles y confirma autenticación básica.
    $conexion = conectarBaseDatos();
    $usuarios = new Usuario($conexion);
    $roles = new Rol($conexion);
    $adminId = $usuarios->create(
        'Administrador de prueba',
        $correoAdmin,
        null,
        $clavePrueba,
        'administrador',
        true
    );
    $admin = $usuarios->authenticate($correoAdmin, $clavePrueba);
    comprobar($admin !== null, 'una cuenta valida debe permitir iniciar sesion.');
    comprobar($usuarios->authenticate($correoAdmin, 'incorrecta') === null, 'una clave incorrecta debe rechazarse.');
    comprobar($usuarios->authenticate('', '') === null, 'campos vacios no deben iniciar sesion.');
    comprobar($roles->userHasPermission($adminId, 'usuarios.gestionar_roles'), 'el admin debe gestionar roles.');
    $coordinadorId = $usuarios->create(
        'Coordinador de prueba',
        $correoCoordinador,
        null,
        $clavePrueba,
        'coordinador',
        true
    );
    comprobar(
        $roles->userHasPermission($coordinadorId, 'panel.administracion')
            && $roles->userHasPermission($coordinadorId, 'solicitudes.revisar')
            && $roles->userHasPermission($coordinadorId, 'horas.registrar_para_estudiantes'),
        'el coordinador debe ver el panel, revisar solicitudes y registrar horas de estudiantes.'
    );

    // Este bloque simula el registro pendiente y su aprobación antes de activar la cuenta estudiante.
    $estudiantes = new Estudiante($conexion);
    $solicitudes = new SolicitudRegistro($conexion);
    $estudianteId = $usuarios->create(
        'Estudiante de prueba',
        $correoEstudiante,
        $carnetEstudiante,
        $clavePrueba,
        'estudiante',
        false,
        'software',
        true
    );
    comprobar(
        $usuarios->authenticate($carnetEstudiante, $clavePrueba) === null,
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
        $usuarios->authenticate($carnetEstudiante, $clavePrueba) !== null,
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
            && $roles->userHasPermission($estudianteId, 'solicitudes.crear')
            && $roles->userHasPermission($estudianteId, 'solicitudes.consultar_propias')
            && $roles->userHasPermission($estudianteId, 'actividades.calendario')
            && !$roles->userHasPermission($estudianteId, 'horas.registrar_para_estudiantes'),
        'el estudiante puede consultar su calendario, editar su contacto y gestionar solicitudes propias.'
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

    // Este bloque crea cuentas de personal para comprobar permisos de cada perfil.
    $docenteId = $usuarios->create(
        'Docente de prueba',
        $correoDocente,
        null,
        $clavePrueba,
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
        $clavePrueba,
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
    // Este bloque compara la matriz de permisos real con la esperada para cada rol.
    $permisosEsperados = [
        $adminId => [
            'usuarios.consultar', 'usuarios.crear', 'usuarios.gestionar_roles', 'usuarios.gestionar_estado',
            'estudiantes.crear', 'panel.administracion', 'solicitudes.revisar', 'estudiantes.consultar',
            'estudiantes.actualizar', 'estudiantes.actualizar_propios', 'proyectos.consultar',
            'proyectos.catalogo', 'proyectos.crear', 'proyectos.actualizar', 'proyectos.asignar',
            'horas.registrar', 'horas.registrar_para_estudiantes', 'horas.revisar',
            'portal.dashboard', 'actividades.calendario', 'respuestas.consultar', 'solicitudes.crear',
            'solicitudes.consultar_propias', 'notificaciones.gestionar', 'documentos.generar',
            'evaluaciones.crear', 'evaluaciones.criterios', 'asistencia.consultar',
            'privacidad.gestionar', 'privacidad.consultar',
        ],
        $coordinadorId => [
            'panel.administracion', 'solicitudes.revisar', 'estudiantes.consultar', 'estudiantes.actualizar',
            'proyectos.consultar', 'proyectos.crear', 'proyectos.actualizar', 'proyectos.asignar',
            'horas.registrar_para_estudiantes', 'horas.revisar',
            'portal.dashboard', 'actividades.calendario', 'respuestas.consultar',
            'notificaciones.gestionar', 'documentos.generar', 'evaluaciones.crear',
            'evaluaciones.criterios', 'asistencia.consultar', 'privacidad.consultar',
        ],
        $docenteId => [
            'estudiantes.consultar', 'proyectos.consultar', 'proyectos.crear', 'proyectos.actualizar',
            'proyectos.catalogo', 'horas.revisar', 'actividades.calendario', 'respuestas.consultar',
            'evaluaciones.crear', 'asistencia.consultar', 'privacidad.consultar',
        ],
        $asistenteId => [],
        $estudianteId => [
            'horas.registrar', 'proyectos.catalogo', 'estudiantes.actualizar_propios',
            'actividades.calendario', 'solicitudes.crear', 'solicitudes.consultar_propias',
            'asistencia.responder', 'privacidad.consultar',
        ],
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

    // Este bloque agrupa la integración funcional para poder conservarla o revertirla al final.
    $conexion->beginTransaction();
    $proyectos = new Proyecto($conexion);
    $registroHoras = new RegistroHoras($conexion);
    $consultaEstudiante = $conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE correo = :correo');
    $consultaEstudiante->execute(['correo' => $correoEstudiante]);
    $fichaId = (int) $consultaEstudiante->fetchColumn();
    comprobar($fichaId > 0, 'la cuenta debe tener una ficha de estudiante asociada.');
    // Este bloque comprueba filtros de expedientes y sincronización de datos de cuenta.
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

    // Este bloque crea proyectos y actividades de prueba y verifica validaciones del calendario.
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
        $proyectos->activitiesForProjects([]) === [],
        'una lista vacía de proyectos debe devolver un mapa vacío.'
    );

    $segundoProyectoId = $proyectos->create([
        'nombre' => 'Segundo proyecto de integración ' . $sufijo,
        'codigo' => 'TEST2-' . strtoupper(substr($sufijo, 0, 8)),
        'descripcion' => 'Segundo proyecto creado por la prueba.',
        'objetivo' => 'Validar agrupación de actividades.',
        'carrera' => 'software',
        'anio_academico' => 1,
        'modalidad' => 'Presencial',
        'fecha_inicio' => '2099-01-01',
        'fecha_fin' => '2099-12-31',
        'estado' => 'Activo',
        'responsable_id' => $adminId,
    ], $adminId);

    $segundaActividadData = $activityData;
    $segundaActividadData['titulo'] = 'Actividad del segundo proyecto ' . $sufijo;
    $segundaActividadData['proyecto_id'] = $segundoProyectoId;
    $segundaActividadData['recurso'] = 'Aula-segunda-' . $sufijo;
    $segundaActividadData['responsable_id'] = $adminId;
    $segundaActividadId = $proyectos->addActivity($segundaActividadData, $adminId);
    $actividadesPorProyecto = $proyectos->activitiesForProjects([$proyectoId, $segundoProyectoId]);

    comprobar(
        count($actividadesPorProyecto[$proyectoId] ?? []) === 1
            && (int) $actividadesPorProyecto[$proyectoId][0]['actividad_id'] === $actividadId
            && count($actividadesPorProyecto[$segundoProyectoId] ?? []) === 1
            && (int) $actividadesPorProyecto[$segundoProyectoId][0]['actividad_id'] === $segundaActividadId,
        'las actividades deben agruparse bajo el proyecto correspondiente.'
    );
    comprobar(
        $proyectos->activitiesForProjects([PHP_INT_MAX]) === [],
        'identificadores sin actividades deben devolver un mapa vacío.'
    );
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

    // Este bloque valida asignaciones y los flujos nuevos de evaluación, asistencia y privacidad.
    $asignacionId = $proyectos->assign($proyectoId, $fichaId, $coordinadorId, 'Participante');
    comprobar($asignacionId > 0, 'se debe asignar al estudiante activo al proyecto.');
    try {
        $proyectos->assign($proyectoId, $fichaId, $coordinadorId, 'Participante');
        comprobar(false, 'no se deben duplicar asignaciones activas.');
    } catch (DomainException) {
    }
    $participacion = new Participacion($conexion);
    $criterioId = $participacion->saveCriterion(
        'Criterio de prueba ' . $sufijo,
        'Criterio para la suite de integración.',
        1,
        5,
        true,
        $adminId
    );
    comprobar(
        count(array_filter(
            $participacion->activeCriteria(),
            static fn (array $criterion): bool => (int) $criterion['criterio_id'] === $criterioId
        )) === 1,
        'se debe configurar y consultar un criterio de evaluación activo.'
    );
    $evaluationId = $participacion->createEvaluation($fichaId, $proyectoId, 'QA-' . $sufijo, $coordinadorId);
    comprobar(
        $participacion->createEvaluation($fichaId, $proyectoId, 'QA-' . $sufijo, $coordinadorId) === $evaluationId
            && $participacion->evaluation($evaluationId)['estado_evaluacion'] === 'Borrador',
        'la evaluación debe guardar los criterios vigentes y evitar duplicar el mismo periodo.'
    );
    $workflowController = new WorkflowController($conexion, new HttpController($conexion));
    $_SESSION['usuario_id'] = $coordinadorId;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['id' => (string) $evaluationId];
    ob_start();
    $workflowController->evaluations();
    $evaluationPageHtml = (string) ob_get_clean();
    comprobar(
        str_contains($evaluationPageHtml, 'Guardar borrador')
            && str_contains($evaluationPageHtml, 'name="token_csrf"')
            && str_contains($evaluationPageHtml, 'Evaluaciones de participación'),
        'la pantalla de evaluación debe mostrar captura y protección CSRF.'
    );
    $_GET = [];
    unset($_SESSION['usuario_id']);
    try {
        $participacion->closeEvaluation($evaluationId, $coordinadorId);
        comprobar(false, 'no se debe cerrar una evaluación sin completar sus criterios obligatorios.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'puntuación'), 'la evaluación vacía debe indicar qué falta.');
    }
    $evaluation = $participacion->evaluation($evaluationId);
    $scores = ['comentario' => []];
    $scoreTotal = 0;
    foreach ($evaluation['criterios'] as $criterion) {
        $resultId = (int) $criterion['resultado_id'];
        $score = (int) $criterion['escala_min'];
        $scores[$resultId] = (string) $score;
        $scores['comentario'][$resultId] = 'Criterio verificado.';
        $scoreTotal += $score;
    }
    $expectedEvaluationScore = number_format($scoreTotal / count($evaluation['criterios']), 2, '.', '');
    $participacion->saveEvaluation(
        $evaluationId,
        $scores,
        'Evaluación de integración.',
        $coordinadorId
    );
    $participacion->closeEvaluation($evaluationId, $coordinadorId);
    comprobar(
        (string) $participacion->evaluation($evaluationId)['calificacion'] === $expectedEvaluationScore
            && $participacion->evaluation($evaluationId)['estado_evaluacion'] === 'Cerrada',
        'la evaluación debe calcular su calificación y bloquearse al cerrar.'
    );
    try {
        $participacion->saveEvaluation($evaluationId, [], 'Cambio posterior', $coordinadorId);
        comprobar(false, 'no se debe editar una evaluación cerrada.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'cerrada'), 'la edición posterior debe explicar el bloqueo.');
    }

    $participacion->respondAttendance($actividadId, $fichaId, 'Asistiré');
    $attendanceForStudent = array_values(array_filter(
        $participacion->studentActivities($fichaId),
        static fn (array $activity): bool => (int) $activity['actividad_id'] === $actividadId
    ));
    $attendanceSummary = array_values(array_filter(
        $participacion->attendanceActivities(),
        static fn (array $activity): bool => (int) $activity['actividad_id'] === $actividadId
    ));
    comprobar(
        ($attendanceForStudent[0]['respuesta'] ?? null) === 'Asistiré'
            && (int) ($attendanceSummary[0]['confirmados'] ?? 0) === 1,
        'el estudiante debe poder confirmar asistencia y el personal consultar el conteo.'
    );
    $_SESSION['usuario_id'] = $estudianteId;
    ob_start();
    $workflowController->attendance();
    $attendancePageHtml = (string) ob_get_clean();
    comprobar(
        str_contains($attendancePageHtml, 'Declinar asistencia')
            && str_contains($attendancePageHtml, 'name="token_csrf"'),
        'la pantalla de asistencia debe ofrecer respuestas y proteger sus formularios.'
    );
    unset($_SESSION['usuario_id']);
    $conexion->prepare(
        'UPDATE actividades_proyecto SET fecha_limite_confirmacion = DATE_SUB(NOW(), INTERVAL 1 DAY)
         WHERE actividad_id = :actividad'
    )->execute(['actividad' => $actividadId]);
    try {
        $participacion->respondAttendance($actividadId, $fichaId, 'No asistiré');
        comprobar(false, 'no se debe cambiar la asistencia después del plazo.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'plazo'), 'el rechazo debe indicar que venció el plazo.');
    }
    $conexion->prepare(
        'UPDATE actividades_proyecto SET fecha_limite_confirmacion = NULL WHERE actividad_id = :actividad'
    )->execute(['actividad' => $actividadId]);

    $workflowRequestId = $participacion->createRequest(
        $fichaId,
        null,
        'Solicitud de corrección ' . $sufijo,
        'Detalle original de prueba.',
        $estudianteId
    );
    comprobar(
        count($participacion->requestHistory($workflowRequestId)) === 1,
        'la creación de una solicitud debe guardar su primer evento de historial.'
    );
    try {
        $participacion->reviewRequest($workflowRequestId, $coordinadorId, 'Rechazada', '');
        comprobar(false, 'no se debe rechazar una solicitud sin motivo.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'observaciones'), 'la decisión debe exigir observaciones.');
    }
    $conexion->exec('SAVEPOINT request_attachment_test');
    $participacion->addRequestAttachment(
        $workflowRequestId,
        'respaldo.pdf',
        bin2hex(random_bytes(32)),
        'application/pdf',
        128,
        $estudianteId
    );
    comprobar(
        count($participacion->requestAttachments($workflowRequestId)) === 1,
        'la solicitud debe conservar los metadatos de los adjuntos privados.'
    );
    $conexion->exec('ROLLBACK TO SAVEPOINT request_attachment_test');
    $conexion->exec('RELEASE SAVEPOINT request_attachment_test');
    $_SESSION['usuario_id'] = $coordinadorId;
    $_GET = ['solicitud_id' => (string) $workflowRequestId];
    ob_start();
    $workflowController->reviewRequests();
    $reviewRequestPageHtml = (string) ob_get_clean();
    comprobar(
        str_contains($reviewRequestPageHtml, 'Aprobar')
            && str_contains($reviewRequestPageHtml, 'Devolver para corrección')
            && str_contains($reviewRequestPageHtml, 'name="token_csrf"'),
        'la bandeja debe mostrar controles de revisión y protección CSRF.'
    );
    $_GET = [];
    unset($_SESSION['usuario_id']);
    comprobar(
        $participacion->reviewRequest($workflowRequestId, $coordinadorId, 'Devuelta', 'Adjunta el respaldo faltante.'),
        'la persona revisora debe poder devolver una solicitud para corrección.'
    );
    comprobar(
        $participacion->updateReturnedRequest(
            $workflowRequestId,
            $fichaId,
            null,
            'Solicitud corregida ' . $sufijo,
            'Detalle actualizado.',
            $estudianteId
        ),
        'la persona estudiante debe poder corregir y reenviar una solicitud devuelta.'
    );
    comprobar(
        $participacion->reviewRequest($workflowRequestId, $coordinadorId, 'Aprobada', 'Solicitud atendida.')
            && count($participacion->requestHistory($workflowRequestId)) === 5,
        'el flujo de solicitud debe conservar devolución, reenvío y decisión final.'
    );
    $conexion->exec('SAVEPOINT privacy_notice_test');
    $noticeId = $participacion->publishPrivacyNotice(
        'QA-' . $sufijo,
        'Aviso de privacidad de prueba transaccional.',
        true,
        $adminId
    );
    $_SESSION['usuario_id'] = $estudianteId;
    ob_start();
    $workflowController->privacyNotice();
    $privacyPageHtml = (string) ob_get_clean();
    comprobar(
        str_contains($privacyPageHtml, 'Aviso de privacidad de prueba transaccional.')
            && str_contains($privacyPageHtml, 'He leído y acepto')
            && str_contains($privacyPageHtml, 'name="token_csrf"'),
        'la pantalla debe exponer el aviso vigente y requerir aceptación explícita.'
    );
    unset($_SESSION['usuario_id']);
    try {
        $participacion->acceptPrivacyNotice($estudianteId, $noticeId + 1);
        comprobar(false, 'no se debe aceptar una versión que ya no está vigente.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'cambió'), 'la aceptación debe confirmar que la versión sigue vigente.');
    }
    $participacion->acceptPrivacyNotice($estudianteId, $noticeId);
    comprobar(
        (int) $participacion->currentPrivacyNotice()['aviso_id'] === $noticeId
            && count($participacion->privacyAcceptances($estudianteId)) === 1,
        'el aviso vigente y la aceptación explícita deben registrar su versión.'
    );
    $conexion->exec('ROLLBACK TO SAVEPOINT privacy_notice_test');
    $conexion->exec('RELEASE SAVEPOINT privacy_notice_test');

    // Este bloque comprueba registro, duplicados, revisión y agregación de horas.
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

    // Este bloque verifica portal social, notificaciones, solicitudes, respuestas y documentos.
    $portal = new PortalSocial($conexion);
    $templateId = $portal->saveTemplate(
        'Plantilla de prueba ' . $sufijo,
        'Actividad',
        'Actividad nueva: {{nombre_actividad}}',
        'Proyecto {{nombre_proyecto}}, fecha {{fecha_actividad}}.',
        $adminId
    );
    comprobar($templateId > 0, 'se debe guardar una plantilla de notificación.');
    comprobar(
        $portal->notifyAssignedStudents($actividadId) === 1,
        'una nueva actividad debe crear una notificación interna por estudiante asignado.'
    );

    $solicitudEstudianteId = $portal->createRequest(
        $fichaId,
        $proyectoId,
        'Solicitud de integración ' . $sufijo,
        'Detalle de solicitud de prueba.'
    );
    comprobar(
        $portal->requestCountsForStudent($fichaId)['Pendiente'] === 1
            && count($portal->requestsForStudent($fichaId, 'integración')) === 1
            && $portal->requestDetailsForStudent($solicitudEstudianteId, $fichaId)['proyecto'] !== null,
        'el estudiante debe consultar sus solicitudes y sus detalles.'
    );
    comprobar(
        $portal->reviewRequest($solicitudEstudianteId, $coordinadorId, 'Aprobada', 'Solicitud atendida.')
            && $portal->requestCountsForStudent($fichaId)['Aprobada'] === 2,
        'la persona coordinadora debe poder resolver una solicitud.'
    );
    $portal->notifyRequestStudent($solicitudEstudianteId);
    $notificacionesEstudiante = $conexion->prepare(
        'SELECT COUNT(*) FROM notificaciones WHERE usuario_id = :usuario_id'
    );
    $notificacionesEstudiante->execute(['usuario_id' => $estudianteId]);
    comprobar(
        (int) $notificacionesEstudiante->fetchColumn() === 2,
        'la resolución debe generar una notificación interna para la persona estudiante.'
    );

    $conexion->prepare(
        'INSERT INTO respuestas_cuestionario
            (titulo_cuestionario, estudiante_id, proyecto_id, actividad_id, calificacion_general)
         VALUES ("Evaluación de integración", :estudiante, :proyecto, :actividad, "Excelente")'
    )->execute(['estudiante' => $fichaId, 'proyecto' => $proyectoId, 'actividad' => $actividadId]);
    $respuestaId = (int) $conexion->lastInsertId();
    $conexion->prepare(
        'INSERT INTO respuestas_cuestionario_detalle (respuesta_id, pregunta, respuesta)
         VALUES (:respuesta, "¿Cómo califica la actividad?", "Excelente")'
    )->execute(['respuesta' => $respuestaId]);
    $respuestasPortal = $portal->responseSummary([
        'cuestionario' => 'Evaluación de integración',
        'proyecto_id' => (string) $proyectoId,
        'actividad_id' => (string) $actividadId,
    ]);
    comprobar(
        $respuestasPortal['total'] === 1
            && $respuestasPortal['preguntas'] === 1
            && $portal->eligibleResponseCount(['actividad_id' => (string) $actividadId]) === 1
            && $portal->responseDetails($respuestaId)['answers'][0]['respuesta'] === 'Excelente',
        'la consulta de respuestas debe filtrar, resumir y mostrar el detalle guardado.'
    );

    $documentIds = $portal->generateDocuments(
        'diploma',
        $proyectoId,
        $actividadId,
        [$fichaId],
        '2099-01-03',
        $coordinadorId
    );
    comprobar(
        count($documentIds) === 1
            && $portal->issuedDocuments($documentIds)[0]['contenido']['estudiante'] === $datosActualizados['nombre_completo'],
        'se debe emitir y conservar una instantánea del documento de un estudiante con horas aprobadas.'
    );
    try {
        $portal->generateDocuments('diploma', $proyectoId, $actividadId, [$fichaId], '2099-01-03', $coordinadorId);
        comprobar(false, 'no se debe emitir dos veces el mismo tipo de documento por actividad y estudiante.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'elegibles'), 'el documento duplicado debe informar que no hay nuevas emisiones.');
    }

    comprobar(
        $portal->dashboardSummary()['estudiantes'] >= 1
            && $portal->dashboardSummary()['proyectos'] >= 2
            && $portal->projectStatusCounts()['Activo'] >= 2
            && count($portal->calendarActivities([
                'inicio' => '2099-01-02 00:00:00',
                'fin' => '2099-01-03 00:00:00',
                'proyecto_id' => $proyectoId,
                'estado' => 'Activo',
            ])) === 1,
        'el panel y calendario deben consultar métricas y actividades persistidas.'
    );

    // Este bloque renderiza las pantallas con roles distintos y verifica contenido y navegación.
    $portalController = new PortalController($conexion, new HttpController($conexion));
    $_SESSION['usuario_id'] = $adminId;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    ob_start();
    $portalController->dashboard();
    $dashboardHtml = (string) ob_get_clean();
    comprobar(
            str_contains($dashboardHtml, 'Estudiantes activos')
                && str_contains($dashboardHtml, 'Actividades por mes')
                && str_contains($dashboardHtml, 'Cerrar sesión')
                && str_contains($dashboardHtml, 'Administrador de prueba')
                && !str_contains($dashboardHtml, 'href="/mis_solicitudes.php"')
                && !str_contains($dashboardHtml, '>128</h3>'),
            'el dashboard debe renderizar métricas reales, navegación autorizada y reemplazar datos de muestra.'
    );
    $_GET = ['month' => '2099-01', 'day' => '2099-01-02'];
    ob_start();
    $portalController->calendar();
    $calendarHtml = (string) ob_get_clean();
    $calendarChecks = [
            str_contains($calendarHtml, 'Actividad de prueba'),
            str_contains($calendarHtml, 'name="proyecto_id"'),
            !str_contains($calendarHtml, 'Reunión de coor'),
    ];
    comprobar(
            !in_array(false, $calendarChecks, true),
            'el calendario debe renderizar actividades reales, filtros y navegación mensual: ' . implode(',', $calendarChecks)
    );
    $_SESSION['usuario_id'] = $estudianteId;
    $_GET = ['month' => '2099-01', 'day' => '2099-01-02', 'view' => 'day'];
    ob_start();
    $portalController->calendar();
    $calendarStudentHtml = (string) ob_get_clean();
    comprobar(
        str_contains($calendarStudentHtml, 'catalogo_proyectos.php?q=')
            && str_contains($calendarStudentHtml, 'value="Activo" selected')
            && str_contains($calendarStudentHtml, 'Ver detalle')
            && !str_contains($calendarStudentHtml, 'href="cronograma_nuevo.php"'),
        'el estudiante debe consultar el calendario activo y abrir detalles sin ver acciones de gestión.'
    );
    $_SESSION['usuario_id'] = $adminId;
    $_GET = [
            'cuestionario' => 'Evaluación de integración',
            'proyecto_id' => (string) $proyectoId,
            'actividad_id' => (string) $actividadId,
    ];
    ob_start();
    $portalController->responses();
    $responsesHtml = (string) ob_get_clean();
    comprobar(
            str_contains($responsesHtml, 'Estudiante de prueba')
                && str_contains($responsesHtml, 'Exportar CSV')
                && str_contains($responsesHtml, '¿Cómo califica la actividad?')
                && !str_contains($responsesHtml, 'Carlos Andrés Ramírez López'),
            'la vista de respuestas debe leer la base de datos en lugar de mostrar datos de demostración.'
    );
    $_GET = [];
    ob_start();
    $portalController->templates();
    $templateHtml = (string) ob_get_clean();
    comprobar(
            str_contains($templateHtml, 'name="mensaje"')
                && str_contains($templateHtml, 'Plantillas guardadas')
                && str_contains($templateHtml, 'Plantilla de prueba'),
            'la vista debe permitir guardar plantillas y listar las plantillas persistidas.'
    );
    $_GET = ['proyecto_id' => (string) $proyectoId, 'actividad_id' => (string) $actividadId];
    ob_start();
    $portalController->documents('diploma');
    $documentsHtml = (string) ob_get_clean();
    comprobar(
            str_contains($documentsHtml, 'name="estudiante_ids[]"')
                && str_contains($documentsHtml, 'Estudiante de prueba')
                && str_contains($documentsHtml, 'horas aprobadas'),
            'la vista de diplomas debe listar participantes elegibles desde el expediente de horas.'
    );
    $_SESSION['usuario_id'] = $estudianteId;
    $_GET = ['nueva' => '1'];
    ob_start();
    $portalController->requests();
    $requestsHtml = (string) ob_get_clean();
    comprobar(
            str_contains($requestsHtml, 'name="asunto"')
                && str_contains($requestsHtml, 'Solicitud de integración')
                && str_contains($requestsHtml, 'Ver detalle')
                && str_contains($requestsHtml, 'Cerrar sesión')
                && str_contains($requestsHtml, 'Estudiante de prueba'),
            'la vista debe ofrecer captura y consulta de solicitudes propias con navegación segura.'
    );
    $_GET = [];
    $_SESSION['usuario_id'] = $adminId;
    // Este bloque confirma que el expediente muestra relaciones y estados vacíos correctamente.
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
        count($expedienteCompleto['evaluaciones']) === 2 && count($expedienteCompleto['documentos']) === 1,
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

    // Este bloque verifica edición, cierre de proyectos y conservación o reversión de la prueba.
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
    if ($conservarDatos) {
        $conexion->commit();
        $datosPersistidos = true;
    } else {
        $conexion->rollBack();
    }

    // Este bloque cubre rechazo de registro, unicidad de identificadores y desactivación de cuentas.
    $rechazadoId = $usuarios->create(
        'Estudiante rechazado',
        $correoRechazado,
        'R' . $carnetEstudiante,
        $clavePrueba,
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
        $usuarios->authenticate($correoRechazado, $clavePrueba) === null,
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
            $correoAdmin,
            'D' . $carnetEstudiante,
            $clavePrueba,
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
            $conservarDatos ? $datosActualizados['carnet'] : $carnetEstudiante,
            $clavePrueba,
            'estudiante',
            true
        );
        comprobar(false, 'no se debe aceptar un carnet repetido.');
    } catch (PDOException $error) {
        comprobar($error->getCode() === '23000', 'el carnet repetido debe dar error de integridad.');
    }

    $usuarios->setActive($estudianteId, false);
    comprobar(
        $usuarios->authenticate($carnetEstudiante, $clavePrueba) === null,
        'una cuenta apagada no debe iniciar sesion.'
    );
    comprobar(
        $usuarios->findActiveById($estudianteId) === null,
        'una cuenta apagada no debe conservar acceso.'
    );

    // Este bloque valida escape de errores y marca la suite como completa para el cierre.
    $controladorHttp = new HttpController($conexion);
    $contenidoHtml = $controladorHttp->insertFieldError('<input name="email">', 'email', 'Correo <invalido>');
    comprobar(str_contains($contenidoHtml, 'role="alert"'), 'el error debe quedar junto al campo.');
    comprobar(str_contains($contenidoHtml, 'Correo &lt;invalido&gt;'), 'el texto del error debe escaparse.');

    $suiteCompletada = true;
    echo "OK: permisos, expedientes, proyectos, horas, evaluaciones, asistencia, solicitudes, privacidad, calendario, notificaciones y documentos.\n";
    if ($conservarDatos) {
        echo 'DATOS DE PRUEBA CONSERVADOS: ' . $sufijo . "\n";
    }
} finally {
    // Este bloque revierte datos incompletos o desactiva cuentas QA conservadas.
    if ($conexion instanceof PDO) {
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        if ($conservarDatos && $datosPersistidos) {
            $usuarioIds = array_values(array_filter([
                $adminId ?? null,
                $estudianteId ?? null,
                $docenteId ?? null,
                $asistenteId ?? null,
                $coordinadorId ?? null,
                $rechazadoId ?? null,
            ], static fn($id): bool => is_int($id) && $id > 0));
            if ($usuarioIds !== []) {
                $placeholders = implode(', ', array_fill(0, count($usuarioIds), '?'));
                $deshabilitarUsuarios = $conexion->prepare(
                    'UPDATE usuarios SET estado_activo = 0 WHERE usuario_id IN (' . $placeholders . ')'
                );
                $deshabilitarUsuarios->execute($usuarioIds);
            }
        } elseif (!$conservarDatos || !$suiteCompletada) {
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
}
