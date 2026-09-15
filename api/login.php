<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$body = read_json_body();
$email = read_email($body);
$password = read_password($body);

$db = get_db();
$stmt = $db->prepare('SELECT ID, Password FROM Users WHERE Login = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($userId, $passwordHash);
$userFound = $stmt->fetch();
$stmt->close();
$db->close();

if (!$userFound || !password_verify($password, $passwordHash)) {
    fail('Incorrect email or password.', 401);
}

start_auth_session();

// Replace the old session ID so it cannot be reused after login.
if (!session_regenerate_id(true)) {
    throw new RuntimeException('Could not renew the session.');
}
$_SESSION = ['userId' => (int) $userId];

respond([
    'success' => true,
    'id' => (int) $userId,
    'error' => '',
]);
