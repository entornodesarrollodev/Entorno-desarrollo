<?php
declare(strict_types=1);

final class AdminController
{
    public function __construct(private PDO $pdo, private HttpController $http)
    {
    }

    public function saveStaff(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('users.create');
        $name = trim((string) ($_POST['nombre'] ?? ''));
        $email = trim((string) ($_POST['correo'] ?? ''));
        $role = (string) ($_POST['rol'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $active = isset($_POST['estado']);
        $errors = $this->accountErrors($name, $email, $password);
        $roleModel = new Role($this->pdo);

        if ($roleModel->findByName($role) === null || $role === 'estudiante') {
            $errors['rol'] = 'Selecciona un rol válido para personal.';
        }

        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'staff');
            $this->http->redirect('/personal.php');
        }

        try {
            (new User($this->pdo))->create($name, $email, null, $password, $role, $active);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $this->http->rememberErrors(['correo' => 'Ya existe una cuenta con este correo.'], 'staff');
            $this->http->redirect('/personal.php');
        }

        $this->http->redirect('/roles.php');
    }

    public function saveStudent(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('students.create');
        $name = trim((string) ($_POST['fullname'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $career = trim((string) ($_POST['career'] ?? ''));
        $yearInput = (string) ($_POST['year'] ?? '');
        $errors = [];

        if ($name === '') {
            $errors['fullname'] = 'El nombre completo es obligatorio.';
        }
        if ($email === '') {
            $errors['email'] = 'El correo institucional es obligatorio.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Ingresa un correo electrónico válido.';
        }
        if ($career === '') {
            $errors['career'] = 'Selecciona una carrera.';
        }
        if (!in_array($yearInput, ['1', '2'], true)) {
            $errors['year'] = 'Selecciona un año académico válido.';
        }

        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'student');
            $this->http->redirect('/estudiante.php');
        }

        try {
            (new Student($this->pdo))->create($name, $email, $phone !== '' ? $phone : null, $career, (int) $yearInput);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $this->http->rememberErrors(['email' => 'Ya existe un estudiante con este correo.'], 'student');
            $this->http->redirect('/estudiante.php');
        }

        $this->http->redirect('/dashboard_admin.php');
    }

    public function assignRole(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('users.manage_roles');
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $roleId = filter_var($_POST['role_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId || !$roleId) {
            $this->http->rememberErrors(['access' => 'La cuenta y el rol seleccionados no son válidos.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        $user = (new User($this->pdo))->findById($userId);
        $role = $this->pdo->prepare('SELECT id, name FROM roles WHERE id = :id');
        $role->execute(['id' => $roleId]);
        $selectedRole = $role->fetch();

        if ($user === null || $selectedRole === false) {
            $this->http->rememberErrors(['access' => 'La cuenta o el rol seleccionado no existe.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        $roleModel = new Role($this->pdo);
        $currentRole = $roleModel->forUser($userId);
        if (
            $currentRole !== null
            && $currentRole['name'] === 'admin'
            && $selectedRole !== null
            && $selectedRole['name'] !== 'admin'
            && $this->activeAdministratorCount() <= 1
        ) {
            $this->http->rememberErrors(['access' => 'No se puede quitar el rol al último administrador activo.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        $roleModel->assign($userId, $roleId);
        $this->http->redirect('/roles.php');
    }

    public function toggleStatus(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('users.manage_status');
        $userId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
        $activeInput = filter_var($_POST['active'] ?? null, FILTER_VALIDATE_INT);

        if (!$userId || !in_array($activeInput, [0, 1], true)) {
            $this->http->rememberErrors(['access' => 'Los datos de estado de cuenta no son válidos.'], 'roles');
            $this->http->redirect('/roles.php');
        }
        $active = (int) $activeInput;
        if ((new User($this->pdo))->findById($userId) === null) {
            $this->http->rememberErrors(['access' => 'La cuenta seleccionada no existe.'], 'roles');
            $this->http->redirect('/roles.php');
        }
        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] === $userId && $active === 0) {
            $this->http->rememberErrors(['access' => 'No puedes desactivar tu propia cuenta.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        if ($active === 0 && $this->isActiveAdministrator($userId) && $this->activeAdministratorCount() <= 1) {
            $this->http->rememberErrors(['access' => 'No se puede desactivar la última cuenta administradora activa.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        (new User($this->pdo))->setActive($userId, $active === 1);
        $this->http->redirect('/roles.php');
    }

    private function accountErrors(string $name, string $email, string $password): array
    {
        $errors = [];
        if ($name === '') {
            $errors['nombre'] = 'El nombre completo es obligatorio.';
        }
        if ($email === '') {
            $errors['correo'] = 'El correo institucional es obligatorio.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['correo'] = 'Ingresa un correo electrónico válido.';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        return $errors;
    }

    private function requireMethod(string $method): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            http_response_code(405);
            header('Allow: ' . $method);
            exit;
        }
    }

    private function activeAdministratorCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.active = 1 AND r.name = 'admin'"
        )->fetchColumn();
    }

    private function isActiveAdministrator(int $userId): bool
    {
        $statement = $this->pdo->prepare(
            "SELECT 1 FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.id = :id AND u.active = 1 AND r.name = 'admin'"
        );
        $statement->execute(['id' => $userId]);

        return $statement->fetchColumn() !== false;
    }
}
