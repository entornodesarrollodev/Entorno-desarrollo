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
$resultadosPruebas = [];
$fechaInicioPruebas = new DateTimeImmutable('now', new DateTimeZone('America/El_Salvador'));
$sufijo = bin2hex(random_bytes(6));
$correoAdmin = 'admin-' . $sufijo . '@itca.edu.sv';
$correoEstudiante = 'student-' . $sufijo . '@itca.edu.sv';
$correoDocente = 'teacher-' . $sufijo . '@itca.edu.sv';
$correoAsistente = 'assistant-' . $sufijo . '@itca.edu.sv';
$correoCoordinador = 'coordinador-' . $sufijo . '@itca.edu.sv';
$correoRechazado = 'rejected-' . $sufijo . '@itca.edu.sv';
$correoOtro = 'other-' . $sufijo . '@itca.edu.sv';
$correoNombreInvalido = 'single-name-' . $sufijo . '@itca.edu.sv';
$carnetEstudiante = 'S' . strtoupper(substr($sufijo, 0, 10));
$clavePrueba = bin2hex(random_bytes(24));
$conservarDatos = in_array('--keep-data', $argv, true);
$suiteCompletada = false;
$datosPersistidos = false;
$conexion = null;

function comprobar(bool $condicion, string $mensaje): void
{
    $resultados = &$GLOBALS['resultadosPruebas'];
    $numeroPrueba = count($resultados) + 1;
    $estado = $condicion ? 'PASS' : 'FAIL';

    $resultados[] = [
        'id' => sprintf('TC-%03d', $numeroPrueba),
        'estado' => $estado,
        'nombre' => $mensaje,
        'esperado' => $mensaje,
        'obtenido' => $condicion
            ? 'La comprobación devolvió true; la condición esperada se cumplió.'
            : 'La comprobación devolvió false; la condición esperada no se cumplió.',
        'error' => $condicion ? null : 'FALLO: ' . $mensaje,
    ];

    if (!$condicion) {
        throw new RuntimeException('FALLO: ' . $mensaje);
    }
}

function generarReportesAutomaticos(array $resultados, DateTimeImmutable $fecha): void
{
    $directorioReportes = __DIR__ . DIRECTORY_SEPARATOR . 'reportes_auto';

    if (!is_dir($directorioReportes)
        && !mkdir($directorioReportes, 0775, true)
        && !is_dir($directorioReportes)
    ) {
        throw new RuntimeException('No se pudo crear la carpeta de reportes: ' . $directorioReportes);
    }

    $numeroEjecucion = 1;
    do {
        $directorioEjecucion = $directorioReportes . DIRECTORY_SEPARATOR . 'Ejecucion-' . $numeroEjecucion;
        if (is_dir($directorioEjecucion)) {
            $numeroEjecucion++;
            continue;
        }

        if (!mkdir($directorioEjecucion, 0775)) {
            if (is_dir($directorioEjecucion)) {
                $numeroEjecucion++;
                continue;
            }

            throw new RuntimeException('No se pudo crear la carpeta de ejecución: ' . $directorioEjecucion);
        }

        break;
    } while (true);

    $directoriosPorEstado = [
        'PASS' => $directorioEjecucion . DIRECTORY_SEPARATOR . 'pasado',
        'FAIL' => $directorioEjecucion . DIRECTORY_SEPARATOR . 'fallo',
    ];

    foreach ($directoriosPorEstado as $directorioEstado) {
        if (!mkdir($directorioEstado, 0775)) {
            throw new RuntimeException('No se pudo crear la carpeta de reportes: ' . $directorioEstado);
        }
    }

    foreach ($resultados as $resultado) {
        $contenido = sprintf(
            "%s\n%s · %s\n\n**Nombre:** %s\n\n**Módulo:** Pruebas automatizadas de integración.\n\n"
                . "**Objetivo:** Verificar que se cumpla la condición descrita para esta prueba.\n\n"
                . "**Precondiciones:**\n\n"
                . "- Entorno de pruebas configurado.\n"
                . "- Dependencias de la aplicación disponibles.\n\n"
                . "**Procedimiento:**\n\n"
                . "1. Ejecutar la comprobación automatizada.\n\n"
                . "**Resultado esperado:**\n\n"
                . "- %s\n\n"
                . "**Resultado obtenido:** %s\n\n"
                . "**Fecha de ejecución:** %s\n",
            $resultado['id'],
            $resultado['estado'],
            $resultado['nombre'],
            $resultado['nombre'],
            $resultado['esperado'],
            $resultado['obtenido'],
            $fecha->format('Y-m-d H:i:s')
        );
        $directorioEstado = $directoriosPorEstado[$resultado['estado']];
        $rutaReporte = $directorioEstado . DIRECTORY_SEPARATOR . $resultado['id'] . '.md';

        if (file_put_contents($rutaReporte, $contenido, LOCK_EX) === false) {
            throw new RuntimeException('No se pudo escribir el reporte: ' . $rutaReporte);
        }
    }

    $total = count($resultados);
    $aprobadas = count(array_filter(
        $resultados,
        static fn (array $resultado): bool => $resultado['estado'] === 'PASS'
    ));
    $fallidas = count(array_filter(
        $resultados,
        static fn (array $resultado): bool => $resultado['estado'] === 'FAIL'
    ));
    $resumen = sprintf(
        "# Resumen de ejecución %d\n\n"
            . "- **Fecha:** %s\n"
            . "- **Pruebas realizadas:** %d\n"
            . "- **Aprobadas:** %d\n"
            . "- **Fallidas:** %d\n"
            . "- **Estado de la suite:** %s\n",
        $numeroEjecucion,
        $fecha->format('Y-m-d H:i:s'),
        $total,
        $aprobadas,
        $fallidas,
        $fallidas === 0 ? 'PASS' : 'FAIL'
    );
    $rutaResumen = $directorioEjecucion . DIRECTORY_SEPARATOR . 'resumen.md';

    if (file_put_contents($rutaResumen, $resumen, LOCK_EX) === false) {
        throw new RuntimeException('No se pudo escribir el resumen: ' . $rutaResumen);
    }
}

try {
// Este bloque verifica reglas reutilizables de correo, emoji y fecha antes de usar la base.
comprobar(Validacion::correoItcaValido('alumno@itca.edu.sv'), 'se debe aceptar correo del dominio ITCA.');
comprobar(Validacion::correoItcaValido('ALUMNO@ITCA.EDU.SV'), 'el dominio debe aceptar mayusculas.');
comprobar(!Validacion::correoItcaValido('alumno@gmail.com'), 'se debe rechazar correo externo.');
comprobar(!Validacion::nombreCompletoValido('Mononimo'), 'el registro debe exigir nombre y apellido.');
comprobar(Validacion::nombreCompletoValido('Nombre Apellido'), 'se debe aceptar un nombre con apellido.');
comprobar(Validacion::nombrePersonaValido("María O'Neil"), 'se deben aceptar nombres con tildes y apóstrofos.');
comprobar(!Validacion::nombrePersonaValido('Nombre123'), 'los nombres no deben aceptar números.');
comprobar(Validacion::duiValido('01234567-8') && Validacion::duiValido('012345678'), 'el DUI debe aceptar exactamente nueve dígitos, con guion opcional.');
comprobar(!Validacion::duiValido('01234567890'), 'el DUI debe rechazar números excedentes.');
comprobar(Validacion::telefonoValido('2222-2222') && Validacion::telefonoValido('+503 2222 2222'), 'el teléfono debe aceptar ocho dígitos locales y prefijo internacional.');
comprobar(!Validacion::telefonoValido('222222222'), 'el teléfono debe rechazar más de ocho dígitos locales.');
comprobar(Validacion::carreraRegistroValida('software'), 'el selector debe aceptar una carrera disponible.');
comprobar(!Validacion::carreraRegistroValida('carrera-inventada'), 'el registro debe rechazar valores de carrera ajenos al selector.');
comprobar(Validacion::horasRegistroValida('0.01') && Validacion::horasRegistroValida('8'), 'el registro de horas debe aceptar centésimas hasta el límite diario.');
comprobar(!Validacion::horasRegistroValida('0.001') && !Validacion::horasRegistroValida('8.01'), 'el registro de horas debe rechazar más de dos decimales y excesos diarios.');
comprobar(Validacion::horasEstimadasValidas('0.5') && Validacion::horasEstimadasValidas('24'), 'las horas estimadas deben aceptar medias horas dentro del intervalo permitido.');
comprobar(!Validacion::horasEstimadasValidas('1.25'), 'las horas estimadas deben rechazar fracciones ajenas al paso de media hora.');
comprobar(Validacion::tieneEmoji('Nombre 😀'), 'se deben detectar emojis en nombres.');
comprobar(Validacion::tieneEmoji('Bandera 🇸🇻'), 'se deben detectar emojis de bandera.');
comprobar(!Validacion::tieneEmoji('Nombre sin emoji'), 'el texto normal debe pasar la revision.');
comprobar(Validacion::fechaValida('2028-02-29'), 'una fecha válida de año bisiesto debe aceptarse.');
comprobar(!Validacion::fechaValida('2027-02-29'), 'una fecha inválida no debe normalizarse.');
comprobar(Validacion::fechaHoraLocalValida('2028-02-29T10:30'), 'se debe aceptar una fecha y hora local válida.');
comprobar(!Validacion::fechaHoraLocalValida('2028-02-30T10:30'), 'se debe rechazar una fecha y hora imposible.');
// Este bloque genera identidades aisladas para no modificar cuentas reales existentes.
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
    foreach (['dui' => '01234567890', 'telefono' => '222222222'] as $campoInvalido => $valorInvalido) {
        try {
            $estudiantes->create([
                'nombre_completo' => 'Nombre Apellido',
                'nombres' => 'Nombre',
                'apellidos' => 'Apellido',
                'correo' => 'alumno@itca.edu.sv',
                'carnet' => 'ABC123',
                'carrera' => 'software',
                'anio_academico' => 1,
                'modalidad_participacion' => 'Horas sociales',
                'dui' => $campoInvalido === 'dui' ? $valorInvalido : '',
                'telefono' => $campoInvalido === 'telefono' ? $valorInvalido : '',
            ]);
            comprobar(false, 'el modelo debe rechazar identificadores de contacto con longitud incorrecta.');
        } catch (InvalidArgumentException $error) {
            comprobar(
                str_contains($error->getMessage(), $campoInvalido === 'dui' ? 'DUI' : 'teléfono'),
                'el modelo debe explicar el formato inválido de ' . $campoInvalido . '.'
            );
        }
    }
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
    $nombreInvalidoId = $usuarios->create(
        'Nombre Antiguo',
        $correoNombreInvalido,
        'N' . strtoupper(substr($sufijo, 0, 10)),
        $clavePrueba,
        'estudiante',
        false,
        'software',
        true
    );
    $conexion->prepare('UPDATE usuarios SET nombre_completo = "Mononimo" WHERE usuario_id = :usuario')
        ->execute(['usuario' => $nombreInvalidoId]);
    $solicitudNombreInvalido = array_values(array_filter(
        $solicitudes->pendientes(),
        static fn (array $solicitud): bool => (int) $solicitud['usuario_id'] === $nombreInvalidoId
    ));
    try {
        $solicitudes->resolver((int) $solicitudNombreInvalido[0]['solicitud_id'], $adminId, 'aceptada');
        comprobar(false, 'no se debe aprobar una cuenta sin apellido.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'nombre y un apellido'), 'la aprobación debe explicar cómo corregir el nombre.');
    }
    $estadoNombreInvalido = $conexion->prepare(
        "SELECT s.estado_solicitud, u.estado_activo,
                (SELECT COUNT(*) FROM estudiantes e WHERE e.usuario_id = u.usuario_id) AS expedientes
         FROM solicitudes_registro s JOIN usuarios u ON u.usuario_id = s.usuario_id
         WHERE s.usuario_id = :usuario"
    );
    $estadoNombreInvalido->execute(['usuario' => $nombreInvalidoId]);
    $estadoNombreInvalido = $estadoNombreInvalido->fetch();
    comprobar(
        $estadoNombreInvalido['estado_solicitud'] === 'pendiente'
            && (int) $estadoNombreInvalido['estado_activo'] === 0
            && (int) $estadoNombreInvalido['expedientes'] === 0,
        'si falla la aprobación del nombre, la solicitud debe seguir pendiente sin activar la cuenta.'
    );
    comprobar(
        $solicitudes->resolver(
            (int) $solicitudNombreInvalido[0]['solicitud_id'],
            $adminId,
            'aceptada',
            'Nombre Corregido'
        ),
        'el administrador debe poder corregir el nombre antes de aprobar la cuenta.'
    );
    $nombreCorregido = $conexion->prepare(
        'SELECT u.nombre_completo, u.estado_activo, e.estudiante_id
         FROM usuarios u JOIN estudiantes e ON e.usuario_id = u.usuario_id
         WHERE u.usuario_id = :usuario'
    );
    $nombreCorregido->execute(['usuario' => $nombreInvalidoId]);
    $nombreCorregido = $nombreCorregido->fetch();
    comprobar(
        $nombreCorregido['nombre_completo'] === 'Nombre Corregido'
            && (int) $nombreCorregido['estado_activo'] === 1
            && (int) $nombreCorregido['estudiante_id'] > 0,
        'la corrección debe activar la cuenta y crear el expediente asociado.'
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
    $roles->assign($estudianteId, (int) $roles->findByName('estudiante')['rol_id']);
    $_SESSION['usuario_id'] = $adminId;
    $_GET = [];
    ob_start();
    (new HttpController($conexion))->render('dashboard_admin.php');
    $dashboardConRolEstudiante = (string) ob_get_clean();
    preg_match('~<div class="number">(\d+)</div>~', $dashboardConRolEstudiante, $conteoEstudiantesConRol);
    $roles->assign($estudianteId, (int) $roles->findByName('asistente')['rol_id']);
    ob_start();
    (new HttpController($conexion))->render('dashboard_admin.php');
    $dashboardTrasCambioRol = (string) ob_get_clean();
    preg_match('~<div class="number">(\d+)</div>~', $dashboardTrasCambioRol, $conteoEstudiantesTrasCambio);
    comprobar(
        isset($conteoEstudiantesConRol[1], $conteoEstudiantesTrasCambio[1])
            && (int) $conteoEstudiantesTrasCambio[1] === (int) $conteoEstudiantesConRol[1] - 1
            && $estudiantes->find($fichaId) !== null,
        'el conteo debe seguir el rol vigente y conservar el expediente tras cambiar el rol.'
    );
    comprobar(
        str_contains($dashboardTrasCambioRol, 'aria-label="Navegación principal"')
            && str_contains($dashboardTrasCambioRol, 'href="#main-content"')
            && str_contains($dashboardTrasCambioRol, '<li class="nav-group">')
            && str_contains($dashboardTrasCambioRol, 'Solicitudes de registro pendientes')
            && !str_contains($dashboardTrasCambioRol, 'Módulos disponibles')
            && !str_contains($dashboardTrasCambioRol, 'Módulos del sistema'),
        'el panel administrativo debe mostrar el resumen de solicitudes sin un bloque redundante de accesos.'
    );
    $_GET = ['panel' => 'solicitudes_registro'];
    ob_start();
    (new HttpController($conexion))->render('dashboard_admin.php');
    $vistaSolicitudesRegistro = (string) ob_get_clean();
    $_GET = [];
    $modeloSolicitudesRegistro = new SolicitudRegistro($conexion);
    $totalSolicitudesPendientes = $modeloSolicitudesRegistro->contarPendientes();
    comprobar(
        str_contains($vistaSolicitudesRegistro, '<h1>Solicitudes de registro</h1>')
            && str_contains($vistaSolicitudesRegistro, 'registration-search')
            && str_contains($vistaSolicitudesRegistro, 'Fecha de solicitud')
            && count($modeloSolicitudesRegistro->pendientesPaginadas('', 20, 0)) === min(20, $totalSolicitudesPendientes),
        'las solicitudes de registro deben abrirse en un listado buscable y paginado.'
    );
    $roles->assign($estudianteId, (int) $roles->findByName('estudiante')['rol_id']);
    $_SESSION['usuario_id'] = $estudianteId;
    ob_start();
    (new HttpController($conexion))->render('dashboard.php');
    $dashboardEstudiante = (string) ob_get_clean();
    comprobar(
        str_contains($dashboardEstudiante, 'class="dashboard-dynamic-grid"')
            && !str_contains($dashboardEstudiante, 'class="dashboard-links"'),
        'el panel del estudiante no debe repetir los accesos rápidos estáticos y dinámicos.'
    );
    $_SESSION['usuario_id'] = $adminId;
    unset($_SESSION['usuario_id']);
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
        'fecha_inicio' => '2099-01-02',
        'fecha_fin' => '2099-01-02',
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
    $actividadHorasInvalida = $activityData;
    $actividadHorasInvalida['horas_estimadas'] = '1.25';
    try {
        $proyectos->addActivity($actividadHorasInvalida, $adminId);
        comprobar(false, 'las horas estimadas deben respetar incrementos de media hora.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'media hora'), 'el error debe explicar el incremento válido de horas estimadas.');
    }
    $actividadInvalida = $activityData;
    $actividadInvalida['fecha_fin'] = '2099-01-02T10:00';
    try {
        $proyectos->addActivity($actividadInvalida, $adminId);
        comprobar(false, 'la fecha final de una actividad debe ser posterior al inicio.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'posterior'), 'la validación de actividad debe explicar el orden de fechas.');
    }
    foreach (['2099-01-01T10:00' => '2099-01-01T11:00', '2099-01-03T10:00' => '2099-01-03T11:00'] as $inicioFuera => $finFuera) {
        $actividadFueraDelProyecto = $activityData;
        $actividadFueraDelProyecto['fecha_inicio'] = $inicioFuera;
        $actividadFueraDelProyecto['fecha_fin'] = $finFuera;
        try {
            $proyectos->addActivity($actividadFueraDelProyecto, $adminId);
            comprobar(false, 'la actividad debe quedar dentro del periodo del proyecto.');
        } catch (InvalidArgumentException $error) {
            comprobar(str_contains($error->getMessage(), 'periodo de fechas'), 'el error debe explicar el límite del proyecto.');
        }
    }
    $actividadId = $proyectos->addActivity($activityData, $adminId);
    $periodoReducido = $proyectos->find($proyectoId);
    $periodoReducido['fecha_inicio'] = '2099-01-03';
    $periodoReducido['fecha_fin'] = '2099-01-04';
    try {
        $proyectos->update($proyectoId, $periodoReducido, $adminId);
        comprobar(false, 'no se debe cambiar el periodo de un proyecto dejando actividades fuera de fechas.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'actividad'), 'el cambio de periodo debe identificar la actividad incompatible.');
    }
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
            && str_contains($formularioActividad, 'Seleccione...')
            && str_contains($formularioActividad, 'name="confirmar_conflicto"'),
        'el formulario debe permitir confirmar explícitamente un cruce de horario.'
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
    $conflictoPorLugar = $activityData;
    $conflictoPorLugar['titulo'] = 'Actividad con recurso implícito';
    $conflictoPorLugar['fecha_inicio'] = '2099-01-02T11:00';
    $conflictoPorLugar['fecha_fin'] = '2099-01-02T11:30';
    $conflictoPorLugar['responsable_id'] = $adminId;
    $conflictoPorLugar['lugar'] = $activityData['recurso'];
    $conflictoPorLugar['recurso'] = '';
    try {
        $proyectos->addActivity($conflictoPorLugar, $adminId);
        comprobar(false, 'el lugar debe considerarse recurso compartido cuando no se especifica uno aparte.');
    } catch (DomainException $error) {
        comprobar(
            str_contains($error->getMessage(), 'Actividad de prueba')
                && str_contains($error->getMessage(), 'Marca la casilla de confirmación'),
            'el recurso implícito del lugar debe generar una advertencia de cruce.'
        );
    }
    try {
        $overlap = $activityData;
        $overlap['titulo'] = 'Actividad solapada';
        $overlap['fecha_inicio'] = '2099-01-02T11:00';
        $overlap['fecha_fin'] = '2099-01-02T11:30';
        $proyectos->addActivity($overlap, $adminId);
        comprobar(false, 'no se debe guardar una actividad que solape al responsable.');
    } catch (DomainException $error) {
        comprobar(
            str_contains($error->getMessage(), 'Actividad de prueba')
                && str_contains($error->getMessage(), 'Marca la casilla de confirmación'),
            'el aviso debe identificar el cruce y solicitar confirmación.'
        );
    }
    $overlap['confirmar_conflicto'] = true;
    $actividadConfirmadaId = $proyectos->addActivity($overlap, $adminId);
    comprobar(
        $proyectos->findActivity($actividadConfirmadaId) !== null,
        'una persona autorizada debe poder guardar el cruce después de confirmarlo.'
    );

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
    comprobar(
        $participacion->countEvaluations('QA-' . $sufijo, 'Borrador', $proyectoId) === 1
            && (int) $participacion->evaluations('QA-' . $sufijo, 'Borrador', $proyectoId, '', '', 1)[0]['evaluacion_id'] === $evaluationId,
        'los filtros de evaluaciones deben combinar texto, estado y proyecto y limitar resultados.'
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
    $_GET = ['q' => 'QA-' . $sufijo, 'estado' => 'Borrador', 'proyecto_id' => (string) $proyectoId];
    ob_start();
    $workflowController->evaluations();
    $evaluationFilterHtml = (string) ob_get_clean();
    comprobar(
        str_contains($evaluationFilterHtml, 'evaluation-search')
            && str_contains($evaluationFilterHtml, 'value="QA-' . $sufijo . '"')
            && str_contains($evaluationFilterHtml, 'name="estado"')
            && str_contains($evaluationFilterHtml, 'Iniciar una evaluación'),
        'la pantalla de evaluaciones debe exponer filtros y agrupar el formulario de creación.'
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
            && array_key_exists('fecha_cancelacion', $attendanceForStudent[0])
            && $attendanceForStudent[0]['fecha_cancelacion'] === null
            && array_key_exists('fecha_cancelacion', $attendanceSummary[0])
            && $attendanceSummary[0]['fecha_cancelacion'] === null
            && (int) ($attendanceSummary[0]['confirmados'] ?? 0) === 1,
        'el estudiante debe poder confirmar asistencia y el personal consultar conteos y estado de cancelación.'
    );
    $activitiesFilteredCount = $participacion->countAttendanceActivities('Actividad de prueba ' . $sufijo, $proyectoId);
    $studentsFilteredCount = $participacion->countAttendanceForActivity($actividadId, 'Estudiante de prueba');
    $activitiesFiltered = $participacion->attendanceActivities('Actividad de prueba ' . $sufijo, $proyectoId, '', '', 1);
    comprobar(
        $activitiesFilteredCount === 1
            && $studentsFilteredCount === 1
            && isset($activitiesFiltered[0])
            && (int) $activitiesFiltered[0]['actividad_id'] === $actividadId,
        'los filtros y límites de asistencia deben acotar actividades y estudiantes: '
            . $activitiesFilteredCount . '/' . $studentsFilteredCount . '/' . count($activitiesFiltered)
    );
    $_SESSION['usuario_id'] = $coordinadorId;
    $_GET = ['q' => 'Actividad de prueba ' . $sufijo, 'proyecto_id' => (string) $proyectoId];
    ob_start();
    $workflowController->attendanceReview();
    $attendanceReviewHtml = (string) ob_get_clean();
    comprobar(
        str_contains($attendanceReviewHtml, 'attendance-search')
            && str_contains($attendanceReviewHtml, 'Actividad de prueba ' . $sufijo)
            && str_contains($attendanceReviewHtml, 'name="desde"'),
        'la revisión de asistencia debe presentar filtros para actividades y periodos.'
    );
    unset($_SESSION['usuario_id']);
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
        $registroHoras->create($actividadId, $fichaId, '0.001', 'Precisión inválida', '2099-01-02', $coordinadorId);
        comprobar(false, 'las horas registradas no deben redondearse silenciosamente por debajo de una centésima.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), 'dos decimales'), 'el error debe explicar la precisión admitida.');
    }
    try {
        $registroHoras->create($actividadId, $fichaId, 8.5, 'Límite excedido', '2099-01-02', $coordinadorId);
        comprobar(false, 'un registro diario no debe superar ocho horas.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), '8 por día'), 'el rechazo debe informar el máximo diario.');
    }
    foreach (['2099-01-01', '2099-01-03'] as $fechaFueraDeActividad) {
        try {
            $registroHoras->create($actividadId, $fichaId, 0.25, 'Fecha fuera de actividad', $fechaFueraDeActividad, $coordinadorId);
            comprobar(false, 'las horas deben registrarse dentro de las fechas de la actividad.');
        } catch (InvalidArgumentException $error) {
            comprobar(str_contains($error->getMessage(), 'dentro de las fechas'), 'el error debe indicar que la fecha sale de la actividad.');
        }
    }
    $registroRechazadoId = $registroHoras->create(
        $actividadId,
        $fichaId,
        0.5,
        'Registro duplicado para revisar',
        '2099-01-02',
        $coordinadorId
    );
    $registroExtraId = $registroHoras->create(
        $actividadId,
        $fichaId,
        6,
        'Trabajo adicional de campo',
        '2099-01-02',
        $coordinadorId
    );
    comprobar(
        $registroHoras->hasPotentialDuplicate($actividadId, $fichaId, '2099-01-02'),
        'se debe advertir la posible duplicación de horas por actividad y fecha.'
    );
    comprobar(
        $proyectos->hoursSummary($fichaId)['total'] === 0.0
            && $proyectos->hoursSummary($fichaId)['pendientes'] === 8.0,
        'se deben sumar las horas pendientes sin limitarlas por las horas estimadas de la actividad.'
    );
    try {
        $registroHoras->create($actividadId, $fichaId, 0.01, 'Exceso diario', '2099-01-02', $coordinadorId);
        comprobar(false, 'el total diario pendiente no debe superar ocho horas.');
    } catch (InvalidArgumentException $error) {
        comprobar(str_contains($error->getMessage(), '8 horas por estudiante al día'), 'el exceso diario debe informar el límite acumulado.');
    }
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
    $registroLuegoDeRechazoId = $registroHoras->create(
        $actividadId,
        $fichaId,
        0.5,
        'Trabajo de campo luego del rechazo',
        '2099-01-02',
        $coordinadorId
    );
    comprobar(
        $registroExtraId > 0 && $registroLuegoDeRechazoId > 0,
        'las horas aprobadas y pendientes deben contar hacia el límite diario, pero las rechazadas no.'
    );
    comprobar(
        $proyectos->reviewHours($registroExtraId, $coordinadorId, 'Aprobado'),
        'se deben poder aprobar horas por encima de las horas estimadas de la actividad.'
    );
    $resumen = $proyectos->hoursSummary($fichaId, '2099-01-02', '2099-01-02');
    comprobar(
        $resumen['total'] === 7.5 && $resumen['pendientes'] === 0.5 && $resumen['rechazadas'] === 0.5
            && count($resumen['desglose']) === 1,
        'el resumen debe reflejar las horas aprobadas por encima de lo estimado y separar las rechazadas.'
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
    $plantillasActivas = $conexion->query('SELECT plantilla_id FROM plantillas_notificacion WHERE activa = 1')->fetchAll(PDO::FETCH_COLUMN);
    $conexion->exec('UPDATE plantillas_notificacion SET activa = 0');
    try {
        $avisoPredeterminadoEnviado = $portal->notifyAssignedStudents($actividadId);
    } finally {
        $reactivarPlantilla = $conexion->prepare('UPDATE plantillas_notificacion SET activa = 1 WHERE plantilla_id = :id');
        foreach ($plantillasActivas as $plantillaActivaId) {
            $reactivarPlantilla->execute(['id' => $plantillaActivaId]);
        }
    }
    comprobar(
        $avisoPredeterminadoEnviado === 1,
        'las actividades deben notificar con el mensaje predeterminado cuando no hay plantilla activa.'
    );
    $notificacionPredeterminada = $conexion->prepare(
        'SELECT asunto, plantilla_id FROM notificaciones WHERE usuario_id = :usuario_id ORDER BY notificacion_id DESC LIMIT 1'
    );
    $notificacionPredeterminada->execute(['usuario_id' => $estudianteId]);
    $notificacionPredeterminada = $notificacionPredeterminada->fetch();
    comprobar(
        $notificacionPredeterminada !== false
            && $notificacionPredeterminada['asunto'] === 'Nueva actividad en tu proyecto'
            && $notificacionPredeterminada['plantilla_id'] === null,
        'el aviso predeterminado debe ser claro y no depender de una plantilla guardada.'
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
        (int) $notificacionesEstudiante->fetchColumn() === 3,
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

    $calendarActivities = $portal->calendarActivities([
        'inicio' => '2099-01-02 00:00:00',
        'fin' => '2099-01-03 00:00:00',
        'proyecto_id' => $proyectoId,
        'estado' => 'Activo',
    ]);
    comprobar(
        $portal->dashboardSummary()['estudiantes'] >= 1
            && $portal->dashboardSummary()['proyectos'] >= 2
            && $portal->projectStatusCounts()['Activo'] >= 2
            && count($calendarActivities) === 2
            && array_key_exists('fecha_cancelacion', $calendarActivities[0])
            && array_key_exists('motivo_cancelacion', $calendarActivities[0])
            && $calendarActivities[0]['fecha_cancelacion'] === null,
        'el panel y calendario deben consultar métricas, actividades y sus datos de cancelación.'
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
                && !str_contains($dashboardHtml, 'crear_plantilla.php')
                && !str_contains($dashboardHtml, '/mis_solicitudes.php')
                && !str_contains($dashboardHtml, 'Detalle y calendario:')
                && !str_contains($dashboardHtml, ': /')
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
            && str_contains($calendarStudentHtml, 'name="estado" value="Activo"')
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
                && str_contains($templateHtml, 'Plantillas activas para los avisos')
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
                && str_contains($documentsHtml, 'horas aprobadas')
                && str_contains($documentsHtml, 'Carrera:')
                && str_contains($documentsHtml, 'Año académico:')
                && str_contains($documentsHtml, 'Modalidad de participación:')
                && str_contains($documentsHtml, 'versión 1.0'),
            'la vista de diplomas debe listar datos académicos y participantes elegibles desde el expediente de horas.'
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
    $horasDelExpediente = $estudiantes->related($fichaId)['horas'];
    comprobar(
        count($estudiantes->related($fichaId)['proyectos']) === 1
            && count($horasDelExpediente) === 4
            && count(array_filter(
                $horasDelExpediente,
                static fn (array $registro): bool => in_array(
                    (int) $registro['registro_id'],
                    [$registroId, $registroRechazadoId, $registroExtraId, $registroLuegoDeRechazoId],
                    true
                )
            )) === 4,
        'el expediente debe relacionar todos los registros de horas, sin limitarlos a dos.'
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
            && str_contains($vistaExpediente, 'id="main-content" tabindex="-1" class="content-area student-record"')
            && str_contains($vistaExpediente, htmlspecialchars($datosActualizados['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))
            && str_contains($vistaExpediente, 'Evaluaciones')
            && str_contains($vistaExpediente, 'Documentos')
            && !str_contains($vistaExpediente, '{{'),
        'el detalle debe conservar el layout compartido y mostrar datos relacionados sin marcadores pendientes.'
    );
    $fichaSinActividad = $estudiantes->create(
        'Estudiante sin actividad',
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
        'confirmar_conflicto' => true,
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
    comprobar(
        $roles->forUser($estudianteId)['nombre_rol'] === 'docente',
        'el rol debe poder cambiarse y consultarse.'
    );

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
} catch (Throwable $error) {
    $ultimoResultado = $resultadosPruebas === []
        ? null
        : $resultadosPruebas[count($resultadosPruebas) - 1];

    if ($ultimoResultado === null || $ultimoResultado['error'] !== $error->getMessage()) {
        $numeroPrueba = count($resultadosPruebas) + 1;
        $resultadosPruebas[] = [
            'id' => sprintf('TC-%03d', $numeroPrueba),
            'estado' => 'FAIL',
            'nombre' => 'Error no controlado durante la ejecución de la suite.',
            'esperado' => 'La suite debe completar su ejecución sin errores.',
            'obtenido' => 'Excepción: ' . $error->getMessage(),
            'error' => $error->getMessage(),
        ];
    }

    throw $error;
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
                $nombreInvalidoId ?? null,
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
            $conexion->prepare(
                'DELETE e FROM estudiantes e
                 JOIN usuarios u ON u.usuario_id = e.usuario_id
                 WHERE u.correo = :correo'
            )->execute(['correo' => $correoNombreInvalido]);
            $borrarUsuarios = $conexion->prepare(
                'DELETE FROM usuarios
                 WHERE correo IN (:admin, :estudiante, :docente, :asistente, :coordinador, :rechazado, :otro, :nombre_invalido)'
            );
            $borrarUsuarios->execute([
                'admin' => $correoAdmin,
                'estudiante' => $correoEstudiante,
                'docente' => $correoDocente,
                'asistente' => $correoAsistente,
                'coordinador' => $correoCoordinador,
                'rechazado' => $correoRechazado,
                'otro' => $correoOtro,
                'nombre_invalido' => $correoNombreInvalido,
            ]);
        }
    }

    generarReportesAutomaticos($resultadosPruebas, $fechaInicioPruebas);
}