<?php
declare(strict_types=1);

final class PageController
{
    // Este controlador solo decide que vista mostrar y que permiso revisar.
    public function __construct(private HttpController $http)
    {
    }

    // Este bloque muestra el acceso o redirige al panel de una sesión existente.
    public function showLogin(): void
    {
        if ($this->http->currentUser() !== null) {
            $this->http->redirect($this->http->userCan('panel.administracion') ? '/dashboard_admin.php' : '/dashboard.php');
        }

        $this->http->render('login.php');
    }

    // Este bloque exige autenticación antes de mostrar el panel general.
    public function showDashboard(): void
    {
        $this->http->requireAuthentication();
        $this->http->render('dashboard.php');
    }

    // Este bloque protege el panel administrativo con su permiso específico.
    public function showAdminDashboard(): void
    {
        $this->http->requirePermission('panel.administracion');
        $this->http->render('dashboard_admin.php');
    }

    // Este bloque restringe la gestión de roles a quienes pueden consultar usuarios.
    public function showRoles(): void
    {
        $this->http->requirePermission('usuarios.consultar');
        $this->http->render('roles.php');
    }

    // Este bloque protege la pantalla de creación de personal.
    public function showPersonal(): void
    {
        $this->http->requirePermission('usuarios.crear');
        $this->http->render('personal.php');
    }

    // Este bloque redirige la ruta antigua al formulario canónico de alta.
    public function showStudentForm(): void
    {
        $this->http->requirePermission('estudiantes.crear');
        $this->http->redirect('/estudiantes_nuevo.php');
    }

    // Este bloque muestra el listado de expedientes tras comprobar el permiso.
    public function showStudents(): void { $this->http->requirePermission('estudiantes.consultar'); $this->http->render('estudiantes_listado.php'); }
    // Este bloque muestra el formulario de alta solo a personal autorizado.
    public function showStudentCreate(): void { $this->http->requirePermission('estudiantes.crear'); $this->http->render('estudiantes_nuevo.php'); }

    // Este bloque permite editar el expediente propio o cualquier expediente con permiso administrativo.
    public function showStudentEdit(): void
    {
        $usuario = $this->http->requireAuthentication();
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $puedeEditarTodos = $this->http->userCan('estudiantes.actualizar');
        $puedeEditarPropio = $this->http->userCan('estudiantes.actualizar_propios')
            && $id !== false
            && $id !== null
            && $this->http->userOwnsStudent((int) $id, (int) $usuario['usuario_id']);
        if (!$puedeEditarTodos && !$puedeEditarPropio) {
            http_response_code(403);
            exit('No tienes permisos para editar este expediente.');
        }
        $this->http->render('estudiantes_editar.php');
    }
    // Este bloque muestra información personal únicamente a quienes consultan expedientes.
    public function showStudentDetail(): void { $this->http->requirePermission('estudiantes.consultar'); $this->http->render('estudiantes_detalle.php'); }

    // Este bloque distingue el permiso de edición del permiso para crear cronogramas.
    public function showActivityCreate(): void
    {
        $permiso = isset($_GET['id']) ? 'proyectos.actualizar' : 'proyectos.crear';
        $this->http->requirePermission($permiso);
        $this->http->render('cronograma_nuevo.php');
    }
    // Este bloque muestra el listado de proyectos con su permiso de consulta.
    public function showProjects(): void { $this->http->requirePermission('proyectos.consultar'); $this->http->render('proyectos.php'); }
    // Este bloque muestra el catálogo público de proyectos activos al perfil autorizado.
    public function showCatalog(): void { $this->http->requirePermission('proyectos.catalogo'); $this->http->render('catalogo_proyectos.php'); }
    // Este bloque protege la gestión de asignaciones de estudiantes.
    public function showProjectAssign(): void { $this->http->requirePermission('proyectos.asignar'); $this->http->render('proyectos_asignar.php'); }
    // Este bloque permite registrar horas solo a estudiantes y personal autorizado.
    public function showHoursCreate(): void
    {
        $this->http->requireAuthentication();
        if (!$this->http->userCan('horas.registrar') && !$this->http->userCan('horas.registrar_para_estudiantes')) {
            http_response_code(403);
            exit('No tienes permisos para registrar horas.');
        }
        $this->http->render('horas_registrar.php');
    }
    // Este bloque muestra la bandeja únicamente a revisores de horas.
    public function showHoursReview(): void { $this->http->requirePermission('horas.revisar'); $this->http->render('horas_bandeja.php'); }
    // Este bloque confirma que la persona tiene un expediente propio para consultar horas.
    public function showHoursSummary(): void
    {
        $usuario = $this->http->requirePermission('horas.registrar');
        if ($this->http->studentIdForUser((int) $usuario['usuario_id']) === null) {
            http_response_code(403);
            exit('El resumen de horas personales requiere un expediente activo asociado.');
        }
        $this->http->render('estudiantes_resumen_horas.php');
    }
}
