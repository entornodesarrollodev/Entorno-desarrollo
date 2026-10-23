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
            $this->http->redirect('/dashboard.php');
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
        $this->http->render('estudiante.php');
    }
}
