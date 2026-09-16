<?php
declare(strict_types=1);

require_once __DIR__ . '/../api.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Pulls the logged-in user's ID from the session cookie — never trust
// a client-supplied userId for who owns this contact.
$userId = require_user_id();

$body = read_json_body();

$firstName = trim((string) ($body['firstName'] ?? ''));
$lastName  = trim((string) ($body['lastName'] ?? ''));
$phone     = trim((string) ($body['phone'] ?? ''));
$email     = trim((string) ($body['email'] ?? ''));

if ($firstName === '' || $lastName === '' || $phone === '' || $email === '') {
    fail('firstName, lastName, phone, and email are all required.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Invalid email format.', 400);
}

$db = get_db();
$stmt = $db->prepare(
    'INSERT INTO Contacts (userId, firstName, lastName, phone, email) VALUES (?, ?, ?, ?, ?)'
);
$stmt->bind_param('issss', $userId, $firstName, $lastName, $phone, $email);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    fail('Could not add contact.', 500);
}

$stmt->close();
$db->close();

respond([
    'success' => true,
    'error' => '',
]);
