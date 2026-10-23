<?php
declare(strict_types=1);

final class HttpController
{
    public function __construct(private PDO $pdo)
    {
    }

    public function redirect(string $path): never
    {
        header('Location: ' . $path, true, 303);
        exit;
    }

    public function rememberErrors(array $errors, string $form = ''): void
    {
        $_SESSION['_form_errors'] = $errors;
        $_SESSION['_form_name'] = $form;
    }

    public function render(string $view): void
    {
        $allowedViews = ['login.php', 'dashboard.php', 'dashboard_admin.php', 'roles.php', 'personal.php', 'estudiante.php'];

        if (!in_array($view, $allowedViews, true)) {
            throw new RuntimeException('Vista no permitida.');
        }

        $viewPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $view;
        ob_start();
        require $viewPath;
        $html = (string) ob_get_clean();
        $errors = $_SESSION['_form_errors'] ?? [];
        $form = $_SESSION['_form_name'] ?? '';
        unset($_SESSION['_form_errors']);
        unset($_SESSION['_form_name']);

        foreach ($errors as $field => $message) {
            $html = $this->insertFieldError($html, (string) $field, (string) $message);
        }

        if (isset($errors['access'])) {
            $notice = '<p role="alert" style="padding:1rem;color:#991b1b;background:#fee2e2">'
                . htmlspecialchars((string) $errors['access'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</p>';
            $html = str_replace('</body>', $notice . '</body>', $html);
        }

        if ($view === 'login.php' && $form === 'register') {
            $html = str_replace(
                '</body>',
                '<script>switchTab("register");</script></body>',
                $html
            );
        }

        if ($view === 'roles.php') {
            $html = $this->populateRoles($html);
        }

        if ($view === 'dashboard_admin.php') {
            $html = $this->populateDashboard($html);
        }

        $token = $_SESSION['_csrf_token'] ??= bin2hex(random_bytes(32));
        $escapedToken = htmlspecialchars($token, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = preg_replace_callback(
            '~<form\b(?=[^>]*\bmethod="POST")[^>]*>~i',
            static fn (array $matches): string => $matches[0]
                . '<input type="hidden" name="csrf_token" value="' . $escapedToken . '">',
            $html
        ) ?? $html;

        echo $html;
    }

    public function verifyCsrfToken(): void
    {
        $expected = $_SESSION['_csrf_token'] ?? '';
        $provided = (string) ($_POST['csrf_token'] ?? '');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            http_response_code(400);
            exit('Solicitud no válida. Recarga la página e inténtalo de nuevo.');
        }
    }

    public function currentUser(): ?array
    {
        $userId = $_SESSION['user_id'] ?? null;

        if (!is_int($userId)) {
            return null;
        }

        $user = (new User($this->pdo))->findActiveById($userId);
        if ($user === null) {
            unset($_SESSION['user_id']);
            session_regenerate_id(true);
        }

        return $user;
    }

    public function requireAuthentication(): array
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->redirect('/login.php');
        }

        return $user;
    }

    public function requirePermission(string $permission): array
    {
        $user = $this->requireAuthentication();
        if (!(new Role($this->pdo))->userHasPermission((int) $user['id'], $permission)) {
            http_response_code(403);
            exit('No tienes permisos para realizar esta acción.');
        }

        return $user;
    }

    public function insertFieldError(string $html, string $field, string $message): string
    {
        $escapedName = preg_quote($field, '~');
        $error = '<small class="field-error" role="alert" style="display:block;color:#dc2626;margin-top:.25rem">'
            . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</small>';

        $inputPattern = '~(<input\b(?=[^>]*\bname="' . $escapedName . '")[^>]*>)~i';
        if (preg_match($inputPattern, $html) === 1) {
            return preg_replace($inputPattern, '$1' . $error, $html, 1) ?? $html;
        }

        $selectPattern = '~(<select\b(?=[^>]*\bname="' . $escapedName . '")[^>]*>.*?</select>)~is';

        return preg_replace($selectPattern, '$1' . $error, $html, 1) ?? $html;
    }

    private function populateRoles(string $html): string
    {
        $users = (new User($this->pdo))->allWithRoles();
        $roles = (new Role($this->pdo))->all();
        $rows = '';

        foreach ($users as $user) {
            $id = (int) $user['id'];
            $name = htmlspecialchars($user['fullname'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $email = htmlspecialchars($user['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $roleName = htmlspecialchars($user['role_name'] ?? 'Sin rol', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $roleLabel = htmlspecialchars($user['role_label'] ?? 'Sin rol', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $badgeClass = htmlspecialchars($user['role_name'] ?? 'estudiante', ENT_QUOTES, 'UTF-8');
            $rows .= '<tr><td>' . $name . '</td><td>' . $email . '</td><td><span class="badge '
                . $badgeClass . '">' . $roleLabel . '</span></td><td>';
            $rows .= '<form action="/admin/assign-role" method="POST" style="display:inline">'
                . '<input type="hidden" name="user_id" value="' . $id . '"><select name="role_id" required>';

            foreach ($roles as $role) {
                $selected = (int) $role['id'] === (int) $user['role_id'] ? ' selected' : '';
                $label = htmlspecialchars($role['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $rows .= '<option value="' . (int) $role['id'] . '"' . $selected . '>' . $label . '</option>';
            }

            $rows .= '</select><button class="btn-action btn-edit" type="submit">Editar rol</button></form> ';
            $activeLabel = (int) $user['active'] === 1 ? 'Desactivar' : 'Activar';
            $confirm = (int) $user['active'] === 1
                ? ' onsubmit="return confirm(\'¿Confirmas que deseas desactivar esta cuenta?\')"'
                : '';
            $rows .= '<form action="/admin/toggle-status" method="POST" style="display:inline"' . $confirm . '>'
                . '<input type="hidden" name="user_id" value="' . $id . '">'
                . '<input type="hidden" name="active" value="' . ((int) $user['active'] === 1 ? '0' : '1') . '">'
                . '<button class="btn-action btn-delete" type="submit">' . $activeLabel . '</button></form>';

            if ((int) $user['active'] === 0) {
                $rows .= '<strong> (Cuenta desactivada)</strong>';
            }
            $rows .= '</td></tr>';
        }

        return preg_replace('~<tbody>.*?</tbody>~is', '<tbody>' . $rows . '</tbody>', $html, 1) ?? $html;
    }

    private function populateDashboard(string $html): string
    {
        $counts = [
            'students' => (int) $this->pdo->query('SELECT COUNT(*) FROM students')->fetchColumn(),
            'staff' => (int) $this->pdo->query(
                "SELECT COUNT(*) FROM users WHERE active = 1 AND id IN (
                    SELECT ur.user_id FROM user_roles ur JOIN roles r ON r.id = ur.role_id WHERE r.name != 'estudiante'
                )"
            )->fetchColumn(),
            'roles' => (int) $this->pdo->query('SELECT COUNT(*) FROM roles')->fetchColumn(),
        ];
        $numbers = array_map(
            static fn (int $count): string => '<div class="number">' . $count . '</div>',
            array_values($counts)
        );
        $index = 0;

        return preg_replace_callback(
            '~<div class="number">.*?</div>~s',
            static function () use (&$index, $numbers): string {
                return $numbers[$index++] ?? '';
            },
            $html,
            3
        ) ?? $html;
    }
}
