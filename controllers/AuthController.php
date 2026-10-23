<?php
declare(strict_types=1);

final class AuthController
{
    public function __construct(private PDO $pdo, private HttpController $http)
    {
    }

    public function login(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->http->redirect('/login.php');
        }
        $this->http->verifyCsrfToken();

        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        if ($identifier === '') {
            $errors['identifier'] = 'Este campo es obligatorio.';
        } elseif (str_contains($identifier, '@') && filter_var($identifier, FILTER_VALIDATE_EMAIL) === false) {
            $errors['identifier'] = 'Ingresa un correo válido o tu número de carnet.';
        } elseif (!str_contains($identifier, '@') && preg_match('/^[A-Za-z0-9._-]{3,64}$/', $identifier) !== 1) {
            $errors['identifier'] = 'Ingresa un correo válido o tu número de carnet.';
        }

        if ($password === '') {
            $errors['password'] = 'Este campo es obligatorio.';
        }

        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'login');
            $this->http->redirect('/login.php');
        }

        $user = (new User($this->pdo))->authenticate($identifier, $password);
        if ($user === null) {
            $this->http->rememberErrors(['password' => 'Credenciales inválidas o cuenta desactivada.'], 'login');
            $this->http->redirect('/login.php');
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $role = (new Role($this->pdo))->forUser((int) $user['id']);
        $this->http->redirect($role !== null && $role['name'] === 'admin' ? '/dashboard_admin.php' : '/dashboard.php');
    }

    public function register(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->http->redirect('/login.php');
        }
        $this->http->verifyCsrfToken();

        $fullname = trim((string) ($_POST['fullname'] ?? ''));
        $carnet = trim((string) ($_POST['carnet'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $career = trim((string) ($_POST['career'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $errors = [];

        if ($fullname === '') {
            $errors['fullname'] = 'El nombre completo es obligatorio.';
        }
        if ($carnet === '') {
            $errors['carnet'] = 'El número de carnet es obligatorio.';
        } elseif (preg_match('/^[A-Za-z0-9-]{3,32}$/', $carnet) !== 1) {
            $errors['carnet'] = 'El formato del carnet no es válido.';
        }
        if ($email === '') {
            $errors['email'] = 'El correo institucional es obligatorio.';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Ingresa un correo electrónico válido.';
        }
        if ($career === '') {
            $errors['career'] = 'Selecciona una carrera.';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria.';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres.';
        }

        if ($errors !== []) {
            $this->http->rememberErrors($errors, 'register');
            $this->http->redirect('/login.php');
        }

        try {
            (new User($this->pdo))->create($fullname, $email, $carnet, $password, 'estudiante', true, $career);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            $errors = [];
            if ((new User($this->pdo))->emailExists($email)) {
                $errors['email'] = 'Ya existe una cuenta con este correo.';
            }
            if ((new User($this->pdo))->carnetExists($carnet)) {
                $errors['carnet'] = 'Ya existe una cuenta con este carnet.';
            }
            $this->http->rememberErrors($errors, 'register');
            $this->http->redirect('/login.php');
        }

        $this->http->redirect('/login.php');
    }

    public function logout(): never
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $parameters['path'],
                'domain' => $parameters['domain'],
                'secure' => $parameters['secure'],
                'httponly' => $parameters['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        $this->http->redirect('/login.php');
    }
}
