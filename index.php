<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Rol.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Estudiante.php';
require_once __DIR__ . '/models/SolicitudRegistro.php';
require_once __DIR__ . '/models/Validacion.php';
require_once __DIR__ . '/models/Auditoria.php';
require_once __DIR__ . '/models/Proyecto.php';
require_once __DIR__ . '/models/RegistroHoras.php';
require_once __DIR__ . '/controllers/HttpController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/EstudianteController.php';
require_once __DIR__ . '/controllers/ProyectoController.php';
require_once __DIR__ . '/controllers/HorasController.php';

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
]);
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Aqui se conectan las piezas y cada ruta va al controlador que le toca.
$conexion = conectarBaseDatos();
$controladorHttp = new HttpController($conexion);
$controladorAcceso = new AuthController($conexion, $controladorHttp);
$controladorAdmin = new AdminController($conexion, $controladorHttp);
$controladorPaginas = new PageController($controladorHttp);
$controladorEstudiantes = new EstudianteController($conexion, $controladorHttp);
$controladorProyectos = new ProyectoController($conexion, $controladorHttp);
$controladorHoras = new HorasController($conexion, $controladorHttp);
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($ruta, '/Views/') || str_starts_with($ruta, '/database/')) {
    http_response_code(404);
    exit('No encontrado.');
}

// Las vistas conservan sus referencias relativas a css/; se sirve solo el CSS público del sistema.
if ($ruta === '/css/estudiantes.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/Views/css/estudiantes.css');
    exit;
}

match ($ruta) {
    '/', '/login.php' => $controladorPaginas->showLogin(),
    '/auth.php' => $controladorAcceso->login(),
    '/register.php' => $controladorAcceso->register(),
    '/logout.php' => $controladorAcceso->logout(),
    '/dashboard.php' => $controladorPaginas->showDashboard(),
    '/dashboard_admin.php' => $controladorPaginas->showAdminDashboard(),
    '/roles.php' => $controladorPaginas->showRoles(),
    '/personal.php' => $controladorPaginas->showPersonal(),
    '/estudiante.php' => $controladorPaginas->showStudentForm(),
    '/estudiantes_listado.php' => $controladorPaginas->showStudents(),
    '/estudiantes_nuevo.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorEstudiantes->save() : $controladorPaginas->showStudentCreate(),
    '/estudiantes_editar.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorEstudiantes->update() : $controladorPaginas->showStudentEdit(),
    '/estudiantes_detalle.php' => $controladorPaginas->showStudentDetail(),
    '/estudiantes_estado.php' => $controladorEstudiantes->toggle(),
    '/cronograma_nuevo.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorProyectos->activity() : $controladorPaginas->showActivityCreate(),
    '/cronograma.php' => $controladorPaginas->showProjects(),
    '/proyectos.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorProyectos->create() : $controladorPaginas->showProjects(),
    '/catalogo_proyectos.php' => $controladorPaginas->showCatalog(),
    '/proyectos_listado.php' => $controladorPaginas->showProjects(),
    '/proyectos_actualizar.php' => $controladorProyectos->update(),
    '/proyectos_estado.php' => $controladorProyectos->toggle(),
    '/proyectos_asignar.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorProyectos->assign() : $controladorPaginas->showProjectAssign(),
    '/proyectos_desasignar.php' => $controladorProyectos->unassign(),
    '/horas_registrar.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorHoras->save() : $controladorPaginas->showHoursCreate(),
    '/horas_bandeja.php' => $_SERVER['REQUEST_METHOD'] === 'POST' ? $controladorHoras->review() : $controladorPaginas->showHoursReview(),
    '/estudiantes_resumen_horas.php' => $controladorPaginas->showHoursSummary(),
    '/guardar_personal.php' => $controladorAdmin->saveStaff(),
    '/admin/asignar-rol' => $controladorAdmin->assignRole(),
    '/admin/cambiar-estado' => $controladorAdmin->toggleStatus(),
    '/admin/revisar-solicitud' => $controladorAdmin->reviewRegistration(),
    default => http_response_code(404),
};
