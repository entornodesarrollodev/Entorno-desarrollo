<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/models/Rol.php';
require_once dirname(__DIR__) . '/models/Usuario.php';
require_once dirname(__DIR__) . '/models/Estudiante.php';
require_once dirname(__DIR__) . '/models/SolicitudRegistro.php';
require_once dirname(__DIR__) . '/models/Validacion.php';
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
            && $roles->userHasPermission($coordinadorId, 'solicitudes.revisar'),
        'el coordinador debe ver el panel y revisar solicitudes.'
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
    $estudiantes->create('Estudiante de prueba', $correoEstudiante, '0000-0000', 'software', 1);
    $consultaEstudiante = $conexion->prepare(
        'SELECT usuario_id FROM estudiantes WHERE correo = :correo'
    );
    $consultaEstudiante->execute(['correo' => $correoEstudiante]);
    comprobar(
        (int) $consultaEstudiante->fetchColumn() === $estudianteId,
        'la ficha debe quedar ligada a su cuenta.'
    );

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

    echo "OK: MySQL, acceso, solicitudes, decisiones, roles y estado de cuentas.\n";
} finally {
    if ($conexion instanceof PDO) {
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
