<?php
declare(strict_types=1);

// Este bloque carga modelos y controladores antes de despachar una ruta.
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Rol.php';
require_once __DIR__ . '/models/Usuario.php';
require_once __DIR__ . '/models/Estudiante.php';
require_once __DIR__ . '/models/SolicitudRegistro.php';
require_once __DIR__ . '/models/Validacion.php';
require_once __DIR__ . '/models/Auditoria.php';
require_once __DIR__ . '/models/Proyecto.php';
require_once __DIR__ . '/models/RegistroHoras.php';
require_once __DIR__ . '/models/PortalSocial.php';
require_once __DIR__ . '/models/Participacion.php';
require_once __DIR__ . '/controllers/HttpController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/PageController.php';
require_once __DIR__ . '/controllers/EstudianteController.php';
require_once __DIR__ . '/controllers/ProyectoController.php';
require_once __DIR__ . '/controllers/HorasController.php';
require_once __DIR__ . '/controllers/PortalController.php';
require_once __DIR__ . '/controllers/WorkflowController.php';

// Este bloque endurece la sesión y evita reutilizar respuestas privadas desde caché.
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
$ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($ruta === '/css/estudiantes.css') {
    header('Content-Type: text/css; charset=UTF-8');
    readfile(__DIR__ . '/Views/css/estudiantes.css');
    exit;
}

// Este bloque comparte la conexión y los servicios HTTP entre controladores.
$conexion = conectarBaseDatos();
$controladorHttp = new HttpController($conexion);
$controladorAcceso = new AuthController($conexion, $controladorHttp);
$controladorAdmin = new AdminController($conexion, $controladorHttp);
$controladorPaginas = new PageController($controladorHttp);
$controladorEstudiantes = new EstudianteController($conexion, $controladorHttp);
$controladorProyectos = new ProyectoController($conexion, $controladorHttp);
$controladorHoras = new HorasController($conexion, $controladorHttp);
$controladorPortal = new PortalController($conexion, $controladorHttp);
$controladorFlujos = new WorkflowController($conexion, $controladorHttp);

// Este bloque impide acceder directamente a vistas y scripts de base de datos.
if (str_starts_with($ruta, '/Views/') || str_starts_with($ruta, '/database/')) {
    http_response_code(404);
    exit('No encontrado.');
}

// Este bloque enlaza cada URL pública con su acción y verifica métodos POST donde corresponde.
match ($ruta) {
    '/', '/login.php' => $controladorPaginas->showLogin(),
    '/auth.php' => $controladorAcceso->login(),
    '/register.php' => $controladorAcceso->register(),
    '/logout.php' => $controladorAcceso->logout(),
    '/dashboard.php' => $controladorPaginas->showDashboard(),
    '/dashboard_admin.php' => $controladorPaginas->showAdminDashboard(),
    '/dashboard_licenciada.php' => $controladorPortal->dashboard(),
    '/calendario_actividades.php' => $controladorPortal->calendar(),
    '/consulta_respuestas.php' => $controladorPortal->responses(),
    '/respuestas_exportar.php' => $controladorPortal->exportResponses(),
    '/crear_plantilla.php' => $controladorPortal->templates(),
    '/mis_solicitudes.php' => $controladorFlujos->myRequests(),
    '/solicitudes_revisar.php' => $_SERVER['REQUEST_METHOD'] === 'POST'
        && $controladorProyectos->isProjectParticipationRequest($_POST['solicitud_id'] ?? null)
            ? $controladorProyectos->reviewParticipationRequest()
            : $controladorFlujos->reviewRequests(),
    '/solicitud_adjunto.php' => $controladorFlujos->requestAttachment(),
    '/evaluaciones.php' => $controladorFlujos->evaluations(),
    '/evaluacion_crear.php' => $controladorFlujos->createEvaluation(),
    '/evaluacion_guardar.php' => $controladorFlujos->saveEvaluation(),
    '/evaluacion_cerrar.php' => $controladorFlujos->closeEvaluation(),
    '/evaluacion_criterio_estado.php' => $controladorFlujos->toggleCriterion(),
    '/asistencia.php' => $controladorFlujos->attendance(),
    '/asistencia_resumen.php' => $controladorFlujos->attendanceReview(),
    '/aviso-privacidad.php' => $controladorFlujos->privacyNotice(),
    '/privacidad_aceptar.php' => $controladorFlujos->acceptPrivacyNotice(),
    '/privacidad.php' => $controladorFlujos->privacySettings(),
    '/diplomas_generar.php' => $controladorPortal->documents('diploma'),
    '/cartas_participacion.php' => $controladorPortal->documents('carta'),
    '/documento_generado.php' => $controladorPortal->printDocuments(),
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
