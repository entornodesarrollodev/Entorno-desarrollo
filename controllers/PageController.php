<?php
declare(strict_types=1);

final class PageController
{
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
        $this->http->requirePermission('dashboard.admin');
        $this->http->render('dashboard_admin.php');
    }

    public function showRoles(): void
    {
        $this->http->requirePermission('users.view');
        $this->http->render('roles.php');
    }

    public function showPersonal(): void
    {
        $this->http->requirePermission('users.create');
        $this->http->render('personal.php');
    }

    public function showStudentForm(): void
    {
        $this->http->requirePermission('students.create');
        $this->http->render('estudiante.php');
    }
}
