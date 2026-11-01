<?php
declare(strict_types=1);

final class AuthController
{
    public function __construct(private PDO $conexion, private HttpController $http)
    {
    }

    public function login(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->http->redirect('/login.php');
        }
        $this->http->verifyCsrfToken();

        // Los nombres del formulario se quedan como estan en la vista.
        $identificador = trim((string) ($_POST['identifier'] ?? ''));
        $clave = (string) ($_POST['password'] ?? '');
        $errores = [];

        if ($identificador === '') {
            $errores['identifier'] = 'Este campo es obligatorio.';
        } elseif (Validacion::tieneEmoji($identificador)) {
            $errores['identifier'] = 'No se permiten emojis.';
        } elseif (
            str_contains($identificador, '@')
            && filter_var($identificador, FILTER_VALIDATE_EMAIL) === false
        ) {
            $errores['identifier'] = 'Ingresa un correo valido o tu numero de carnet.';
        } elseif (
            str_contains($identificador, '@')
            && !Validacion::correoItcaValido($identificador)
        ) {
            $errores['identifier'] = 'Usa un correo terminado en @itca.edu.sv.';
        } elseif (!str_contains($identificador, '@') && preg_match('/^[A-Za-z0-9._-]{3,64}$/', $identificador) !== 1) {
            $errores['identifier'] = 'Ingresa un correo valido o tu numero de carnet.';
        }

        if ($clave === '') {
            $errores['password'] = 'Este campo es obligatorio.';
        } elseif (Validacion::tieneEmoji($clave)) {
            $errores['password'] = 'No se permiten emojis en la contrasena.';
        }

        if ($errores !== []) {
            $this->http->rememberErrors($errores, 'login');
            $this->http->redirect('/login.php');
        }

        $usuario = (new Usuario($this->conexion))->authenticate($identificador, $clave);
        if ($usuario === null) {
            $mensaje = (new Usuario($this->conexion))->hasPendingRegistration($identificador)
                ? 'Tu solicitud sigue pendiente de aprobacion por el admin o coordinador.'
                : 'Credenciales invalidas o cuenta desactivada.';
            $this->http->rememberErrors(['password' => $mensaje], 'login');
            $this->http->redirect('/login.php');
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $usuario['usuario_id'];
        $rol = (new Rol($this->conexion))->forUser((int) $usuario['usuario_id']);
        $this->http->redirect(
            $rol !== null && in_array($rol['nombre_rol'], ['administrador', 'coordinador'], true)
                ? '/dashboard_admin.php'
                : '/dashboard.php'
        );
    }

    public function register(): never
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->http->redirect('/login.php');
        }
        $this->http->verifyCsrfToken();

        $nombreCompleto = trim((string) ($_POST['fullname'] ?? ''));
        $carnet = trim((string) ($_POST['carnet'] ?? ''));
        $correo = trim((string) ($_POST['email'] ?? ''));
        $carrera = trim((string) ($_POST['career'] ?? ''));
        $anioAcademico = filter_var($_POST['anio_academico'] ?? null, FILTER_VALIDATE_INT);
        $modalidadParticipacion = trim((string) ($_POST['modalidad_participacion'] ?? ''));
        $clave = (string) ($_POST['password'] ?? '');
        $errores = [];

        if ($nombreCompleto === '') {
            $errores['fullname'] = 'El nombre completo es obligatorio.';
        } elseif (Validacion::tieneEmoji($nombreCompleto)) {
            $errores['fullname'] = 'No se permiten emojis en el nombre.';
        }
        if ($carnet === '') {
            $errores['carnet'] = 'El numero de carnet es obligatorio.';
        } elseif (Validacion::tieneEmoji($carnet)) {
            $errores['carnet'] = 'No se permiten emojis en el carnet.';
        } elseif (preg_match('/^[A-Za-z0-9-]{3,32}$/', $carnet) !== 1) {
            $errores['carnet'] = 'El formato del carnet no es valido.';
        }
        if ($correo === '') {
            $errores['email'] = 'El correo institucional es obligatorio.';
        } elseif (Validacion::tieneEmoji($correo)) {
            $errores['email'] = 'No se permiten emojis en el correo.';
        } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'] = 'Ingresa un correo electronico valido.';
        } elseif (!Validacion::correoItcaValido($correo)) {
            $errores['email'] = 'Usa tu correo institucional terminado en @itca.edu.sv.';
        }
        if ($carrera === '') {
            $errores['career'] = 'Selecciona una carrera.';
        } elseif (Validacion::tieneEmoji($carrera)) {
            $errores['career'] = 'No se permiten emojis en la carrera.';
        }
        if (!in_array($anioAcademico, [1, 2], true)) {
            $errores['anio_academico'] = 'Selecciona un año académico válido.';
        }
        if (!in_array($modalidadParticipacion, ['Voluntariado', 'Horas sociales', 'Práctica profesional'], true)) {
            $errores['modalidad_participacion'] = 'Selecciona una modalidad de participación.';
        }
        if ($clave === '') {
            $errores['password'] = 'La contrasena es obligatoria.';
        } elseif (Validacion::tieneEmoji($clave)) {
            $errores['password'] = 'No se permiten emojis en la contrasena.';
        } elseif (strlen($clave) < 6) {
            $errores['password'] = 'La contrasena debe tener al menos 6 caracteres.';
        }

        if ($errores !== []) {
            $this->http->rememberErrors($errores, 'register', [
                'fullname' => $nombreCompleto,
                'carnet' => $carnet,
                'email' => $correo,
                'career' => $carrera,
                'anio_academico' => (string) $anioAcademico,
                'modalidad_participacion' => $modalidadParticipacion,
            ]);
            $this->http->redirect('/login.php');
        }

        try {
            (new Usuario($this->conexion))->create(
                $nombreCompleto,
                $correo,
                $carnet,
                $clave,
                'estudiante',
                false,
                $carrera,
                true,
                (int) $anioAcademico,
                $modalidadParticipacion
            );
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000') {
                throw $error;
            }

            // Le decimos al usuario cual de los dos datos ya estaba registrado.
            $errores = [];
            $usuarioModelo = new Usuario($this->conexion);
            if ($usuarioModelo->emailExists($correo)) {
                $errores['email'] = 'Ya existe una cuenta con este correo.';
            }
            if ($usuarioModelo->carnetExists($carnet)) {
                $errores['carnet'] = 'Ya existe una cuenta con este carnet.';
            }
            $this->http->rememberErrors($errores, 'register', [
                'fullname' => $nombreCompleto,
                'carnet' => $carnet,
                'email' => $correo,
                'career' => $carrera,
                'anio_academico' => (string) $anioAcademico,
                'modalidad_participacion' => $modalidadParticipacion,
            ]);
            $this->http->redirect('/login.php');
        }

        $this->http->rememberErrors(
            ['identifier' => 'Tu solicitud fue enviada. Podras iniciar sesion cuando el admin o coordinador la apruebe.'],
            'login'
        );
        $this->http->redirect('/login.php');
    }

    public function logout(): never
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $opcionesCookie = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $opcionesCookie['path'],
                'domain' => $opcionesCookie['domain'],
                'secure' => $opcionesCookie['secure'],
                'httponly' => $opcionesCookie['httponly'],
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
        $this->http->redirect('/login.php');
    }
}
