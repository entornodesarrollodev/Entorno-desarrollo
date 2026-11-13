<?php
declare(strict_types=1);

final class HttpController
{
    // Este bloque inyecta la conexión usada por los controles comunes de la aplicación.
    public function __construct(private PDO $conexion)
    {
    }

    // Este bloque centraliza redirecciones HTTP y detiene la ejecución actual.
    public function redirect(string $ruta): never
    {
        header('Location: ' . $ruta, true, 303);
        exit;
    }

    // Este bloque conserva errores y valores seguros para volver a mostrar el formulario.
    public function rememberErrors(array $errores, string $formulario = '', array $valores = []): void
    {
        $_SESSION['_errores_formulario'] = $errores;
        $_SESSION['_nombre_formulario'] = $formulario;
        unset($valores['password'], $valores['token_csrf']);
        $_SESSION['_valores_formulario'] = $valores;
    }

    // Este bloque guarda un aviso temporal para mostrarlo en la siguiente respuesta.
    public function rememberNotice(string $mensaje, string $tipo = 'success'): void
    {
        $_SESSION['_aviso'] = ['mensaje' => $mensaje, 'tipo' => $tipo];
    }

    // Este bloque carga solo vistas permitidas y aplica navegación, datos y protecciones comunes.
    public function render(string $vista, ?callable $transformar = null): void
    {
        $vistasPermitidas = ['login.php', 'dashboard.php', 'dashboard_admin.php', 'roles.php', 'personal.php', 'proyectos.php', 'catalogo_proyectos.php',
            'estudiantes_listado.php', 'estudiantes_nuevo.php', 'estudiantes_editar.php', 'estudiantes_detalle.php', 'cronograma_nuevo.php',
            'horas_registrar.php', 'horas_bandeja.php', 'estudiantes_resumen_horas.php', 'proyectos_asignar.php',
            'dashboard_licenciada.php', 'calendario_actividades.php', 'consulta_respuestas.php', 'crear_plantilla.php',
            'diplomas_generar.php', 'cartas_participacion.php', 'mis_solicitudes.php', 'workflow.php'];

        if (!in_array($vista, $vistasPermitidas, true)) {
            throw new RuntimeException('Vista no permitida.');
        }

        $rutaVista = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $vista;
        $canManageStudents = $vista === 'estudiantes_editar.php' && $this->userCan('estudiantes.actualizar');
        $canEditOwnProfile = $vista === 'estudiantes_editar.php' && $this->userCan('estudiantes.actualizar_propios');
        ob_start();
        require $rutaVista;
        $contenidoHtml = (string) ob_get_clean();
        if (stripos($contenidoHtml, 'name="viewport"') === false
            && stripos($contenidoHtml, "name='viewport'") === false) {
            $contenidoHtml = preg_replace(
                '~</head>~i',
                '<meta name="viewport" content="width=device-width, initial-scale=1.0"></head>',
                $contenidoHtml,
                1
            ) ?? $contenidoHtml;
        }
        $errores = $_SESSION['_errores_formulario'] ?? [];
        $formulario = $_SESSION['_nombre_formulario'] ?? '';
        $valores = $_SESSION['_valores_formulario'] ?? [];
        $aviso = $_SESSION['_aviso'] ?? null;
        unset($_SESSION['_errores_formulario'], $_SESSION['_nombre_formulario'], $_SESSION['_valores_formulario'], $_SESSION['_aviso']);

        if ($vista === 'login.php' && $formulario === 'register') {
            $contenidoHtml = str_replace(
                '</body>',
                '<script>switchTab("register");</script></body>',
                $contenidoHtml
            );
        }
        if ($vista === 'login.php') {
            $contenidoHtml = $this->populateRegistrationPrivacy($contenidoHtml);
        }

        if ($vista === 'roles.php') {
            $contenidoHtml = $this->populateRoles($contenidoHtml);
        }

        if ($vista === 'dashboard_admin.php') {
            if (($_GET['panel'] ?? '') === 'solicitudes_registro') {
                $this->requirePermission('solicitudes.revisar');
                $contenidoHtml = $this->populateRegistrationRequests($contenidoHtml);
            } else {
                $contenidoHtml = $this->populateDashboard($contenidoHtml);
                $contenidoHtml = $this->insertarSolicitudes($contenidoHtml);
            }
        }

        if ($vista === 'dashboard.php') {
            $contenidoHtml = $this->insertarAccesosDashboard($contenidoHtml);
        }

        if ($vista === 'estudiantes_listado.php') $contenidoHtml = $this->populateStudents($contenidoHtml);
        if ($vista === 'estudiantes_editar.php') $contenidoHtml = $this->populateStudentEdit($contenidoHtml);
        if ($vista === 'estudiantes_detalle.php') $contenidoHtml = $this->populateStudentDetail($contenidoHtml);
        if ($vista === 'horas_registrar.php') $contenidoHtml = $this->populateHoursCreate($contenidoHtml);
        if ($vista === 'horas_bandeja.php') $contenidoHtml = $this->populateHoursReview($contenidoHtml);
        if ($vista === 'estudiantes_resumen_horas.php') $contenidoHtml = $this->populateHoursSummary($contenidoHtml);
        if ($vista === 'proyectos_asignar.php') $contenidoHtml = $this->populateProjectAssign($contenidoHtml);
        if ($vista === 'proyectos.php') $contenidoHtml = $this->populateProjects($contenidoHtml);
        if ($vista === 'catalogo_proyectos.php') $contenidoHtml = $this->populateCatalog($contenidoHtml);
        if ($vista === 'cronograma_nuevo.php') $contenidoHtml = $this->populateActivityCreate($contenidoHtml);

        if ($transformar !== null) {
            $contenidoHtml = $transformar($contenidoHtml);
        }

        if ($vista !== 'login.php') {
            $contenidoHtml = $this->applySharedNavigation($contenidoHtml);
        }
        $contenidoHtml = $this->restoreFormValues($contenidoHtml, $valores);

        foreach ($errores as $campo => $mensaje) {
            $contenidoHtml = $this->insertFieldError($contenidoHtml, (string) $campo, (string) $mensaje);
        }

        if (isset($errores['acceso'])) {
            $mensajeAcceso = '<div class="alert alert-error" role="alert">'
                . htmlspecialchars((string) $errores['acceso'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</div>';
            $contenidoHtml = str_contains($contenidoHtml, '</main>')
                ? str_replace('</main>', $mensajeAcceso . '</main>', $contenidoHtml)
                : str_replace('</body>', $mensajeAcceso . '</body>', $contenidoHtml);
        }

        if (is_array($aviso) && isset($aviso['mensaje'])) {
            $clase = ($aviso['tipo'] ?? '') === 'error' ? 'alert alert-error' : 'alert alert-success';
            $notificacion = '<div class="' . $clase . '" role="status">'
                . htmlspecialchars((string) $aviso['mensaje'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</div>';
            $contenidoHtml = str_contains($contenidoHtml, '</main>')
                ? str_replace('</main>', $notificacion . '</main>', $contenidoHtml)
                : str_replace('</body>', $notificacion . '</body>', $contenidoHtml);
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

    // Este bloque valida el token CSRF de formularios antes de aceptar cambios.
    public function verifyCsrfToken(): void
    {
        $tokenEsperado = $_SESSION['_token_csrf'] ?? '';
        $tokenRecibido = (string) ($_POST['token_csrf'] ?? '');

        if ($tokenEsperado === '' || $tokenRecibido === '' || !hash_equals($tokenEsperado, $tokenRecibido)) {
            http_response_code(400);
            exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
        }
    }

    // Este bloque obliga a usar POST para acciones que modifican datos.
    public function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit;
        }
    }

    // Este bloque obtiene la cuenta activa de la sesión o limpia una sesión obsoleta.
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

    // Este bloque exige sesión y dirige al usuario al aviso si falta una aceptación obligatoria.
    public function requireAuthentication(): array
    {
        $usuario = $this->currentUser();
        if ($usuario === null) {
            $this->redirect('/login.php');
        }
        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $role = (new Rol($this->conexion))->forUser((int) $usuario['usuario_id']);
        if (
            $role !== null
            && $role['nombre_rol'] === 'estudiante'
            && !in_array($ruta, ['/aviso-privacidad.php', '/privacidad_aceptar.php'], true)
            && $this->currentPrivacyNoticeRequiresAcceptance((int) $usuario['usuario_id'])
        ) {
            $this->redirect('/aviso-privacidad.php?aceptar=1');
        }

        return $usuario;
    }

    // Este bloque exige autenticación y permiso, y responde con acceso denegado si falta.
    public function requirePermission(string $permiso): array
    {
        $usuario = $this->requireAuthentication();
        if (!(new Rol($this->conexion))->userHasPermission((int) $usuario['usuario_id'], $permiso)) {
            http_response_code(403);
            exit('No tienes permisos para realizar esta acción.');
        }

        return $usuario;
    }

    // Este bloque consulta si la cuenta actual posee un permiso específico.
    public function userCan(string $permiso): bool
    {
        $usuario = $this->currentUser();
        return $usuario !== null
            && (new Rol($this->conexion))->userHasPermission((int) $usuario['usuario_id'], $permiso);
    }

    // Este bloque verifica que un expediente pertenece a la cuenta indicada.
    public function userOwnsStudent(int $estudianteId, int $usuarioId): bool
    {
        $consulta = $this->conexion->prepare('SELECT 1 FROM estudiantes WHERE estudiante_id = :estudiante_id AND usuario_id = :usuario_id');
        $consulta->execute(['estudiante_id' => $estudianteId, 'usuario_id' => $usuarioId]);
        return $consulta->fetchColumn() !== false;
    }

    // Este bloque obtiene el expediente activo asociado a una cuenta.
    public function studentIdForUser(int $usuarioId): ?int
    {
        $consulta = $this->conexion->prepare('SELECT estudiante_id FROM estudiantes WHERE usuario_id = :usuario_id');
        $consulta->execute(['usuario_id' => $usuarioId]);
        $id = $consulta->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    // Este bloque inserta un mensaje escapado junto al campo que falló validación.
    public function insertFieldError(string $contenidoHtml, string $campo, string $mensaje): string
    {
        $nombreEscapado = preg_quote($campo, '~');
        $error = '<small class="field-error" role="alert" style="display:block;color:#dc2626;margin-top:.25rem">'
            . htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</small>';
        $patron = '~<input\b(?=[^>]*\bname="' . $nombreEscapado . '")[^>]*>|<(?:select|textarea)\b(?=[^>]*\bname="' . $nombreEscapado . '")[^>]*>.*?</(?:select|textarea)>~is';
        return preg_replace_callback($patron, static fn (array $match): string => $match[0] . $error, $contenidoHtml, 1) ?? $contenidoHtml;
    }

    // Este bloque restaura valores enviados sin inyectar contenido peligroso en el HTML.
    private function restoreFormValues(string $html, array $valores): string
    {
        if ($valores === []) {
            return $html;
        }

        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = preg_replace_callback('~<input\b[^>]*>~i', static function (array $match) use ($valores, $escape): string {
            $tag = $match[0];
            if (preg_match('~\bname="([^"]+)"~i', $tag, $name) !== 1 || !array_key_exists($name[1], $valores)) {
                return $tag;
            }
            if (preg_match('~\btype="(hidden|password|submit|button|file)"~i', $tag) === 1 || !is_scalar($valores[$name[1]])) {
                return $tag;
            }
            $value = (string) $valores[$name[1]];
            if (preg_match('~\btype="(checkbox|radio)"~i', $tag) === 1) {
                $checked = preg_match('~\bvalue="' . preg_quote($escape($value), '~') . '"~i', $tag) === 1
                    || ($value === '1' && preg_match('~\bname="' . preg_quote($name[1], '~') . '"~i', $tag) === 1);
                $tag = preg_replace('~\schecked(?:="checked")?~i', '', $tag) ?? $tag;
                return $checked ? preg_replace('~\s*/?>$~', ' checked>', $tag) ?? $tag : $tag;
            }
            if (preg_match('~\bvalue="[^"]*"~i', $tag) === 1) {
                return preg_replace_callback(
                    '~\bvalue="[^"]*"~i',
                    static fn (): string => 'value="' . $escape($value) . '"',
                    $tag,
                    1
                ) ?? $tag;
            }
            return preg_replace('~\s*/?>$~', ' value="' . $escape($value) . '">', $tag) ?? $tag;
        }, $html) ?? $html;

        $html = preg_replace_callback('~<textarea\b([^>]*)>.*?</textarea>~is', static function (array $match) use ($valores, $escape): string {
            if (preg_match('~\bname="([^"]+)"~i', $match[1], $name) !== 1 || !array_key_exists($name[1], $valores)) {
                return $match[0];
            }
            return is_scalar($valores[$name[1]])
                ? '<textarea' . $match[1] . '>' . $escape((string) $valores[$name[1]]) . '</textarea>'
                : $match[0];
        }, $html) ?? $html;

        return preg_replace_callback('~<select\b([^>]*)>.*?</select>~is', static function (array $match) use ($valores): string {
            if (preg_match('~\bname="([^"]+)"~i', $match[1], $name) !== 1 || !array_key_exists($name[1], $valores)) {
                return $match[0];
            }
            $values = is_array($valores[$name[1]]) ? $valores[$name[1]] : [$valores[$name[1]]];
            $selectedValues = array_map('strval', array_filter($values, 'is_scalar'));
            return preg_replace_callback('~<option\b([^>]*)>(.*?)</option>~is', static function (array $option) use ($selectedValues): string {
                $optionValue = trim(strip_tags($option[2]));
                if (preg_match('~\bvalue="([^"]*)"~i', $option[1], $valueMatch) === 1) {
                    $optionValue = html_entity_decode($valueMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
                $attributes = preg_replace('~\sselected(?:="selected")?~i', '', $option[1]) ?? $option[1];
                if (in_array($optionValue, $selectedValues, true)) {
                    $attributes .= ' selected';
                }
                return '<option' . $attributes . '>' . $option[2] . '</option>';
            }, $match[0]) ?? $match[0];
        }, $html) ?? $html;
    }

    // Este bloque construye la navegación visible según permisos y usuario actual.
    private function applySharedNavigation(string $html): string
    {
        $usuario = $this->currentUser();
        if ($usuario === null) {
            return $html;
        }

        $grupos = [
            'Gestión académica' => [],
            'Operación' => [],
            'Seguimiento' => [],
            'Administración' => [],
            'Mi espacio' => [],
        ];
        $agregar = function (string $grupo, string $permiso, string $ruta, string $etiqueta, string $icono) use (&$grupos): void {
            if ($this->userCan($permiso)) {
                $grupos[$grupo][] = [$ruta, $icono . ' <span>' . $etiqueta . '</span>'];
            }
        };
        $inicio = $this->userCan('panel.administracion') ? '/dashboard_admin.php' : '/dashboard.php';
        $enlacesInicio = [[$inicio, '🏠 <span>Inicio</span>']];
        if ($this->userCan('portal.dashboard')) {
            $enlacesInicio[] = ['/dashboard_licenciada.php', '📊 <span>Panel de proyección social</span>'];
        }
        $agregar('Gestión académica', 'estudiantes.consultar', '/estudiantes_listado.php', 'Expedientes', '🎓');
        $agregar('Gestión académica', 'proyectos.consultar', '/proyectos.php', 'Proyectos', '📁');
        $agregar('Gestión académica', 'proyectos.catalogo', '/catalogo_proyectos.php', 'Proyectos disponibles', '🗂️');
        $agregar('Gestión académica', 'actividades.calendario', '/calendario_actividades.php', 'Calendario', '🗓️');
        $agregar('Seguimiento', 'respuestas.consultar', '/consulta_respuestas.php', 'Respuestas', '📊');
        $agregar('Seguimiento', 'evaluaciones.crear', '/evaluaciones.php', 'Evaluaciones', '🧾');
        $agregar('Seguimiento', 'asistencia.responder', '/asistencia.php', 'Mi asistencia', '✅');
        $agregar('Seguimiento', 'asistencia.consultar', '/asistencia_resumen.php', 'Respuestas de asistencia', '👥');
        $agregar('Administración', 'privacidad.gestionar', '/privacidad.php', 'Configurar aviso de privacidad', '🔒');
        $agregar('Seguimiento', 'documentos.generar', '/diplomas_generar.php', 'Diplomas y cartas', '🏅');
        if ($this->userCan('solicitudes.consultar_propias')
            && $this->studentIdForUser((int) $usuario['usuario_id']) !== null) {
            $grupos['Mi espacio'][] = ['/mis_solicitudes.php', '📄 <span>Mis solicitudes</span>'];
        }
        $agregar('Operación', 'proyectos.crear', '/cronograma_nuevo.php', 'Cronograma', '📅');
        $agregar('Operación', 'proyectos.asignar', '/proyectos_asignar.php', 'Asignaciones', '👥');
        if ($this->userCan('horas.registrar')
            && $this->studentIdForUser((int) $usuario['usuario_id']) !== null) {
            $grupos['Mi espacio'][] = ['/estudiantes_resumen_horas.php', '⏱️ <span>Mi resumen de horas</span>'];
        }
        if ($this->userCan('horas.registrar') || $this->userCan('horas.registrar_para_estudiantes')) {
            $grupos['Operación'][] = ['/horas_registrar.php', '📝 <span>Registrar horas</span>'];
        }
        $agregar('Operación', 'horas.revisar', '/horas_bandeja.php', 'Validar horas', '📋');
        $agregar('Administración', 'usuarios.consultar', '/roles.php', 'Usuarios y roles', '⚙️');
        $agregar('Administración', 'usuarios.crear', '/personal.php', 'Personal', '🧑‍💼');
        $agregar('Administración', 'solicitudes.revisar', '/dashboard_admin.php?panel=solicitudes_registro', 'Solicitudes de registro', '📨');
        if ($this->userCan('estudiantes.actualizar_propios')) {
            $usuarioId = (int) $usuario['usuario_id'];
            $studentId = $this->studentIdForUser($usuarioId);
            if ($studentId !== null) {
                $grupos['Mi espacio'][] = ['/estudiantes_editar.php?id=' . $studentId, '🪪 <span>Mi perfil</span>'];
            }
            if ($this->userCan('privacidad.consultar')) {
                $grupos['Mi espacio'][] = ['/aviso-privacidad.php', '🔐 <span>Mi aviso y privacidad</span>'];
            }
        }

        $rutaActual = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $panelActual = (string) ($_GET['panel'] ?? '');
        $esPanelSolicitudes = $rutaActual === '/dashboard_admin.php' && $panelActual === 'solicitudes_registro';
        $esActiva = function (string $ruta) use ($rutaActual, $esPanelSolicitudes): bool {
            return ($ruta === $rutaActual && !$esPanelSolicitudes)
                || ($ruta === '/dashboard_admin.php?panel=solicitudes_registro' && $esPanelSolicitudes)
                || (str_starts_with($ruta, '/estudiantes_editar.php?id=') && $rutaActual === '/estudiantes_editar.php'
                    && !$this->userCan('estudiantes.actualizar'))
                || ($ruta === '/estudiantes_listado.php'
                    && in_array($rutaActual, ['/estudiantes_nuevo.php', '/estudiantes_detalle.php', '/estudiantes_estado.php'], true))
                || ($ruta === '/estudiantes_listado.php' && $rutaActual === '/estudiantes_editar.php'
                    && $this->userCan('estudiantes.actualizar'))
                || ($ruta === '/proyectos.php' && in_array($rutaActual, ['/proyectos_listado.php', '/cronograma.php'], true))
                || ($ruta === '/diplomas_generar.php' && $rutaActual === '/cartas_participacion.php');
        };
        $renderEnlace = static function (array $enlace) use ($esActiva): string {
            [$ruta, $etiqueta] = $enlace;
            $activa = $esActiva($ruta);

            return '<a href="' . $ruta . '"' . ($activa ? ' class="active" aria-current="page"' : '') . '>' . $etiqueta . '</a>';
        };
        $items = '';
        foreach ($enlacesInicio as $enlace) {
            $items .= '<li class="nav-primary-link">' . $renderEnlace($enlace) . '</li>';
        }
        foreach ($grupos as $nombreGrupo => $enlaces) {
            if ($enlaces === []) {
                continue;
            }
            $grupoActivo = count(array_filter(
                $enlaces,
                static fn (array $enlace): bool => $esActiva($enlace[0])
            )) > 0;
            $items .= '<li class="nav-group"><details' . ($grupoActivo ? ' open' : '') . '><summary>'
                . '<span>' . $nombreGrupo . '</span><span class="nav-count">' . count($enlaces) . '</span></summary><ul class="nav-submenu">';
            foreach ($enlaces as $enlace) {
                $items .= '<li>' . $renderEnlace($enlace) . '</li>';
            }
            $items .= '</ul></details></li>';
        }
        $nav = '<nav class="sidebar-navigation" aria-label="Navegación principal"><ul class="sidebar-nav">' . $items . '</ul></nav>';
        $html = preg_replace('~<ul\b[^>]*class="(?:sidebar-nav|sidebar-menu)"[^>]*>.*?</ul>~is', $nav, $html, 1) ?? $html;
        $html = str_replace('ITCA FEPADE', 'ITCA-FEPADE', $html);
        $html = preg_replace('~<body\b[^>]*>~i', '$0<a class="skip-link" href="#main-content">Saltar al contenido</a>', $html, 1) ?? $html;
        if (preg_match('~<main\b[^>]*\bid=~i', $html) !== 1) {
            $html = preg_replace('~<main\b~i', '<main id="main-content" tabindex="-1"', $html, 1) ?? $html;
        }

        $nombre = htmlspecialchars((string) ($usuario['nombre_completo'] ?? 'Usuario'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return preg_replace_callback(
            '~(<header\b[^>]*class="topbar"[^>]*>)(.*?)(</header>)~is',
            static function (array $match) use ($nombre): string {
                $logout = '';
                $contenido = preg_replace_callback(
                    '~<a\b[^>]*href="(?:/)?logout\.php"[^>]*>.*?</a>~is',
                    static function (array $link) use (&$logout): string {
                        $logout = $link[0];
                        return '';
                    },
                    $match[2],
                    1
                ) ?? $match[2];
                if ($logout === '') {
                    $logout = '<a href="/logout.php" class="btn-logout">Cerrar sesión</a>';
                }

                return $match[1] . '<div class="topbar-context">' . trim($contenido) . '</div>'
                    . '<div class="topbar-actions"><span class="topbar-user">' . $nombre . '</span>' . $logout . '</div>'
                    . $match[3];
            },
            $html,
            1
        ) ?? $html;
    }

    // Este bloque añade al registro público el enlace y la aceptación del aviso vigente.
    private function populateRegistrationPrivacy(string $html): string
    {
        $notice = $this->conexion->query(
            'SELECT aviso_id, version, contenido, requiere_aceptacion
             FROM avisos_privacidad WHERE vigente = 1 ORDER BY fecha_publicacion DESC LIMIT 1'
        )->fetch();
        $content = '';
        if ($notice !== false) {
            $content .= '<div class="form-group"><a href="/aviso-privacidad.php" target="_blank" rel="noopener">'
                . 'Consultar aviso de privacidad · versión '
                . htmlspecialchars((string) $notice['version'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</a></div>';
            if ((int) $notice['requiere_aceptacion'] === 1) {
                $content .= '<input type="hidden" name="privacy_notice_id" value="' . (int) $notice['aviso_id'] . '">'
                    . '<div class="form-group"><label><input type="checkbox" name="privacy_accept" value="1" required> '
                    . 'He leído y acepto el aviso de privacidad vigente.</label></div>';
            }
        } else {
            $content .= '<div class="form-group"><a href="/aviso-privacidad.php" target="_blank" rel="noopener">Consultar aviso de privacidad</a>'
                . '<small>El aviso institucional aún no está configurado.</small></div>';
        }
        return str_replace(
            '<button type="submit" class="btn-itca">Registrarse y Guardar Datos</button>',
            $content . '<button type="submit" class="btn-itca">Registrarse y Guardar Datos</button>',
            $html
        );
    }

    // Este bloque detecta si el usuario debe aceptar la versión vigente antes de continuar.
    private function currentPrivacyNoticeRequiresAcceptance(int $userId): bool
    {
        $query = $this->conexion->prepare(
            'SELECT n.aviso_id FROM avisos_privacidad n
             WHERE n.vigente = 1 AND n.requiere_aceptacion = 1
               AND NOT EXISTS (
                   SELECT 1 FROM aceptaciones_privacidad a
                   WHERE a.aviso_id = n.aviso_id AND a.usuario_id = :usuario
               )
             ORDER BY n.fecha_publicacion DESC LIMIT 1'
        );
        $query->execute(['usuario' => $userId]);
        return $query->fetchColumn() !== false;
    }

    // Este bloque llena la tabla de usuarios y controles de roles desde la base de datos.
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
            if ($this->userCan('usuarios.gestionar_roles')) {
                $filas .= '<form action="/admin/asignar-rol" method="POST" class="table-actions">'
                    . '<input type="hidden" name="usuario_id" value="' . $usuarioId . '"><select name="rol_id" required>';

                foreach ($roles as $rol) {
                    $seleccionado = (int) $rol['rol_id'] === (int) $usuario['rol_id'] ? ' selected' : '';
                    $etiqueta = htmlspecialchars($rol['etiqueta'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $filas .= '<option value="' . (int) $rol['rol_id'] . '"' . $seleccionado . '>' . $etiqueta . '</option>';
                }

                $filas .= '</select><button class="btn-action btn-edit" type="submit">Guardar rol</button></form>';
            }

            if ($this->userCan('usuarios.gestionar_estado')) {
                $etiquetaEstado = (int) $usuario['estado_activo'] === 1 ? 'Desactivar' : 'Activar';
                $confirmacion = (int) $usuario['estado_activo'] === 1
                    ? ' onsubmit="return confirm(\'¿Confirmas que deseas desactivar esta cuenta?\')"'
                    : '';
                $filas .= '<form action="/admin/cambiar-estado" method="POST"' . $confirmacion . '>'
                    . '<input type="hidden" name="usuario_id" value="' . $usuarioId . '">'
                    . '<input type="hidden" name="activo" value="' . ((int) $usuario['estado_activo'] === 1 ? '0' : '1') . '">'
                    . '<button class="btn-action btn-delete" type="submit">' . $etiquetaEstado . '</button></form>';
            }

            if ((int) $usuario['estado_activo'] === 0) {
                $filas .= '<strong> (Cuenta desactivada)</strong>';
            }
            $filas .= '</td></tr>';
        }

        return preg_replace_callback(
            '~<tbody>.*?</tbody>~is',
            static fn(): string => '<tbody>' . $filas . '</tbody>',
            $contenidoHtml,
            1
        ) ?? $contenidoHtml;
    }

    // Este bloque reemplaza las métricas de muestra del panel con conteos reales.
    private function populateDashboard(string $contenidoHtml): string
    {
        $conteos = [
            'estudiantes' => (int) $this->conexion->query(
                "SELECT COUNT(*)
                 FROM estudiantes e
                 LEFT JOIN usuarios u ON u.usuario_id = e.usuario_id
                 LEFT JOIN usuarios_roles ur ON ur.usuario_id = u.usuario_id
                 LEFT JOIN roles r ON r.rol_id = ur.rol_id
                 WHERE e.estado_registro = 'Activo'
                   AND (e.usuario_id IS NULL OR (u.estado_activo = 1 AND r.nombre_rol = 'estudiante'))"
            )->fetchColumn(),
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

        // Esta función asigna a cada marcador de muestra el conteo real correspondiente.
        return preg_replace_callback(
            '~<div class="number">.*?</div>~s',
            // Este callback reemplaza los marcadores en el mismo orden de los conteos.
            static function () use (&$indice, $numeros): string {
                return $numeros[$indice++] ?? '';
            },
            $contenidoHtml,
            3
        ) ?? $contenidoHtml;
    }

    // Este bloque agrega tarjetas de módulos disponibles para la cuenta actual.
    private function insertarAccesosDashboard(string $contenidoHtml): string
    {
        $usuario = $this->currentUser();
        if ($usuario === null) return $contenidoHtml;

        $esc = static fn ($valor): string => htmlspecialchars((string) $valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $nombre = $esc($usuario['nombre_completo'] ?? 'Usuario');
        $tarjetas = [];

        if ($this->userCan('horas.registrar')) {
            $registro = (new RegistroHoras($this->conexion));
            $estudianteId = $registro->studentIdForUser((int) $usuario['usuario_id']);
            $total = 0.0;
            $pendientes = 0;
            $actividades = [];

            if ($estudianteId !== null) {
                $resumen = (new Proyecto($this->conexion))->hoursSummary($estudianteId);
                $total = (float) $resumen['total'];
                $consulta = $this->conexion->prepare(
                    'SELECT COUNT(*) FROM registros_horas WHERE estudiante_id = :estudiante_id AND estado = "Pendiente"'
                );
                $consulta->execute(['estudiante_id' => $estudianteId]);
                $pendientes = (int) $consulta->fetchColumn();
                $actividades = (new Proyecto($this->conexion))->activitiesForStudent((int) $usuario['usuario_id']);
            }

            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Mis horas</h3><p><strong>' . number_format($total, 2) . '</strong> horas aprobadas</p>'
                . '<p>' . $pendientes . ' registro(s) pendiente(s) de validación.</p>'
                . '<a href="/estudiantes_resumen_horas.php">Ver resumen</a> · '
                . '<a href="/horas_registrar.php">Registrar horas</a></article>';

            $listaActividades = '';
            foreach ($actividades as $actividad) {
                $listaActividades .= '<li>' . $esc($actividad['proyecto']) . ' — ' . $esc($actividad['titulo']) . '</li>';
            }
            if ($listaActividades === '') $listaActividades = '<li>No tienes actividades asignadas actualmente.</li>';
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Mis actividades</h3><ul>' . $listaActividades . '</ul></article>';
        }

        if ($this->userCan('estudiantes.consultar')) {
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Estudiantes</h3><p>Consulta y administra expedientes.</p>'
                . '<a href="/estudiantes_listado.php">Ir al listado</a></article>';
        }
        if ($this->userCan('proyectos.consultar')) {
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Proyectos</h3><p>Consulta proyectos, asignaciones y estados.</p>'
                . '<a href="/proyectos.php">Ir a proyectos</a>'
                . ($this->userCan('proyectos.crear') ? ' · <a href="/cronograma_nuevo.php">Nuevo cronograma</a>' : '')
                . '</article>';
        }
        if ($this->userCan('proyectos.asignar')) {
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Asignaciones</h3><p>Relaciona estudiantes activos con proyectos.</p>'
                . '<a href="/proyectos_asignar.php">Asignar estudiante</a></article>';
        }
        if ($this->userCan('horas.revisar')) {
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Validación de horas</h3><p>Revisa registros pendientes.</p>'
                . '<a href="/horas_bandeja.php">Abrir bandeja</a></article>';
        }
        if ($this->userCan('proyectos.catalogo')) {
            $tarjetas[] = '<article class="dashboard-dynamic-card">'
                . '<h3 style="margin-top:0">Catálogo de proyectos</h3><p>Consulta los proyectos activos disponibles.</p>'
                . '<a href="/catalogo_proyectos.php">Ver catálogo</a></article>';
        }

        if ($tarjetas === []) {
            $tarjetas[] = '<article style="padding:1.25rem;border:1px solid #e5e7eb;border-radius:8px;background:#fff">'
                . '<p>No hay módulos disponibles para tu cuenta.</p></article>';
        }

        $panel = '<section class="dashboard-dynamic">'
            . '<h2 style="margin-top:0">Hola, ' . $nombre . '</h2>'
            . '<div class="dashboard-dynamic-grid">'
            . implode('', $tarjetas) . '</div></section>';

        return str_replace('</main>', $panel . '</main>', $contenidoHtml);
    }

    // Este bloque adapta enlaces internos de las vistas al sistema de rutas de la aplicación.
    private function wireViewNavigation(string $contenidoHtml): string
    {
        $inicio = $this->userCan('panel.administracion') ? '/dashboard_admin.php' : '/dashboard.php';
        // Esta función resuelve enlaces genéricos de la vista a rutas según su texto y permisos.
        return preg_replace_callback(
            '~<a\s+href="#"([^>]*)>(.*?)</a>~is',
            // Este callback asigna una ruta real a cada enlace interno reconocido.
            function (array $coincidencia) use ($inicio): string {
                $texto = trim(strip_tags($coincidencia[2]));
                $ruta = null;
                if (str_contains($texto, 'Inicio')) $ruta = $inicio;
                elseif (str_contains($texto, 'Proyectos')) $ruta = '/proyectos.php';
                elseif (str_contains($texto, 'Cronograma')) $ruta = '/cronograma_nuevo.php';
                elseif (str_contains($texto, 'Validación de Horas')) $ruta = '/horas_bandeja.php';
                elseif (str_contains($texto, 'Registros')) $ruta = $this->userCan('horas.revisar') ? '/horas_bandeja.php' : '/horas_registrar.php';
                elseif (str_contains($texto, 'Mi Resumen de Horas')) $ruta = '/estudiantes_resumen_horas.php';
                elseif (str_contains($texto, 'Reportes')) $ruta = '/estudiantes_resumen_horas.php';

                return $ruta === null
                    ? $coincidencia[0]
                    : '<a href="' . $ruta . '"' . $coincidencia[1] . '>' . $coincidencia[2] . '</a>';
            },
            $contenidoHtml
        ) ?? $contenidoHtml;
    }

    // Este bloque muestra avisos de solicitudes pendientes a personal autorizado.
    private function insertarSolicitudes(string $contenidoHtml): string
    {
        if (!$this->userCan('solicitudes.revisar')) {
            return str_replace('<div id="registration-request-summary"></div>', '', $contenidoHtml);
        }

        $pendientes = (new SolicitudRegistro($this->conexion))->contarPendientes();
        $card = '<article class="stat-card request-summary"><h2>Solicitudes de registro pendientes</h2>'
            . '<div class="number">' . $pendientes . '</div><a href="/dashboard_admin.php?panel=solicitudes_registro">'
            . 'Revisar solicitudes</a></article>';

        return str_replace('<div id="registration-request-summary"></div>', $card, $contenidoHtml);
    }

    private function populateRegistrationRequests(string $html): string
    {
        $model = new SolicitudRegistro($this->conexion);
        $busqueda = trim((string) ($_GET['q'] ?? ''));
        $total = $model->contarPendientes($busqueda);
        $porPagina = 20;
        $paginas = max(1, (int) ceil($total / $porPagina));
        $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT);
        $pagina = $pagina === false || $pagina === null ? 1 : max(1, (int) $pagina);
        $pagina = min($pagina, $paginas);
        $desplazamiento = ($pagina - 1) * $porPagina;
        $solicitudes = $model->pendientesPaginadas($busqueda, $porPagina, $desplazamiento);
        $escapar = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $filas = '';

        foreach ($solicitudes as $solicitud) {
            $id = (int) $solicitud['solicitud_id'];
            $nombre = $escapar($solicitud['nombre_completo']);
            $filas .= '<tr><td><strong>' . $nombre . '</strong><br><span class="table-secondary">'
                . $escapar($solicitud['correo']) . '</span></td><td>' . $escapar($solicitud['carnet'] ?? '')
                . '</td><td>' . $escapar($this->careerLabel((string) ($solicitud['carrera'] ?? '')))
                . '</td><td>' . $escapar($solicitud['fecha_solicitud']) . '</td><td><form action="/admin/revisar-solicitud" method="POST" class="registration-review-form">'
                . '<input type="hidden" name="solicitud_id" value="' . $id . '">'
                . '<label class="sr-only" for="registration-name-' . $id . '">Nombre completo</label>'
                . '<input id="registration-name-' . $id . '" type="text" name="nombre_completo" maxlength="150" required value="' . $nombre . '">'
                . '<button class="btn-red" type="submit" name="decision" value="aceptar">Aprobar</button>'
                . '<button class="btn-gray" type="submit" name="decision" value="rechazar">Rechazar</button></form></td></tr>';
        }

        if ($filas === '') {
            $mensaje = $busqueda === ''
                ? 'No hay solicitudes de registro pendientes.'
                : 'No se encontraron solicitudes que coincidan con la búsqueda.';
            $filas = '<tr><td colspan="5"><div class="empty-state">' . $escapar($mensaje) . '</div></td></tr>';
        }

        $paginacion = '';
        if ($paginas > 1) {
            $paginacion = '<nav class="pagination" aria-label="Paginación de solicitudes"><span>Página '
                . $pagina . ' de ' . $paginas . '</span><div>';
            foreach ([max(1, $pagina - 1) => 'Anterior', min($paginas, $pagina + 1) => 'Siguiente'] as $numero => $etiqueta) {
                if (($etiqueta === 'Anterior' && $pagina === 1) || ($etiqueta === 'Siguiente' && $pagina === $paginas)) {
                    continue;
                }
                $query = http_build_query(['panel' => 'solicitudes_registro', 'q' => $busqueda, 'pagina' => $numero]);
                $paginacion .= '<a class="btn-gray" href="/dashboard_admin.php?' . $query . '">' . $etiqueta . '</a>';
            }
            $paginacion .= '</div></nav>';
        }

        $desde = $total === 0 ? 0 : $desplazamiento + 1;
        $hasta = min($total, $desplazamiento + $porPagina);
        $main = '<main class="content-area"><div class="breadcrumb"><a href="/dashboard_admin.php">Inicio</a> / Solicitudes de registro</div>'
            . '<div class="page-header"><div><h1>Solicitudes de registro</h1><p class="page-description">Revisa y resuelve las cuentas pendientes de aprobación.</p></div>'
            . '<a class="btn-gray" href="/dashboard_admin.php">Volver al inicio</a></div>'
            . '<form class="form-card filter-card" method="GET" action="/dashboard_admin.php"><input type="hidden" name="panel" value="solicitudes_registro">'
            . '<div class="filter-grid"><div class="form-group"><label for="registration-search">Buscar por nombre, correo o carné</label>'
            . '<input id="registration-search" type="search" name="q" maxlength="100" value="' . $escapar($busqueda) . '"></div></div>'
            . '<div class="form-actions"><button class="btn-red" type="submit">Buscar</button>'
            . '<a class="btn-gray" href="/dashboard_admin.php?panel=solicitudes_registro">Limpiar</a></div></form>'
            . '<p class="page-description request-results-count">Mostrando ' . $desde . '–' . $hasta . ' de ' . $total . ' solicitudes.</p>'
            . $paginacion . '<section class="table-container" aria-label="Solicitudes pendientes"><table><thead><tr>'
            . '<th>Cuenta</th><th>Carné</th><th>Carrera</th><th>Fecha de solicitud</th><th>Revisión</th></tr></thead><tbody>'
            . $filas . '</tbody></table></section>' . $paginacion . '</main>';

        return preg_replace('~<main\b[^>]*>.*?</main>~is', $main, $html, 1) ?? $html;
    }

    private function careerLabel(?string $career): string
    {
        $career = trim((string) $career);
        $labels = [
            'sistemas' => 'Sistemas',
            'software' => 'Software',
            'mantenimiento' => 'Mantenimiento',
            'otro' => 'Otro',
        ];

        return $labels[$career] ?? ($career !== '' ? $career : 'Sin especificar');
    }

    // Este bloque aplica filtros y datos reales al listado de expedientes.
    private function populateStudents(string $html): string
    {
        $filtros = [
            'q' => (string) ($_GET['q'] ?? ''),
            'carrera' => (string) ($_GET['carrera'] ?? ''),
            'anio_academico' => (string) ($_GET['anio_academico'] ?? ''),
            'modalidad_participacion' => (string) ($_GET['modalidad_participacion'] ?? ''),
            'estado_registro' => (string) ($_GET['estado'] ?? 'Activo'),
        ];
        $estudiantes = new Estudiante($this->conexion);
        $canUpdateStudents = $this->userCan('estudiantes.actualizar');
        $filas = '';
        foreach ($estudiantes->all($filtros) as $e) {
            $id = (int) $e['estudiante_id'];
            $safe = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $estado = (string) $e['estado_registro'];
            $clase = $estado === 'Activo' ? 'badge-activo' : 'badge-inactivo';
            $acciones = '<a class="btn-action btn-edit" href="/estudiantes_detalle.php?id=' . $id . '">Ver expediente</a>';
            if ($canUpdateStudents) {
                $acciones .= '<a class="btn-action btn-edit" href="/estudiantes_editar.php?id=' . $id . '">Editar</a>';
                $nuevoEstado = $estado === 'Activo' ? 'Inactivo' : 'Activo';
                $etiquetaEstado = $estado === 'Activo' ? 'Desactivar' : 'Reactivar';
                $confirm = $estado === 'Activo' ? ' onsubmit="return confirm(\'¿Desactivar este expediente? Se conservará su historial.\')"' : '';
                $acciones .= '<form action="/estudiantes_estado.php" method="POST"' . $confirm . '>'
                    . '<input type="hidden" name="estudiante_id" value="' . $id . '">'
                    . '<input type="hidden" name="estado" value="' . $nuevoEstado . '">'
                    . '<button class="btn-action btn-delete" type="submit">' . $etiquetaEstado . '</button></form>';
            }
            $filas .= '<tr><td>' . $id . '</td><td><strong>' . $safe($e['nombre_completo']) . '</strong></td>'
                . '<td>' . $safe($e['carnet'] ?? '') . '</td><td>' . $safe($this->careerLabel((string) $e['carrera'])) . '</td>'
                . '<td>' . $safe($e['anio_academico']) . '</td><td>' . $safe($e['modalidad_participacion'] ?? 'Horas sociales') . '</td>'
                . '<td><span class="badge ' . $clase . '">' . $safe($estado) . '</span></td>'
                . '<td><div class="table-actions">' . $acciones . '</div></td></tr>';
        }
        if ($filas === '') {
            $filas = '<tr><td colspan="8"><div class="empty-state">No se encontraron expedientes con estos filtros. <a href="/estudiantes_listado.php">Limpiar filtros</a></div></td></tr>';
        }
        $html = preg_replace_callback('~<tbody>.*?</tbody>~is', static fn (): string => '<tbody>' . $filas . '</tbody>', $html, 1) ?? $html;
        $html = preg_replace_callback('~<select id="career-filter"[^>]*>.*?</select>~is', function () use ($estudiantes, $filtros): string {
            $options = '<option value="">Todas las carreras</option>';
            foreach ($estudiantes->careers() as $career) {
                $value = htmlspecialchars((string) $career, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $label = htmlspecialchars($this->careerLabel((string) $career), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $selected = $filtros['carrera'] === (string) $career ? ' selected' : '';
                $options .= '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
            }
            return '<select id="career-filter" name="carrera">' . $options . '</select>';
        }, $html, 1) ?? $html;
        foreach ([
            'year-filter' => $filtros['anio_academico'],
            'mode-filter' => $filtros['modalidad_participacion'],
            'status-filter' => $filtros['estado_registro'],
        ] as $id => $selectedValue) {
            $html = preg_replace_callback('~<select id="' . preg_quote($id, '~') . '"[^>]*>.*?</select>~is', static function (array $match) use ($selectedValue): string {
                return preg_replace_callback('~<option\b([^>]*)>(.*?)</option>~is', static function (array $option) use ($selectedValue): string {
                    $attributes = preg_replace('~\sselected(?:="selected")?~i', '', $option[1]) ?? $option[1];
                    if (preg_match('~\bvalue="([^"]*)"~i', $attributes, $value) === 1) {
                        $optionValue = $value[1];
                    } else {
                        $optionValue = trim(strip_tags($option[0]));
                    }
                    return '<option' . $attributes . ($optionValue === $selectedValue ? ' selected' : '') . '>' . $option[2] . '</option>';
                }, $match[0]) ?? $match[0];
            }, $html, 1) ?? $html;
        }
        if (!$this->userCan('estudiantes.crear')) {
            $html = preg_replace('~<a href="estudiantes_nuevo\.php"[^>]*>.*?</a>~is', '', $html) ?? $html;
            $html = preg_replace('~<a href="/estudiantes_nuevo\.php"[^>]*>.*?</a>~is', '', $html) ?? $html;
        }
        return $html;
    }

    // Este bloque llena el formulario de edición con los datos del expediente autorizado.
    private function populateStudentEdit(string $html): string
    {
        $id = (int) ($_GET['id'] ?? 0);
        $e = $id > 0 ? (new Estudiante($this->conexion))->find($id) : null;
        if ($e === null) {
            http_response_code(404);
            return $this->notFoundContent($html, 'No se encontró el expediente solicitado.');
        }
        $html = str_replace('action="/estudiantes_editar.php"', 'action="/estudiantes_editar.php?id=' . $id . '"', $html);
        return $this->restoreFormValues($html, [
            'nombres' => (string) ($e['nombres'] ?? ''),
            'apellidos' => (string) ($e['apellidos'] ?? ''),
            'carnet' => (string) ($e['carnet'] ?? ''),
            'dui' => (string) ($e['dui'] ?? ''),
            'correo' => (string) ($e['correo'] ?? ''),
            'telefono' => (string) ($e['telefono'] ?? ''),
            'carrera' => (string) ($e['carrera'] ?? ''),
            'anio_academico' => (string) $e['anio_academico'],
            'modalidad_participacion' => (string) ($e['modalidad_participacion'] ?? 'Horas sociales'),
            'fecha_nacimiento' => (string) ($e['fecha_nacimiento'] ?? ''),
            'genero' => (string) ($e['genero'] ?? ''),
            'estado' => (string) $e['estado_registro'],
            'direccion' => (string) ($e['direccion'] ?? ''),
        ]);
    }

    // Este bloque renderiza el perfil, proyectos, horas, evaluaciones y documentos asociados.
    private function populateStudentDetail(string $html): string
    {
        $idInput = $_GET['id'] ?? null;
        if ($idInput === null || $idInput === '') {
            $this->redirect('/estudiantes_listado.php');
        }
        $id = filter_var($idInput, FILTER_VALIDATE_INT);
        if ($id === false || $id < 1) {
            http_response_code(404);
            return $this->notFoundContent($html, 'El identificador del expediente no es válido.');
        }

        $model = new Estudiante($this->conexion);
        $student = $model->find((int) $id);
        if ($student === null) {
            http_response_code(404);
            return $this->notFoundContent($html, 'No se encontró el expediente solicitado.');
        }

        $escape = static fn ($value): string => htmlspecialchars(
            trim((string) $value) !== '' ? (string) $value : '—',
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
        $status = (string) ($student['estado_registro'] ?? 'Inactivo');
        $statusClass = $status === 'Activo' ? 'badge-activo' : 'badge-inactivo';
        $actions = '';
        if ($this->userCan('estudiantes.actualizar')) {
            $studentId = (int) $student['estudiante_id'];
            $nextStatus = $status === 'Activo' ? 'Inactivo' : 'Activo';
            $actionLabel = $status === 'Activo' ? 'Desactivar expediente' : 'Reactivar expediente';
            $confirmation = $status === 'Activo'
                ? ' onsubmit="return confirm(\'¿Desactivar este expediente? Se conservará su historial.\')"'
                : '';
            $actions = '<a class="btn-gray" href="/estudiantes_editar.php?id=' . $studentId . '">Editar expediente</a>'
                . '<form action="/estudiantes_estado.php" method="POST"' . $confirmation . '>'
                . '<input type="hidden" name="estudiante_id" value="' . $studentId . '">'
                . '<input type="hidden" name="estado" value="' . $nextStatus . '">'
                . '<button class="' . ($status === 'Activo' ? 'btn-delete' : 'btn-red') . '" type="submit">' . $actionLabel . '</button>'
                . '</form>';
        }

        $related = $model->related((int) $student['estudiante_id']);
        $projects = '';
        foreach ($related['proyectos'] as $project) {
            $assignmentStatus = $project['fecha_desasignacion'] === null ? 'Activa' : 'Histórica';
            $projects .= '<tr><td>' . $escape($project['codigo']) . '</td><td>' . $escape($project['nombre']) . '</td>'
                . '<td>' . $escape($project['modalidad']) . '</td><td>' . $escape($project['estado']) . '</td>'
                . '<td>' . $assignmentStatus . '</td></tr>';
        }
        if ($projects === '') {
            $projects = '<tr><td colspan="5" class="empty-state">Este expediente aún no tiene proyectos asociados.</td></tr>';
        }

        $hoursByState = ['Pendiente' => 0.0, 'Aprobado' => 0.0, 'Rechazado' => 0.0];
        $hours = '';
        foreach ($related['horas'] as $record) {
            $state = (string) $record['estado'];
            if (array_key_exists($state, $hoursByState)) {
                $hoursByState[$state] += (float) $record['horas'];
            }
            $hours .= '<tr><td>' . $escape($record['proyecto']) . '</td><td>' . $escape($record['titulo']) . '</td>'
                . '<td>' . $escape(number_format((float) $record['horas'], 2)) . '</td><td><span class="badge">'
                . $escape($state) . '</span></td><td>' . $escape($record['fecha_registro']) . '</td></tr>';
        }
        if ($hours === '') {
            $hours = '<tr><td colspan="5" class="empty-state">No hay registros de horas para este expediente.</td></tr>';
        }
        $hourTotals = '<div class="stats-grid">'
            . '<article class="stat-card"><h2>Horas aprobadas</h2><div class="number">' . $escape(number_format($hoursByState['Aprobado'], 2)) . '</div></article>'
            . '<article class="stat-card"><h2>Horas pendientes</h2><div class="number">' . $escape(number_format($hoursByState['Pendiente'], 2)) . '</div></article>'
            . '<article class="stat-card"><h2>Horas rechazadas</h2><div class="number">' . $escape(number_format($hoursByState['Rechazado'], 2)) . '</div></article>'
            . '</div>';

        $evaluations = '';
        foreach ($related['evaluaciones'] as $evaluation) {
            $evaluations .= '<tr><td>' . $escape($evaluation['proyecto'] ?? 'General') . '</td><td>'
                . $escape($evaluation['calificacion'] ?? '—') . '</td><td>' . $escape($evaluation['comentarios'])
                . '</td><td>' . $escape($evaluation['evaluador'] ?? 'Sin dato') . '</td><td>'
                . $escape($evaluation['fecha_evaluacion']) . '</td></tr>';
        }
        if ($evaluations === '') {
            $evaluations = '<tr><td colspan="5" class="empty-state">No hay evaluaciones registradas.</td></tr>';
        }

        $documents = '';
        foreach ($related['documentos'] as $document) {
            $documents .= '<tr><td>' . $escape($document['nombre']) . '</td><td>' . $escape($document['tipo'] ?? '—')
                . '</td><td>' . $escape($document['fecha_subida']) . '</td><td>'
                . $escape($document['subido_por_nombre'] ?? 'Sin dato') . '</td></tr>';
        }
        if ($documents === '') {
            $documents = '<tr><td colspan="4" class="empty-state">No hay documentos registrados.</td></tr>';
        }

        $values = [
            '{{student_name}}' => $escape($student['nombre_completo']),
            '{{status_class}}' => $statusClass,
            '{{status}}' => $escape($status),
            '{{career}}' => $escape($this->careerLabel((string) $student['carrera'])),
            '{{carnet}}' => $escape($student['carnet'] ?? ''),
            '{{student_id}}' => $escape($student['estudiante_id']),
            '{{first_names}}' => $escape($student['nombres'] ?? ''),
            '{{last_names}}' => $escape($student['apellidos'] ?? ''),
            '{{personal_carnet}}' => $escape($student['carnet'] ?? ''),
            '{{dui}}' => $escape($student['dui'] ?? ''),
            '{{email}}' => $escape($student['correo'] ?? ''),
            '{{phone}}' => $escape($student['telefono'] ?? ''),
            '{{address}}' => $escape($student['direccion'] ?? ''),
            '{{academic_career}}' => $escape($student['carrera'] ?? ''),
            '{{academic_year}}' => $escape($student['anio_academico'] ?? ''),
            '{{participation_mode}}' => $escape($student['modalidad_participacion'] ?? 'Horas sociales'),
            '{{created_at}}' => $escape($student['fecha_creacion'] ?? ''),
            '{{actions}}' => $actions,
            '{{projects}}' => $projects,
            '{{hour_totals}}' => $hourTotals,
            '{{hours}}' => $hours,
            '{{evaluations}}' => $evaluations,
            '{{documents}}' => $documents,
        ];

        return strtr($html, $values);
    }

    // Este bloque adapta la vista existente para mostrar un error 404 de expediente.
    private function notFoundContent(string $html, string $mensaje): string
    {
        $safe = htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $main = '<main class="content-area"><div class="breadcrumb"><a href="/dashboard.php">Inicio</a></div>'
            . '<section class="form-card empty-state"><h1>No disponible</h1><p>' . $safe . '</p>'
            . '<a class="btn-gray" href="/estudiantes_listado.php">Volver a estudiantes</a></section></main>';
        return preg_replace('~<main\b[^>]*>.*?</main>~is', $main, $html, 1) ?? $html;
    }

    // Este bloque carga responsables, proyectos y fechas para crear o editar actividades.
    private function populateActivityCreate(string $html): string
    {
        $model = new Proyecto($this->conexion);
        $activityInput = $_GET['id'] ?? null;
        $activityId = $activityInput === null ? null : filter_var($activityInput, FILTER_VALIDATE_INT);
        if ($activityId !== null && ($activityId === false || $activityId < 1)) {
            return $this->notFoundContent($html, 'La actividad seleccionada no es válida.');
        }
        $activity = $activityId ? $model->findActivity((int) $activityId) : null;
        if ($activityId && $activity === null) {
            return $this->notFoundContent($html, 'La actividad seleccionada no existe.');
        }

        $projects = $model->all(['estado' => 'Activo']);
        $options = '<option value="">Seleccione...</option>';
        foreach ($projects as $project) {
            $options .= '<option value="' . (int) $project['proyecto_id'] . '">'
                . htmlspecialchars($project['nombre'] . ' (' . $project['codigo'] . ')', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</option>';
        }
        $users = '<option value="">Seleccione...</option>';
        foreach ($this->internalUsers() as $user) {
            $users .= '<option value="' . (int) $user['usuario_id'] . '">'
                . htmlspecialchars($user['nombre_completo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
        }
        $html = preg_replace_callback(
            '~<select name="proyecto_id" required>.*?</select>~is',
            static fn (): string => '<select name="proyecto_id" required>' . $options . '</select>',
            $html,
            1
        ) ?? $html;
        $html = preg_replace_callback(
            '~<select name="responsable_id" required>.*?</select>~is',
            static fn (): string => '<select name="responsable_id" required>' . $users . '</select>',
            $html,
            1
        ) ?? $html;

        $projectId = $activity !== null
            ? (int) $activity['proyecto_id']
            : (int) ($_GET['proyecto_id'] ?? 0);
        $formValues = [];
        if ($activity !== null) {
            $formValues = [
                'titulo' => $activity['titulo'],
                'descripcion' => $activity['descripcion'],
                'modalidad' => $activity['modalidad'],
                'proyecto_id' => (string) $activity['proyecto_id'],
                'fecha_inicio' => date('Y-m-d\TH:i', strtotime($activity['fecha_inicio'])),
                'fecha_fin' => date('Y-m-d\TH:i', strtotime($activity['fecha_fin'])),
                'fecha_limite_confirmacion' => empty($activity['fecha_limite_confirmacion'])
                    ? ''
                    : date('Y-m-d\TH:i', strtotime($activity['fecha_limite_confirmacion'])),
                'lugar' => $activity['lugar'],
                'horas_estimadas' => (string) $activity['horas_estimadas'],
                'cupo' => (string) ($activity['cupo'] ?? ''),
                'recurso' => (string) ($activity['recurso'] ?? ''),
                'responsable_id' => (string) $activity['responsable_id'],
            ];
            $html = preg_replace('~(<form\b[^>]*>)~i', '$1<input type="hidden" name="actividad_id" value="' . (int) $activityId . '">', $html, 1) ?? $html;
            $html = str_replace('Registrar Actividad - ITCA-FEPADE', 'Editar Actividad - ITCA-FEPADE', $html);
            $html = str_replace('Registrar Actividad del Cronograma', 'Editar Actividad del Cronograma', $html);
            $html = str_replace('💾 Guardar Actividad', '💾 Guardar cambios', $html);
        }
        if ($projectId > 0) {
            $formValues['proyecto_id'] = (string) $projectId;
            $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $rows = '';
            foreach ($model->activities($projectId) as $existingActivity) {
                $edit = $this->userCan('proyectos.actualizar')
                    ? '<a href="/cronograma_nuevo.php?id=' . (int) $existingActivity['actividad_id'] . '&proyecto_id=' . $projectId . '">Editar</a>'
                    : '';
                $rows .= '<tr><td>' . $escape($existingActivity['titulo']) . '</td><td>' . $escape($existingActivity['fecha_inicio'])
                    . ' - ' . $escape($existingActivity['fecha_fin']) . '</td><td>' . $escape($existingActivity['responsable_nombre'] ?? '')
                    . '</td><td>' . $escape($existingActivity['horas_estimadas']) . '</td><td>' . $edit . '</td></tr>';
            }
            if ($rows === '') {
                $rows = '<tr><td colspan="5" class="empty-state">No hay actividades asociadas.</td></tr>';
            }
            $panel = '<section class="form-card" style="margin-top:1.5rem"><h3>Actividades existentes del proyecto</h3><table style="width:100%;border-collapse:collapse"><thead><tr><th>Título</th><th>Horario</th><th>Responsable</th><th>Horas</th><th>Acciones</th></tr></thead><tbody>'
                . $rows . '</tbody></table></section>';
            $html = str_replace('</main>', $panel . '</main>', $html);
        }

        $html = preg_replace(
            '~(<div style="margin-top: 1\.5rem; display: flex; gap: 1rem; justify-content: flex-end;">)~',
            '<div class="form-group full"><label><input type="checkbox" name="confirmar_conflicto" value="1"> Confirmo que deseo continuar aunque exista un cruce de horario detectado.</label></div>$1',
            $html,
            1
        ) ?? $html;
        $html = $this->restoreFormValues($html, $formValues);
        return $html;
    }

    // Este bloque devuelve las cuentas activas disponibles para asignar responsabilidades.
    private function internalUsers(): array
    {
        return $this->conexion->query("SELECT DISTINCT u.usuario_id,u.nombre_completo FROM usuarios u JOIN usuarios_roles ur ON ur.usuario_id=u.usuario_id JOIN roles r ON r.rol_id=ur.rol_id WHERE u.estado_activo=1 AND r.nombre_rol <> 'estudiante' ORDER BY u.nombre_completo")->fetchAll();
    }

    // Este bloque llena el formulario de asignación con proyectos y estudiantes elegibles.
    private function populateProjectAssign(string $html): string
    {
        $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $projectId = (int) ($_GET['proyecto_id'] ?? 0);
        $options = '<option value="">Selecciona un proyecto activo</option>';
        foreach ((new Proyecto($this->conexion))->all(['estado' => 'Activo']) as $project) {
            $id = (int) $project['proyecto_id'];
            $selected = $id === $projectId ? ' selected' : '';
            $options .= '<option value="' . $id . '"' . $selected . '>' . $esc($project['nombre']) . ' (' . $esc($project['codigo']) . ')</option>';
        }
        $html = preg_replace_callback(
            '~<select id="target-project"[^>]*>.*?</select>~is',
            static fn (): string => '<select id="target-project" name="proyecto_id" required>' . $options . '</select>',
            $html,
            1
        ) ?? $html;
        $studentOptions = '';
        foreach ((new Proyecto($this->conexion))->activeStudents((string) ($_GET['q'] ?? '')) as $student) {
            $studentOptions .= '<option value="' . (int) $student['estudiante_id'] . '">' . $esc($student['nombre_completo'])
                . ' · ' . $esc($student['carnet'] ?? '') . ' · ' . $esc($student['correo']) . '</option>';
        }
        if ($studentOptions === '') {
            $studentOptions = '<option disabled>No se encontraron estudiantes activos.</option>';
        }
        $html = preg_replace_callback(
            '~<select id="active-students"[^>]*>.*?</select>~is',
            static fn (): string => '<select id="active-students" name="estudiante_ids[]" multiple size="8" required>' . $studentOptions . '</select>',
            $html,
            1
        ) ?? $html;
        return $html;
    }

    // Este bloque muestra proyectos activos que corresponden a los filtros del catálogo.
    private function populateCatalog(string $html): string
    {
        $filters = ['q' => (string) ($_GET['q'] ?? ''), 'modalidad' => (string) ($_GET['modalidad'] ?? ''), 'estado' => 'Activo'];
        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $filterForm = '<form method="GET" action="/catalogo_proyectos.php" class="form-card filter-card"><div class="filter-grid">'
            . '<div class="form-group"><label for="catalog-search">Buscar</label><input id="catalog-search" name="q" value="' . $escape($filters['q']) . '" placeholder="Nombre o código"></div>'
            . '<div class="form-group"><label for="catalog-mode">Modalidad</label><select id="catalog-mode" name="modalidad"><option value="">Todas</option>';
        foreach (['Presencial', 'Virtual', 'Mixta'] as $mode) {
            $filterForm .= '<option' . ($filters['modalidad'] === $mode ? ' selected' : '') . '>' . $mode . '</option>';
        }
        $filterForm .= '</select></div></div><div class="form-actions"><button class="btn-red" type="submit">Filtrar</button>'
            . '<a class="btn-gray" href="/catalogo_proyectos.php">Limpiar</a></div></form>';
        $cards = '';
        $projectModel = new Proyecto($this->conexion);
        $projects = $projectModel->all($filters);
        $activitiesByProject = $projectModel->activitiesForProjects(array_map(
            static fn(array $project): int => (int) $project['proyecto_id'],
            $projects
        ));
        $selectedActivityId = filter_var($_GET['actividad_id'] ?? null, FILTER_VALIDATE_INT);
        foreach ($projects as $project) {
            $activities = '';
            $selectedActivityFound = false;
            foreach ($activitiesByProject[(int) $project['proyecto_id']] ?? [] as $activity) {
                $activityId = (int) $activity['actividad_id'];
                $selectedActivityFound = $selectedActivityFound || $selectedActivityId === $activityId;
                $activities .= '<tr id="activity-' . $activityId . '"><td>' . $escape($activity['titulo']) . '</td><td>' . $escape($activity['fecha_inicio']) . ' – ' . $escape($activity['fecha_fin'])
                    . '</td><td>' . $escape($activity['lugar']) . '</td><td>' . $escape($activity['modalidad']) . '</td></tr>';
            }
            if ($activities === '') {
                $activities = '<tr><td colspan="4" class="empty-state">Las actividades aún no se han publicado.</td></tr>';
            }
            $contact = $project['responsable_correo'] ?? '';
            $contactHtml = $contact === '' ? $escape($project['responsable_nombre'] ?? 'No asignado')
                : '<a href="mailto:' . $escape($contact) . '">' . $escape($project['responsable_nombre'] ?? $contact) . ' · ' . $escape($contact) . '</a>';
            $cards .= '<article class="form-card project-card"><div class="page-header"><div><h2>' . $escape($project['nombre']) . '</h2>'
                . '<p class="page-description">' . $escape($project['codigo']) . ' · ' . $escape($project['modalidad']) . ' · ' . $escape($this->careerLabel((string) $project['carrera'])) . '</p></div>'
                . '<span class="badge badge-activo">Abierto</span></div>'
                . '<p><strong>Objetivo:</strong> ' . $escape($project['objetivo']) . '</p><p><strong>Descripción:</strong> ' . $escape($project['descripcion']) . '</p>'
                . '<p><strong>Requisitos:</strong> ' . $escape($project['requisitos'] ?? 'Consulta con la persona responsable.') . '</p>'
                . '<p><strong>Periodo:</strong> ' . $escape($project['fecha_inicio']) . ' – ' . $escape($project['fecha_fin'] ?? 'Sin fecha final') . '</p>'
                . '<p><strong>Responsable:</strong> ' . $contactHtml . '</p>'
                . '<details' . ($selectedActivityFound ? ' open' : '') . '><summary>Actividades publicadas</summary><div class="table-container"><table><thead><tr><th>Actividad</th><th>Horario</th><th>Lugar</th><th>Modalidad</th></tr></thead><tbody>'
                . $activities . '</tbody></table></div></details></article>';
        }
        if ($cards === '') {
            $cards = '<div class="form-card empty-state"><h2>No hay proyectos disponibles</h2><p>No se encontraron proyectos abiertos para los filtros seleccionados.</p><a href="/catalogo_proyectos.php">Limpiar filtros</a></div>';
        }
        return str_replace('<div id="project-catalog"></div>', $filterForm . $cards, $html);
    }

    // Este bloque reemplaza el contenido de proyectos por registros y acciones autorizadas.
    private function populateProjects(string $html): string
    {
        $f = ['q' => (string) ($_GET['q'] ?? ''), 'carrera' => (string) ($_GET['carrera'] ?? ''),
            'anio_academico' => (string) ($_GET['anio_academico'] ?? ''), 'modalidad' => (string) ($_GET['modalidad'] ?? ''),
            'estado' => (string) ($_GET['estado'] ?? '')];
        $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $modelo = new Proyecto($this->conexion);
        $puedeCrear = $this->userCan('proyectos.crear');
        $puedeActualizar = $this->userCan('proyectos.actualizar');
        $puedeAsignar = $this->userCan('proyectos.asignar');
        $idBruto = $_GET['id'] ?? null;
        $detalleId = $idBruto === null ? null : filter_var($idBruto, FILTER_VALIDATE_INT);
        if ($detalleId !== null && ($detalleId === false || $detalleId < 1)) {
            $detalleId = -1;
        }
        $rows = '';
        foreach ($detalleId === null ? $modelo->all($f) : [] as $proyecto) {
            $id = (int) $proyecto['proyecto_id'];
            $actions = '<a class="btn-action btn-edit" href="/proyectos.php?id=' . $id . '">Ver detalle</a>';
            if ($puedeActualizar) {
                $nuevoEstado = $proyecto['estado'] === 'Activo' ? 'Finalizado' : 'Activo';
                $etiqueta = $proyecto['estado'] === 'Activo' ? 'Cerrar proyecto' : 'Reactivar';
                $confirm = $proyecto['estado'] === 'Activo' ? ' onsubmit="return confirm(\'¿Cerrar el proyecto? Su historial se conservará.\')"' : '';
                $actions .= '<form action="/proyectos_actualizar.php" method="POST"' . $confirm . '>'
                    . '<input type="hidden" name="proyecto_id" value="' . $id . '">'
                    . '<input type="hidden" name="nombre" value="' . $esc($proyecto['nombre']) . '">'
                    . '<input type="hidden" name="codigo" value="' . $esc($proyecto['codigo']) . '">'
                    . '<input type="hidden" name="descripcion" value="' . $esc($proyecto['descripcion']) . '">'
                    . '<input type="hidden" name="objetivo" value="' . $esc($proyecto['objetivo'] ?? $proyecto['descripcion']) . '">'
                    . '<input type="hidden" name="requisitos" value="' . $esc($proyecto['requisitos'] ?? '') . '">'
                    . '<input type="hidden" name="carrera" value="' . $esc($proyecto['carrera']) . '">'
                    . '<input type="hidden" name="anio_academico" value="' . (int) $proyecto['anio_academico'] . '">'
                    . '<input type="hidden" name="modalidad" value="' . $esc($proyecto['modalidad']) . '">'
                    . '<input type="hidden" name="fecha_inicio" value="' . $esc($proyecto['fecha_inicio']) . '">'
                    . '<input type="hidden" name="fecha_fin" value="' . $esc($proyecto['fecha_fin'] ?? '') . '">'
                    . '<input type="hidden" name="responsable_id" value="' . (int) ($proyecto['responsable_id'] ?? 0) . '">'
                    . '<input type="hidden" name="estado" value="' . $nuevoEstado . '">'
                    . '<button class="btn-action btn-delete" type="submit">' . $etiqueta . '</button></form>';
            }
            $rows .= '<tr><td>' . $esc($proyecto['codigo']) . '</td><td><a href="/proyectos.php?id=' . $id . '">' . $esc($proyecto['nombre']) . '</a></td>'
                . '<td>' . $esc($proyecto['modalidad']) . '</td><td><span class="badge">' . $esc($proyecto['estado']) . '</span></td>'
                . '<td>' . $esc($proyecto['fecha_inicio']) . ' - ' . $esc($proyecto['fecha_fin'] ?? 'Sin fecha final') . '</td>'
                . '<td><div class="table-actions">' . $actions . '</div></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="6"><div class="empty-state">No hay proyectos para los filtros seleccionados. <a href="/proyectos.php">Limpiar filtros</a></div></td></tr>';
        }

        $responsables = '<option value="">Selecciona un responsable</option>';
        foreach ($this->internalUsers() as $user) {
            $responsables .= '<option value="' . (int) $user['usuario_id'] . '">' . $esc($user['nombre_completo']) . '</option>';
        }
        $panel = '<section class="form-card filter-card"><h2>Buscar proyectos</h2>'
            . '<form action="/proyectos.php" method="GET" class="filter-grid">'
            . '<div class="form-group"><label>Nombre o código</label><input name="q" value="' . $esc($f['q']) . '"></div>'
            . '<div class="form-group"><label>Carrera</label><input name="carrera" value="' . $esc($f['carrera']) . '"></div>'
            . '<div class="form-group"><label>Año académico</label><select name="anio_academico"><option value="">Todos</option>'
            . '<option value="1"' . ($f['anio_academico'] === '1' ? ' selected' : '') . '>1</option><option value="2"' . ($f['anio_academico'] === '2' ? ' selected' : '') . '>2</option></select></div>'
            . '<div class="form-group"><label>Modalidad</label><select name="modalidad"><option value="">Todas</option>';
        foreach (['Presencial', 'Virtual', 'Mixta'] as $modalidad) {
            $panel .= '<option' . ($f['modalidad'] === $modalidad ? ' selected' : '') . '>' . $modalidad . '</option>';
        }
        $panel .= '</select></div><div class="form-group"><label>Estado</label><select name="estado"><option value="">Todos</option>';
        foreach (['Activo', 'En Pausa', 'Finalizado', 'Inactivo'] as $estado) {
            $panel .= '<option' . ($f['estado'] === $estado ? ' selected' : '') . '>' . $estado . '</option>';
        }
        $panel .= '</select></div><div class="form-actions"><button class="btn-red" type="submit">Filtrar</button><a class="btn-gray" href="/proyectos.php">Limpiar filtros</a></div></form></section>';

        $createPanel = '';
        if ($puedeCrear) {
            $createPanel = '<details class="project-create-panel"><summary class="btn-red">+ Crear proyecto</summary><div class="project-create-content"><form action="/proyectos.php" method="POST">'
                . '<div class="form-grid"><div class="form-group"><label>Nombre *</label><input name="nombre" maxlength="150" required></div>'
                . '<div class="form-group"><label>Código *</label><input name="codigo" maxlength="40" required></div>'
                . '<div class="form-group full"><label>Descripción *</label><textarea name="descripcion" required></textarea></div>'
                . '<div class="form-group full"><label>Objetivo *</label><textarea name="objetivo" required></textarea></div>'
                . '<div class="form-group full"><label>Requisitos para participar</label><textarea name="requisitos" rows="2"></textarea></div>'
                . '<div class="form-group"><label>Carrera *</label><input name="carrera" maxlength="100" required></div>'
                . '<div class="form-group"><label>Año académico *</label><select name="anio_academico" required><option value="">Selecciona</option><option value="1">1</option><option value="2">2</option></select></div>'
                . '<div class="form-group"><label>Modalidad *</label><select name="modalidad" required><option value="">Selecciona</option><option>Presencial</option><option>Virtual</option><option>Mixta</option></select></div>'
                . '<div class="form-group"><label>Estado *</label><select name="estado" required><option selected>Activo</option><option>En Pausa</option></select></div>'
                . '<div class="form-group"><label>Fecha de inicio *</label><input name="fecha_inicio" type="date" required></div>'
                . '<div class="form-group"><label>Fecha de fin</label><input name="fecha_fin" type="date"></div>'
                . '<div class="form-group"><label>Responsable *</label><select name="responsable_id" required>' . $responsables . '</select></div></div>'
                . '<div class="form-actions form-actions-end"><button class="btn-red" type="submit">Guardar proyecto</button></div></form></div></details>';
        }

        $panel .= '<section class="table-container"><table><thead><tr><th>Código</th><th>Proyecto</th><th>Modalidad</th><th>Estado</th><th>Periodo</th><th>Acciones</th></tr></thead><tbody>' . $rows . '</tbody></table></section>';

        $toolbar = $puedeCrear
            ? '<a class="btn-red" href="/cronograma_nuevo.php">+ Nueva actividad</a>'
            : '';
        if ($detalleId !== null) {
            $toolbar = '<a class="btn-gray" href="/proyectos.php">← Volver a proyectos</a>';
            $createPanel = '';
            $html = str_replace('Gestión de proyectos</h1>', 'Detalle de proyecto</h1>', $html);
            $html = str_replace(
                'Consulta y administra proyectos, actividades y participantes.',
                'Consulta la información, el cronograma y las asignaciones del proyecto.',
                $html
            );
            $project = $detalleId > 0 ? $modelo->find($detalleId) : null;
            if ($project === null) {
                http_response_code(404);
                $panel = '<section class="form-card empty-state"><h2>Proyecto no encontrado</h2>'
                    . '<a class="btn-gray" href="/proyectos.php">Volver a proyectos</a></section>';
            } else {
                $detailActions = '';
                if ($project['estado'] === 'Activo' && $puedeCrear) {
                    $detailActions .= '<a class="btn-red" href="/cronograma_nuevo.php?proyecto_id=' . (int) $detalleId . '">Agregar actividad</a>';
                }
                if ($project['estado'] === 'Activo' && $puedeAsignar) {
                    $detailActions .= '<a class="btn-gray" href="/proyectos_asignar.php?proyecto_id=' . (int) $detalleId . '">Asignar estudiantes</a>';
                }
                $detail = '<div class="project-detail-page"><div class="project-detail-actions">' . $detailActions . '</div>'
                    . '<section class="form-card project-detail"><div class="page-header"><div><h2>' . $esc($project['nombre']) . '</h2>'
                    . '<p class="page-description">' . $esc($project['codigo']) . ' · ' . $esc($project['estado']) . '</p></div></div>'
                    . '<p><strong>Objetivo:</strong> ' . $esc($project['objetivo']) . '</p><p><strong>Descripción:</strong> ' . $esc($project['descripcion']) . '</p>'
                    . '<p><strong>Requisitos de participación:</strong> ' . $esc($project['requisitos'] ?? 'Consulta con la persona responsable.') . '</p>'
                    . '<p><strong>Responsable:</strong> ' . $esc($project['responsable_nombre'] ?? 'Sin responsable') . '</p>'
                    . '<p><strong>Periodo:</strong> ' . $esc($project['fecha_inicio']) . ' – ' . $esc($project['fecha_fin'] ?? 'Sin fecha final') . '</p></section>';
                if ($puedeActualizar) {
                    $options = '';
                    foreach ($this->internalUsers() as $user) {
                        $selected = (int) $user['usuario_id'] === (int) ($project['responsable_id'] ?? 0) ? ' selected' : '';
                        $options .= '<option value="' . (int) $user['usuario_id'] . '"' . $selected . '>' . $esc($user['nombre_completo']) . '</option>';
                    }
                    $detail .= '<details class="form-card project-edit-panel"><summary class="section-title">Editar proyecto</summary><form action="/proyectos_actualizar.php" method="POST">'
                        . '<input type="hidden" name="proyecto_id" value="' . (int) $detalleId . '"><div class="form-grid">'
                        . '<div class="form-group"><label>Nombre *</label><input name="nombre" maxlength="150" value="' . $esc($project['nombre']) . '" required></div>'
                        . '<div class="form-group"><label>Código *</label><input name="codigo" maxlength="40" value="' . $esc($project['codigo']) . '" required></div>'
                        . '<div class="form-group full"><label>Descripción *</label><textarea name="descripcion" required>' . $esc($project['descripcion']) . '</textarea></div>'
                        . '<div class="form-group full"><label>Objetivo *</label><textarea name="objetivo" required>' . $esc($project['objetivo']) . '</textarea></div>'
                        . '<div class="form-group full"><label>Requisitos para participar</label><textarea name="requisitos" rows="2">' . $esc($project['requisitos'] ?? '') . '</textarea></div>'
                        . '<div class="form-group"><label>Carrera *</label><input name="carrera" maxlength="100" value="' . $esc($project['carrera']) . '" required></div>'
                        . '<div class="form-group"><label>Año académico *</label><select name="anio_academico" required><option value="1"' . ((int) $project['anio_academico'] === 1 ? ' selected' : '') . '>1</option><option value="2"' . ((int) $project['anio_academico'] === 2 ? ' selected' : '') . '>2</option></select></div>'
                        . '<div class="form-group"><label>Modalidad *</label><select name="modalidad" required>';
                    foreach (['Presencial', 'Virtual', 'Mixta'] as $modalidad) {
                        $detail .= '<option' . ($project['modalidad'] === $modalidad ? ' selected' : '') . '>' . $modalidad . '</option>';
                    }
                    $detail .= '</select></div><div class="form-group"><label>Estado *</label><select name="estado" required>';
                    foreach (['Activo', 'En Pausa', 'Finalizado', 'Inactivo'] as $estado) {
                        $detail .= '<option' . ($project['estado'] === $estado ? ' selected' : '') . '>' . $estado . '</option>';
                    }
                    $detail .= '</select></div><div class="form-group"><label>Fecha de inicio *</label><input name="fecha_inicio" type="date" value="' . $esc($project['fecha_inicio']) . '" required></div>'
                        . '<div class="form-group"><label>Fecha de fin</label><input name="fecha_fin" type="date" value="' . $esc($project['fecha_fin'] ?? '') . '"></div>'
                        . '<div class="form-group"><label>Responsable *</label><select name="responsable_id" required><option value="">Selecciona</option>' . $options . '</select></div></div>'
                        . '<div class="form-actions form-actions-end"><button class="btn-red" type="submit">Guardar cambios</button></div></form></details>';
                }

                $activityRows = '';
                foreach ($modelo->activities((int) $detalleId) as $activity) {
                    $action = $puedeActualizar && $project['estado'] === 'Activo'
                        ? '<a href="/cronograma_nuevo.php?id=' . (int) $activity['actividad_id'] . '&proyecto_id=' . (int) $detalleId . '">Editar</a>'
                        : '';
                    $activityRows .= '<tr><td>' . $esc($activity['titulo']) . '<br><small>' . $esc($activity['modalidad']) . '</small></td><td>' . $esc($activity['fecha_inicio']) . ' – ' . $esc($activity['fecha_fin'])
                        . '</td><td>' . $esc($activity['lugar']) . '</td><td>' . $esc($activity['responsable_nombre'] ?? 'Sin responsable') . '</td><td>' . $action . '</td></tr>';
                }
                if ($activityRows === '') {
                    $activityRows = '<tr><td colspan="5" class="empty-state">No hay actividades registradas.</td></tr>';
                }
                $detail .= '<section class="table-container"><h2 class="section-title">Cronograma</h2><table><thead><tr><th>Actividad</th><th>Horario</th><th>Lugar</th><th>Responsable</th><th>Acciones</th></tr></thead><tbody>' . $activityRows . '</tbody></table>';
                $detail .= '</section>';

                $assignRows = '';
                foreach ($modelo->assignments((int) $detalleId) as $assignment) {
                    $status = $assignment['estado_participacion'];
                    $actions = '';
                    if ($puedeAsignar && $status === 'Activa') {
                        $actions = '<form action="/proyectos_desasignar.php" method="POST" onsubmit="return confirm(\'¿Retirar esta asignación? Su historial se conservará.\')">'
                            . '<input type="hidden" name="asignacion_id" value="' . (int) $assignment['asignacion_id'] . '">'
                            . '<button class="btn-action btn-delete" type="submit">Retirar</button></form>';
                    }
                    $assignRows .= '<tr><td>' . $esc($assignment['nombre_completo']) . '</td><td>' . $esc($assignment['carnet'] ?? '') . '</td>'
                        . '<td>' . $esc($assignment['funcion']) . '</td><td>' . $esc($status) . '</td>'
                        . '<td>' . $esc($assignment['fecha_asignacion']) . ($assignment['fecha_desasignacion'] === null ? '' : ' / ' . $esc($assignment['fecha_desasignacion'])) . '</td>'
                        . '<td><div class="table-actions">' . $actions . '</div></td></tr>';
                }
                if ($assignRows === '') {
                    $assignRows = '<tr><td colspan="6" class="empty-state">No hay estudiantes asignados.</td></tr>';
                }
                $detail .= '<section class="table-container"><h2 class="section-title">Estudiantes asignados</h2><table><thead><tr><th>Estudiante</th><th>Carnet</th><th>Función</th><th>Estado</th><th>Historial</th><th>Acciones</th></tr></thead><tbody>' . $assignRows . '</tbody></table>';
                $detail .= '</section></div>';
                $panel = $detail;
            }
        }

        $html = preg_replace('~<div id="project-toolbar"[^>]*></div>~', $toolbar, $html, 1) ?? $html;
        $html = str_replace('<div id="project-create-panel"></div>', $createPanel, $html);
        return str_replace('<div id="project-management"></div>', $panel, $html);
    }

    // Este bloque prepara actividades y estudiantes disponibles para registrar horas.
    private function populateHoursCreate(string $html): string
    {
        $usuario = $this->currentUser();
        $proyectos = new Proyecto($this->conexion);
        $options = '<option value="">Seleccione la actividad...</option>';
        $studentId = filter_var($_GET['estudiante_id'] ?? null, FILTER_VALIDATE_INT);
        $studentSearch = trim((string) ($_GET['q'] ?? ''));
        $studentOptions = '';
        $studentSelector = '';
        $canReportForOthers = $this->userCan('horas.registrar_para_estudiantes');

        if ($canReportForOthers) {
            $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            foreach ($proyectos->activeStudents($studentSearch) as $student) {
                $id = (int) $student['estudiante_id'];
                $selected = $studentId === $id ? ' selected' : '';
                $studentOptions .= '<option value="' . $id . '"' . $selected . '>' . $esc($student['nombre_completo'])
                    . ' · ' . $esc($student['carnet'] ?? '') . '</option>';
            }
            if ($studentOptions === '') {
                $studentOptions = '<option value="">No hay estudiantes activos disponibles.</option>';
            }
            $studentSelector = '<form method="GET" action="/horas_registrar.php" class="form-card filter-card">'
                . '<div class="form-group"><label for="hours-student-search">Buscar estudiante</label><input id="hours-student-search" name="q" value="' . $esc($studentSearch) . '" placeholder="Nombre, carnet o correo"></div>'
                . '<div class="form-group"><label for="hours-student">Estudiante</label><select id="hours-student" name="estudiante_id" required>'
                . '<option value="">Selecciona un estudiante activo</option>' . $studentOptions
                . '</select></div><div class="form-actions"><button class="btn-red" type="submit">Cargar actividades</button></div></form>';
            if ($studentId !== false && $studentId !== null && $studentId > 0) {
                foreach ($proyectos->activitiesForStudentRecord((int) $studentId) as $activity) {
                    $options .= '<option value="' . (int) $activity['actividad_id'] . '">' . $esc($activity['proyecto'] . ' - ' . $activity['titulo']) . '</option>';
                }
                $html = preg_replace_callback(
                    '~<form\b(?=[^>]*\bmethod="POST")(?=[^>]*\baction="/horas_registrar\.php")[^>]*>~i',
                    static fn (array $match): string => $match[0]
                        . '<input type="hidden" name="estudiante_id" value="' . (int) $studentId . '">'
                        . '<input type="hidden" name="q" value="' . $esc($studentSearch) . '">',
                    $html,
                    1
                ) ?? $html;
            }
            $html = str_replace('<form action="/horas_registrar.php"', $studentSelector . '<form action="/horas_registrar.php"', $html);
            $html = str_replace('href="/estudiantes_resumen_horas.php" class="btn-gray">Cancelar', 'href="/horas_bandeja.php" class="btn-gray">Cancelar', $html);
        } elseif ($usuario !== null) {
            foreach ($proyectos->activitiesForStudent((int) $usuario['usuario_id']) as $activity) {
                $options .= '<option value="' . (int) $activity['actividad_id'] . '">' . htmlspecialchars($activity['proyecto'] . ' - ' . $activity['titulo'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</option>';
            }
        }

        return preg_replace_callback(
            '~<select\b(?=[^>]*\bname="actividad_id")(?=[^>]*\brequired\b)[^>]*>.*?</select>~is',
            static fn (): string => '<select id="activity" name="actividad_id" required>' . $options . '</select>',
            $html,
            1
        ) ?? $html;
    }

    // Este bloque muestra los registros de horas pendientes y sus acciones de revisión.
    private function populateHoursReview(string $html): string
    {
        $escape = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $status = in_array($_GET['estado'] ?? 'Pendiente', ['Pendiente', 'Aprobado', 'Rechazado'], true)
            ? (string) ($_GET['estado'] ?? 'Pendiente')
            : 'Pendiente';
        $rows = '';
        foreach ((new Proyecto($this->conexion))->pendingHours($status) as $registro) {
            $id = (int) $registro['registro_id'];
            $actions = $status === 'Pendiente'
                ? '<form action="/horas_bandeja.php" method="POST"><input type="hidden" name="registro_id" value="' . $id . '">'
                    . '<button class="btn-action btn-edit" name="decision" value="Aprobado" type="submit">Aprobar</button></form>'
                    . '<form action="/horas_bandeja.php" method="POST"><input type="hidden" name="registro_id" value="' . $id . '">'
                    . '<input type="hidden" name="decision" value="Rechazado"><label class="sr-only" for="motivo-' . $id . '">Motivo del rechazo</label>'
                    . '<input id="motivo-' . $id . '" name="motivo" required maxlength="500" placeholder="Motivo del rechazo">'
                    . '<button class="btn-action btn-delete" type="submit">Rechazar</button></form>'
                : $escape($registro['estado']) . ($registro['motivo_revision'] ? '<br><small>' . $escape($registro['motivo_revision']) . '</small>' : '');
            $rows .= '<tr><td><strong>' . $escape($registro['nombre_completo']) . '</strong><br><small>' . $escape($registro['carnet'] ?? '') . '</small></td>'
                . '<td>' . $escape($registro['proyecto']) . '<br><small>' . $escape($registro['titulo']) . '</small></td>'
                . '<td>' . $escape($registro['fecha_actividad']) . '</td><td>' . $escape($registro['horas']) . '</td>'
                . '<td>' . $escape($registro['descripcion']) . '</td><td>' . $escape($registro['reportado_por_nombre'] ?? 'Sin dato') . '</td>'
                . '<td><div class="table-actions">' . $actions . '</div></td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="7"><div class="empty-state">No hay registros ' . strtolower($escape($status)) . '.</div></td></tr>';
        }
        $html = str_replace('Registros pendientes', 'Registros ' . strtolower($escape($status)), $html);
        $filters = '<nav class="form-actions"><a href="/horas_bandeja.php?estado=Pendiente">Pendientes</a> · '
            . '<a href="/horas_bandeja.php?estado=Aprobado">Aprobadas</a> · '
            . '<a href="/horas_bandeja.php?estado=Rechazado">Rechazadas</a></nav>';
        $html = str_replace('<section class="table-container">', $filters . '<section class="table-container">', $html);
        return preg_replace_callback('~<tbody>.*?</tbody>~is', static fn (): string => '<tbody>' . $rows . '</tbody>', $html, 1) ?? $html;
    }

    // Este bloque presenta el resumen de horas personales y sus filtros.
    private function populateHoursSummary(string $html): string
    {
        $usuario = $this->currentUser();
        $studentId = $usuario === null ? null : (new RegistroHoras($this->conexion))->studentIdForUser((int) $usuario['usuario_id']);
        if ($studentId === null) {
            http_response_code(403);
            return str_replace(
                '<section id="hours-summary">',
                '<section id="hours-summary"><div class="alert alert-error" role="alert">Tu cuenta no tiene un expediente activo asociado. Contacta a coordinación.</div>',
                $html
            );
        }

        $desde = (string) ($_GET['desde'] ?? '');
        $hasta = (string) ($_GET['hasta'] ?? '');
        $modelo = new Proyecto($this->conexion);
        $alerta = '';
        try {
            if ($desde !== '' && $hasta !== '' && $desde > $hasta) {
                throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
            }
            $summary = $modelo->hoursSummary($studentId, $desde, $hasta);
        } catch (InvalidArgumentException $error) {
            $alerta = '<div class="alert alert-error" role="alert">' . htmlspecialchars($error->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</div>';
            $summary = ['total' => 0, 'pendientes' => 0, 'rechazadas' => 0, 'desglose' => []];
        }

        foreach ([
            'approved-hours' => $summary['total'],
            'pending-hours' => $summary['pendientes'],
            'rejected-hours' => $summary['rechazadas'],
        ] as $id => $horas) {
            $html = preg_replace(
                '~(<div class="number" id="' . preg_quote($id, '~') . '">).*?(</div>)~s',
                '$1' . number_format((float) $horas, 2) . '$2',
                $html,
                1
            ) ?? $html;
        }

        $esc = static fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $rows = '';
        foreach ($summary['desglose'] as $registro) {
            $rows .= '<tr><td>' . $esc($registro['proyecto']) . '</td><td>' . $esc($registro['periodo']) . '</td>'
                . '<td>' . number_format((float) $registro['horas_aprobadas'], 2) . '</td>'
                . '<td>' . number_format((float) $registro['horas_pendientes'], 2) . '</td>'
                . '<td>' . number_format((float) $registro['horas_rechazadas'], 2) . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td colspan="5"><div class="empty-state">No hay registros para el periodo seleccionado.</div></td></tr>';
        }
        $html = preg_replace_callback('~<tbody>.*?</tbody>~is', static fn (): string => '<tbody>' . $rows . '</tbody>', $html, 1) ?? $html;
        return str_replace('<section id="hours-summary">', '<section id="hours-summary">' . $alerta, $html);
    }
}
