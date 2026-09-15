<?php
declare(strict_types=1);

require_once __DIR__ . '/api.php';

function start_auth_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name('team12_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');

    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (!session_start()) {
        throw new RuntimeException('Could not start the session.');
    }
}

// Get the account ID from the session, not from request data.
function require_user_id(): int
{
    start_auth_session();
    $userId = $_SESSION['userId'] ?? null;

    if (!is_int($userId) || $userId < 1) {
        fail('Please log in first.', 401);
    }

    return $userId;
}
