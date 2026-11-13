<?php
declare(strict_types=1);

final class AuthController
{
    // Este bloque recibe la conexión y centraliza las respuestas de autenticación.
    public function __construct(private PDO $conexion, private HttpController $http)
    {
    }

    // Este bloque autentica la cuenta, comprueba su estado y establece la sesión.
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

    // Este bloque valida el registro público y registra el consentimiento vigente cuando aplica.
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
        $privacyNotice = (new Participacion($this->conexion))->currentPrivacyNotice();
        $acceptedNoticeId = filter_var($_POST['privacy_notice_id'] ?? null, FILTER_VALIDATE_INT);
        $clave = (string) ($_POST['password'] ?? '');
        $errores = [];

        if ($nombreCompleto === '') {
            $errores['fullname'] = 'El nombre completo es obligatorio.';
        } elseif (!Validacion::nombreCompletoValido($nombreCompleto)) {
            $errores['fullname'] = 'Ingresa al menos un nombre y un apellido.';
        } elseif (mb_strlen($nombreCompleto) > 150) {
            $errores['fullname'] = 'El nombre completo no debe exceder 150 caracteres.';
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
        } elseif (strlen($correo) > 254 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['email'] = 'Ingresa un correo electronico valido.';
        } elseif (!Validacion::correoItcaValido($correo)) {
            $errores['email'] = 'Usa tu correo institucional terminado en @itca.edu.sv.';
        }
        if ($carrera === '') {
            $errores['career'] = 'Selecciona una carrera.';
        } elseif (!Validacion::carreraRegistroValida($carrera)) {
            $errores['career'] = 'Selecciona una de las carreras disponibles.';
        }
        if (!in_array($anioAcademico, [1, 2], true)) {
            $errores['anio_academico'] = 'Selecciona un año académico válido.';
        }
        if (!in_array($modalidadParticipacion, ['Voluntariado', 'Horas sociales', 'Práctica profesional'], true)) {
            $errores['modalidad_participacion'] = 'Selecciona una modalidad de participación.';
        }
        if ($privacyNotice !== null && (int) $privacyNotice['requiere_aceptacion'] === 1
            && (($_POST['privacy_accept'] ?? '') !== '1' || $acceptedNoticeId !== (int) $privacyNotice['aviso_id'])) {
            $errores['privacy_accept'] = 'Debes revisar y aceptar el aviso de privacidad vigente.';
        }
        if ($clave === '') {
            $errores['password'] = 'La contrasena es obligatoria.';
        } elseif (Validacion::tieneEmoji($clave)) {
            $errores['password'] = 'No se permiten emojis en la contrasena.';
        } elseif (strlen($clave) < 6 || strlen($clave) > 72) {
            $errores['password'] = 'La contrasena debe tener entre 6 y 72 caracteres.';
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
            $userId = (new Usuario($this->conexion))->create(
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
            if (
                $privacyNotice !== null
                && (int) $privacyNotice['requiere_aceptacion'] === 1
                && ($_POST['privacy_accept'] ?? '') === '1'
                && $acceptedNoticeId === (int) $privacyNotice['aviso_id']
            ) {
                (new Participacion($this->conexion))->acceptPrivacyNotice(
                    $userId,
                    (int) $privacyNotice['aviso_id']
                );
            }
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

    // Este bloque invalida la sesión actual y devuelve al formulario de acceso.
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
