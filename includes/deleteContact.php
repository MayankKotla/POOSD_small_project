<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Pulls the logged-in user's ID from the session cookie.
$userId = require_user_id();

$body = read_json_body();

// Strict integer check: reject anything that isn't a clean integer
// (e.g. "1.9") instead of silently truncating it to 1.
$rawId = $body['id'] ?? null;
if (!is_int($rawId) && !(is_string($rawId) && ctype_digit($rawId))) {
    fail('A valid integer contact id is required.', 400);
}
$contactId = (int) $rawId;

$db = get_db();

// Ownership scoping: WHERE id = ? AND userId = ? means a contact that
// exists but belongs to another user simply won't match, and we
// return the same 404 either way.
$stmt = $db->prepare('DELETE FROM Contacts WHERE id = ? AND userId = ?');
$stmt->bind_param('ii', $contactId, $userId);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    fail('Could not delete contact.', 500);
}

$deleted = $stmt->affected_rows > 0;
$stmt->close();
$db->close();

if (!$deleted) {
    fail('Contact not found.', 404);
}

respond([
    'success' => true,
    'error' => '',
]);
