<?php
declare(strict_types=1);

final class PageController
{
    // Este controlador solo decide que vista mostrar y que permiso revisar.
    public function __construct(private HttpController $http)
    {
    }

    public function showLogin(): void
    {
        if ($this->http->currentUser() !== null) {
            $this->http->redirect($this->http->userCan('panel.administracion') ? '/dashboard_admin.php' : '/dashboard.php');
        }

        $this->http->render('login.php');
    }

    public function showDashboard(): void
    {
        $this->http->requireAuthentication();
        $this->http->render('dashboard.php');
    }

    public function showAdminDashboard(): void
    {
        $this->http->requirePermission('panel.administracion');
        $this->http->render('dashboard_admin.php');
    }

    public function showRoles(): void
    {
        $this->http->requirePermission('usuarios.consultar');
        $this->http->render('roles.php');
    }

    public function showPersonal(): void
    {
        $this->http->requirePermission('usuarios.crear');
        $this->http->render('personal.php');
    }

    public function showStudentForm(): void
    {
        $this->http->requirePermission('estudiantes.crear');
        $this->http->redirect('/estudiantes_nuevo.php');
    }

    public function showStudents(): void { $this->http->requirePermission('estudiantes.consultar'); $this->http->render('estudiantes_listado.php'); }
    public function showStudentCreate(): void { $this->http->requirePermission('estudiantes.crear'); $this->http->render('estudiantes_nuevo.php'); }
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
    public function showStudentDetail(): void { $this->http->requirePermission('estudiantes.consultar'); $this->http->render('estudiantes_detalle.php'); }
    public function showActivityCreate(): void
    {
        $permiso = isset($_GET['id']) ? 'proyectos.actualizar' : 'proyectos.crear';
        $this->http->requirePermission($permiso);
        $this->http->render('cronograma_nuevo.php');
    }
    public function showProjects(): void { $this->http->requirePermission('proyectos.consultar'); $this->http->render('proyectos.php'); }
    public function showCatalog(): void { $this->http->requirePermission('proyectos.catalogo'); $this->http->render('catalogo_proyectos.php'); }
    public function showProjectAssign(): void { $this->http->requirePermission('proyectos.asignar'); $this->http->render('proyectos_asignar.php'); }
    public function showHoursCreate(): void
    {
        $this->http->requireAuthentication();
        if (!$this->http->userCan('horas.registrar') && !$this->http->userCan('horas.registrar_para_estudiantes')) {
            http_response_code(403);
            exit('No tienes permisos para registrar horas.');
        }
        $this->http->render('horas_registrar.php');
    }
    public function showHoursReview(): void { $this->http->requirePermission('horas.revisar'); $this->http->render('horas_bandeja.php'); }
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
