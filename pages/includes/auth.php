<?php
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    function isLoggedIn() {
        return isset($_SESSION['user_id'] ) && !empty($_SESSION['user_id']);
    }

    function getRole() {
        return $_SESSION['role'] ?? null;
    }

    function isAdmin() {
        return getRole() === 'admin';
    }
    function isUser() {
        return getRole() === 'user';
    }
    function isStudent() {
        return getRole() === 'student';
    }
    function currentUserId(): int {
    return (int)($_SESSION['user_id'] ?? 0);
    }
    function currentUserName(): string {
    return htmlspecialchars($_SESSION['user_name'] ?? '');
    }
    
    function requireLogin(string $redirectTo = '../auth/login.php') {
        if (!isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }
    function requireAdmin(string $redirect = '../../index.php'): void {
        requireLogin($redirect);
        if (!isAdmin()) {
            header("Location: $redirect");
            exit;
        }
    }
    function requireRole(array $role, string $redirect = '../../index.php'): void {
        requireLogin($redirect);
        if (!in_array(getRole(), $role)) {
            header("Location: $redirect");
            exit;
        }
    }
    function setFlashMessage(string $message, string $type = 'success'): void {
        $_SESSION['flash_message'] = ['message' => $message, 'type' => $type];
    }
    function getFlashMessage(): ?array {
        if (isset($_SESSION['flash_message'])) {
            $flash = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $flash;
        }
        return null;
    }
    function renderFlash(): void {
    $flash = getFlashMessage();
        if (!$flash) return;
        $cls = match($flash['type']) {
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        default   => 'alert-info',
        };
        echo '<div class="alert '.$cls.' alert-dismissible fade show" role="alert">'
         . htmlspecialchars($flash['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    }

    function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}
?>