<?php
declare(strict_types=1);

final class HttpController
{
    public function __construct(private PDO $conexion)
    {
    }

    public function redirect(string $ruta): never
    {
        header('Location: ' . $ruta, true, 303);
        exit;
    }

    public function rememberErrors(array $errores, string $formulario = ''): void
    {
        $_SESSION['_errores_formulario'] = $errores;
        $_SESSION['_nombre_formulario'] = $formulario;
    }

    public function render(string $vista): void
    {
        $vistasPermitidas = ['login.php', 'dashboard.php', 'dashboard_admin.php', 'roles.php', 'personal.php', 'estudiante.php'];

        if (!in_array($vista, $vistasPermitidas, true)) {
            throw new RuntimeException('Vista no permitida.');
        }

        $rutaVista = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $vista;
        ob_start();
        require $rutaVista;
        $contenidoHtml = (string) ob_get_clean();
        $errores = $_SESSION['_errores_formulario'] ?? [];
        $formulario = $_SESSION['_nombre_formulario'] ?? '';
        unset($_SESSION['_errores_formulario'], $_SESSION['_nombre_formulario']);

        foreach ($errores as $campo => $mensaje) {
            $contenidoHtml = $this->insertFieldError($contenidoHtml, (string) $campo, (string) $mensaje);
        }

        if (isset($errores['acceso'])) {
            $aviso = '<p role="alert" style="padding:1rem;color:#991b1b;background:#fee2e2">'
                . htmlspecialchars((string) $errores['acceso'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p>';
            $contenidoHtml = str_replace('</body>', $aviso . '</body>', $contenidoHtml);
        }

        if ($vista === 'login.php' && $formulario === 'register') {
            $contenidoHtml = str_replace(
                '</body>',
                '<script>switchTab("register");</script></body>',
                $contenidoHtml
            );
        }

        if ($vista === 'roles.php') {
            $contenidoHtml = $this->populateRoles($contenidoHtml);
        }

        if ($vista === 'dashboard_admin.php') {
            $contenidoHtml = $this->populateDashboard($contenidoHtml);
            $contenidoHtml = $this->insertarSolicitudes($contenidoHtml);
        }

        // Agregamos seguridad a los formularios sin editar los archivos de Views.
        $tokenCsrf = $_SESSION['_token_csrf'] ??= bin2hex(random_bytes(32));
        $tokenEscapado = htmlspecialchars($tokenCsrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $contenidoHtml = preg_replace_callback(
            '~<form\b(?=[^>]*\bmethod="POST")[^>]*>~i',
            static fn (array $coincidencias): string => $coincidencias[0]
                . '<input type="hidden" name="token_csrf" value="' . $tokenEscapado . '">',
            $contenidoHtml
        ) ?? $contenidoHtml;

        echo $contenidoHtml;
    }

    public function verifyCsrfToken(): void
    {
        $tokenEsperado = $_SESSION['_token_csrf'] ?? '';
        $tokenRecibido = (string) ($_POST['token_csrf'] ?? '');

        if ($tokenEsperado === '' || $tokenRecibido === '' || !hash_equals($tokenEsperado, $tokenRecibido)) {
            http_response_code(400);
            exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
        }
    }

    public function currentUser(): ?array
    {
        $usuarioId = $_SESSION['usuario_id'] ?? null;

        if (!is_int($usuarioId)) {
            return null;
        }

        $usuario = (new Usuario($this->conexion))->findActiveById($usuarioId);
        if ($usuario === null) {
            unset($_SESSION['usuario_id']);
            session_regenerate_id(true);
        }

        return $usuario;
    }

    public function requireAuthentication(): array
    {
        $usuario = $this->currentUser();
        if ($usuario === null) {
            $this->redirect('/login.php');
        }

        return $usuario;
    }

    public function requirePermission(string $permiso): array
    {
        $usuario = $this->requireAuthentication();
        if (!(new Rol($this->conexion))->userHasPermission((int) $usuario['usuario_id'], $permiso)) {
            http_response_code(403);
            exit('No tienes permisos para realizar esta acción.');
        }

        return $usuario;
    }

    public function insertFieldError(string $contenidoHtml, string $campo, string $mensaje): string
    {
        // Buscamos el control por su name y ponemos el aviso justo debajo.
        $nombreEscapado = preg_quote($campo, '~');
        $error = '<small class="field-error" role="alert" style="display:block;color:#dc2626;margin-top:.25rem">'
            . htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</small>';

        $patronEntrada = '~(<input\b(?=[^>]*\bname="' . $nombreEscapado . '")[^>]*>)~i';
        if (preg_match($patronEntrada, $contenidoHtml) === 1) {
            return preg_replace($patronEntrada, '$1' . $error, $contenidoHtml, 1) ?? $contenidoHtml;
        }

        $patronLista = '~(<select\b(?=[^>]*\bname="' . $nombreEscapado . '")[^>]*>.*?</select>)~is';

        return preg_replace($patronLista, '$1' . $error, $contenidoHtml, 1) ?? $contenidoHtml;
    }

    private function populateRoles(string $contenidoHtml): string
    {
        $usuarios = (new Usuario($this->conexion))->allWithRoles();
        $roles = (new Rol($this->conexion))->all();
        $filas = '';

        foreach ($usuarios as $usuario) {
            $usuarioId = (int) $usuario['usuario_id'];
            $nombre = htmlspecialchars($usuario['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $correo = htmlspecialchars($usuario['correo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $etiquetaRol = htmlspecialchars($usuario['etiqueta'] ?? 'Sin rol', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $nombreRolVista = $usuario['nombre_rol'] ?? 'estudiante';
            $claseInsignia = $nombreRolVista === 'administrador' ? 'admin' : $nombreRolVista;
            $claseInsignia = htmlspecialchars($claseInsignia, ENT_QUOTES, 'UTF-8');
            $filas .= '<tr><td>' . $nombre . '</td><td>' . $correo . '</td><td><span class="badge '
                . $claseInsignia . '">' . $etiquetaRol . '</span></td><td>';
            $filas .= '<form action="/admin/asignar-rol" method="POST" style="display:inline">'
                . '<input type="hidden" name="usuario_id" value="' . $usuarioId . '"><select name="rol_id" required>';

            foreach ($roles as $rol) {
                $seleccionado = (int) $rol['rol_id'] === (int) $usuario['rol_id'] ? ' selected' : '';
                $etiqueta = htmlspecialchars($rol['etiqueta'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $filas .= '<option value="' . (int) $rol['rol_id'] . '"' . $seleccionado . '>' . $etiqueta . '</option>';
            }

            $filas .= '</select><button class="btn-action btn-edit" type="submit">Editar rol</button></form> ';
            $etiquetaEstado = (int) $usuario['estado_activo'] === 1 ? 'Desactivar' : 'Activar';
            $confirmacion = (int) $usuario['estado_activo'] === 1
                ? ' onsubmit="return confirm(\'¿Confirmas que deseas desactivar esta cuenta?\')"'
                : '';
            $filas .= '<form action="/admin/cambiar-estado" method="POST" style="display:inline"' . $confirmacion . '>'
                . '<input type="hidden" name="usuario_id" value="' . $usuarioId . '">'
                . '<input type="hidden" name="activo" value="' . ((int) $usuario['estado_activo'] === 1 ? '0' : '1') . '">'
                . '<button class="btn-action btn-delete" type="submit">' . $etiquetaEstado . '</button></form>';

            if ((int) $usuario['estado_activo'] === 0) {
                $filas .= '<strong> (Cuenta desactivada)</strong>';
            }
            $filas .= '</td></tr>';
        }

        return preg_replace(
            '~<tbody>.*?</tbody>~is',
            '<tbody>' . $filas . '</tbody>',
            $contenidoHtml,
            1
        ) ?? $contenidoHtml;
    }

    private function populateDashboard(string $contenidoHtml): string
    {
        $conteos = [
            'estudiantes' => (int) $this->conexion->query('SELECT COUNT(*) FROM estudiantes')->fetchColumn(),
            'personal' => (int) $this->conexion->query(
                "SELECT COUNT(*) FROM usuarios WHERE estado_activo = 1 AND usuario_id IN (
                    SELECT ur.usuario_id FROM usuarios_roles ur
                    JOIN roles r ON r.rol_id = ur.rol_id
                    WHERE r.nombre_rol != 'estudiante'
                )"
            )->fetchColumn(),
            'roles' => (int) $this->conexion->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
        ];
        $numeros = array_map(
            static fn (int $cantidad): string => '<div class="number">' . $cantidad . '</div>',
            array_values($conteos)
        );
        $indice = 0;

        return preg_replace_callback(
            '~<div class="number">.*?</div>~s',
            static function () use (&$indice, $numeros): string {
                return $numeros[$indice++] ?? '';
            },
            $contenidoHtml,
            3
        ) ?? $contenidoHtml;
    }

    private function insertarSolicitudes(string $contenidoHtml): string
    {
        $solicitudes = (new SolicitudRegistro($this->conexion))->pendientes();
        $panel = '<section style="margin:1.5rem 2rem;padding:1.5rem;background:#fff;border-radius:8px">'
            . '<h2 style="color:#a8201a;margin-bottom:1rem">Solicitudes de registro pendientes ('
            . count($solicitudes) . ')</h2>';

        if ($solicitudes === []) {
            $panel .= '<p>No hay solicitudes pendientes.</p>';
        } else {
            $panel .= '<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse">'
                . '<thead><tr><th style="text-align:left;padding:.75rem">Alumno</th>'
                . '<th style="text-align:left;padding:.75rem">Correo</th>'
                . '<th style="text-align:left;padding:.75rem">Carnet</th>'
                . '<th style="text-align:left;padding:.75rem">Carrera</th>'
                . '<th style="text-align:left;padding:.75rem">Solicitud</th>'
                . '<th style="text-align:left;padding:.75rem">Decision</th></tr></thead><tbody>';

            foreach ($solicitudes as $solicitud) {
                $nombre = htmlspecialchars($solicitud['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $correo = htmlspecialchars($solicitud['correo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $carnet = htmlspecialchars($solicitud['carnet'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $carrera = htmlspecialchars($solicitud['carrera'] ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $fecha = htmlspecialchars($solicitud['fecha_solicitud'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $id = (int) $solicitud['solicitud_id'];
                $panel .= '<tr><td style="padding:.75rem">' . $nombre . '</td>'
                    . '<td style="padding:.75rem">' . $correo . '</td>'
                    . '<td style="padding:.75rem">' . $carnet . '</td>'
                    . '<td style="padding:.75rem">' . $carrera . '</td>'
                    . '<td style="padding:.75rem">' . $fecha . '</td><td style="padding:.75rem">'
                    . '<form action="/admin/revisar-solicitud" method="POST" style="display:inline">'
                    . '<input type="hidden" name="solicitud_id" value="' . $id . '">'
                    . '<button type="submit" name="decision" value="aceptar">Aceptar</button> '
                    . '<button type="submit" name="decision" value="rechazar">Rechazar</button>'
                    . '</form></td></tr>';
            }

            $panel .= '</tbody></table></div>';
        }

        $panel .= '</section>';

        return str_replace('</body>', $panel . '</body>', $contenidoHtml);
    }
}
