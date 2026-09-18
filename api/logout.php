<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_method('POST');
start_auth_session();
$_SESSION = [];
$params = session_get_cookie_params();
if (!session_destroy()) {
    throw new RuntimeException('Could not end the session.');
}
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $params['path'],
    'domain' => $params['domain'], 'secure' => $params['secure'],
    'httponly' => $params['httponly'], 'samesite' => $params['samesite']]);
respond(['success' => true, 'error' => '']);
