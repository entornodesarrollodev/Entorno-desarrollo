<?php
declare(strict_types=1);

final class AdminController
{
    public function __construct(private PDO $conexion, private HttpController $http)
    {
    }

    public function saveStaff(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('usuarios.crear');
        $nombre = trim((string) ($_POST['nombre'] ?? ''));
        $correo = trim((string) ($_POST['correo'] ?? ''));
        $nombreRol = (string) ($_POST['rol'] ?? '');
        $clave = (string) ($_POST['password'] ?? '');
        $activo = isset($_POST['estado']);
        $errores = $this->accountErrors($nombre, $correo, $clave);
        if ($nombre !== '' && Validacion::tieneEmoji($nombre)) {
            $errores['nombre'] = 'No se permiten emojis en el nombre.';
        }

        // La vista todavia envia "admin", pero en la base el rol se llama "administrador".
        if ($nombreRol === 'admin') {
            $nombreRol = 'administrador';
        }
        $rolModelo = new Rol($this->conexion);

        if ($rolModelo->findByName($nombreRol) === null || $nombreRol === 'estudiante') {
            $errores['rol'] = 'Selecciona un rol valido para personal.';
        }

        if ($errores !== []) {
            $this->http->rememberErrors($errores, 'staff');
            $this->http->redirect('/personal.php');
        }

        try {
            $usuarioId = (new Usuario($this->conexion))->create($nombre, $correo, null, $clave, $nombreRol, $activo);
            (new Auditoria($this->conexion))->registrar((int)$_SESSION['usuario_id'], 'usuario', $usuarioId, 'CREAR', null, ['nombre'=>$nombre, 'correo'=>$correo, 'rol'=>$nombreRol, 'activo'=>$activo]);
        } catch (PDOException $error) {
            if ($error->getCode() !== '23000') {
                throw $error;
            }
            $this->http->rememberErrors(['correo' => 'Ya existe una cuenta con este correo.'], 'staff');
            $this->http->redirect('/personal.php');
        }

        $this->http->redirect('/roles.php');
    }

    public function assignRole(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('usuarios.gestionar_roles');
        $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $rolId = filter_var($_POST['rol_id'] ?? null, FILTER_VALIDATE_INT);

        if (!$usuarioId || !$rolId) {
            $this->http->rememberErrors(['acceso' => 'La cuenta y el rol seleccionados no son validos.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        $usuario = (new Usuario($this->conexion))->findById($usuarioId);
        $consultaRol = $this->conexion->prepare('SELECT rol_id, nombre_rol FROM roles WHERE rol_id = :rol_id');
        $consultaRol->execute(['rol_id' => $rolId]);
        $rolSeleccionado = $consultaRol->fetch();

        if ($usuario === null || $rolSeleccionado === false) {
            $this->http->rememberErrors(['acceso' => 'La cuenta o el rol seleccionado no existe.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        $rolModelo = new Rol($this->conexion);
        $rolActual = $rolModelo->forUser($usuarioId);
        if (
            $rolActual !== null
            && $rolActual['nombre_rol'] === 'administrador'
            && $rolSeleccionado['nombre_rol'] !== 'administrador'
            && $this->contarAdministradoresActivos() <= 1
        ) {
            $this->http->rememberErrors(
                ['acceso' => 'No se puede quitar el rol al ultimo administrador activo.'],
                'roles'
            );
            $this->http->redirect('/roles.php');
        }

        $rolModelo->assign($usuarioId, $rolId);
        (new Auditoria($this->conexion))->registrar((int)$_SESSION['usuario_id'], 'usuario', $usuarioId, 'ACTUALIZAR', $rolActual, $rolSeleccionado);
        $this->http->redirect('/roles.php');
    }

    public function toggleStatus(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $this->http->requirePermission('usuarios.gestionar_estado');
        $usuarioId = filter_var($_POST['usuario_id'] ?? null, FILTER_VALIDATE_INT);
        $activoEntrada = filter_var($_POST['activo'] ?? null, FILTER_VALIDATE_INT);

        if (!$usuarioId || !in_array($activoEntrada, [0, 1], true)) {
            $this->http->rememberErrors(['acceso' => 'Los datos del estado de cuenta no son validos.'], 'roles');
            $this->http->redirect('/roles.php');
        }
        $activo = (int) $activoEntrada;
        $usuarioModelo = new Usuario($this->conexion);
        if ($usuarioModelo->findById($usuarioId) === null) {
            $this->http->rememberErrors(['acceso' => 'La cuenta seleccionada no existe.'], 'roles');
            $this->http->redirect('/roles.php');
        }
        if ($activo === 1 && (new SolicitudRegistro($this->conexion))->tieneSolicitudSinAceptar($usuarioId)) {
            $this->http->rememberErrors(
                ['acceso' => 'Esta solicitud debe aceptarse desde el panel antes de activar la cuenta.'],
                'roles'
            );
            $this->http->redirect('/roles.php');
        }
        if (isset($_SESSION['usuario_id']) && (int) $_SESSION['usuario_id'] === $usuarioId && $activo === 0) {
            $this->http->rememberErrors(['acceso' => 'No puedes desactivar tu propia cuenta.'], 'roles');
            $this->http->redirect('/roles.php');
        }

        if (
            $activo === 0
            && $this->esAdministradorActivo($usuarioId)
            && $this->contarAdministradoresActivos() <= 1
        ) {
            $this->http->rememberErrors(
                ['acceso' => 'No se puede desactivar la ultima cuenta administradora activa.'],
                'roles'
            );
            $this->http->redirect('/roles.php');
        }

        $usuarioAnterior = $usuarioModelo->findById($usuarioId);
        $usuarioModelo->setActive($usuarioId, $activo === 1);
        (new Auditoria($this->conexion))->registrar((int)$_SESSION['usuario_id'], 'usuario', $usuarioId, 'CAMBIAR_ESTADO', $usuarioAnterior, ['estado_activo'=>$activo]);
        $this->http->redirect('/roles.php');
    }

    public function reviewRegistration(): never
    {
        $this->requireMethod('POST');
        $this->http->verifyCsrfToken();
        $revisor = $this->http->requirePermission('solicitudes.revisar');
        $solicitudId = filter_var($_POST['solicitud_id'] ?? null, FILTER_VALIDATE_INT);
        $decisionEntrada = (string) ($_POST['decision'] ?? '');
        $decision = match ($decisionEntrada) {
            'aceptar' => 'aceptada',
            'rechazar' => 'rechazada',
            default => '',
        };

        if (!$solicitudId || $decision === '') {
            $this->http->rememberErrors(['acceso' => 'La solicitud o la decision no son validas.'], 'dashboard');
            $this->http->redirect('/dashboard_admin.php');
        }

        $resuelta = (new SolicitudRegistro($this->conexion))->resolver(
            $solicitudId,
            (int) $revisor['usuario_id'],
            $decision
        );
        if ($resuelta) {
            (new Auditoria($this->conexion))->registrar((int)$revisor['usuario_id'], 'solicitud_registro', (int)$solicitudId, 'ACTUALIZAR', null, ['decision'=>$decision]);
        }
        if (!$resuelta) {
            $this->http->rememberErrors(
                ['acceso' => 'Esta solicitud ya fue revisada o ya no existe.'],
                'dashboard'
            );
        }

        $this->http->redirect('/dashboard_admin.php');
    }

    private function accountErrors(string $nombre, string $correo, string $clave): array
    {
        $errores = [];
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre completo es obligatorio.';
        } elseif (Validacion::tieneEmoji($nombre)) {
            $errores['nombre'] = 'No se permiten emojis en el nombre.';
        }
        if ($correo === '') {
            $errores['correo'] = 'El correo institucional es obligatorio.';
        } elseif (Validacion::tieneEmoji($correo)) {
            $errores['correo'] = 'No se permiten emojis en el correo.';
        } elseif (filter_var($correo, FILTER_VALIDATE_EMAIL) === false) {
            $errores['correo'] = 'Ingresa un correo electronico valido.';
        } elseif (!Validacion::correoItcaValido($correo)) {
            $errores['correo'] = 'Usa tu correo institucional terminado en @itca.edu.sv.';
        }
        if ($clave === '') {
            $errores['password'] = 'La contrasena es obligatoria.';
        } elseif (Validacion::tieneEmoji($clave)) {
            $errores['password'] = 'No se permiten emojis en la contrasena.';
        } elseif (strlen($clave) < 6) {
            $errores['password'] = 'La contrasena debe tener al menos 6 caracteres.';
        }

        return $errores;
    }

    private function requireMethod(string $metodo): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== $metodo) {
            http_response_code(405);
            header('Allow: ' . $metodo);
            exit;
        }
    }

    private function contarAdministradoresActivos(): int
    {
        return (int) $this->conexion->query(
            "SELECT COUNT(*)
             FROM usuarios u
             JOIN usuarios_roles ur ON ur.usuario_id = u.usuario_id
             JOIN roles r ON r.rol_id = ur.rol_id
             WHERE u.estado_activo = 1 AND r.nombre_rol = 'administrador'"
        )->fetchColumn();
    }

    private function esAdministradorActivo(int $usuarioId): bool
    {
        $consulta = $this->conexion->prepare(
            "SELECT 1
             FROM usuarios u
             JOIN usuarios_roles ur ON ur.usuario_id = u.usuario_id
             JOIN roles r ON r.rol_id = ur.rol_id
             WHERE u.usuario_id = :usuario_id
               AND u.estado_activo = 1
               AND r.nombre_rol = 'administrador'"
        );
        $consulta->execute(['usuario_id' => $usuarioId]);

        return $consulta->fetchColumn() !== false;
    }
}
