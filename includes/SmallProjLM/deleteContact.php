<?php
declare(strict_types=1);

require_once __DIR__ . '/../api.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

// Pulls the logged-in user's ID from the session cookie.
$userId = require_user_id();

$body = read_json_body();

$contactId = $body['id'] ?? null;

if (!is_numeric($contactId)) {
    fail('A valid contact id is required.', 400);
}
$contactId = (int) $contactId;

$db = get_db();

// Same ownership scoping as updateContact.php: WHERE id = ? AND userId = ?
// means a contact that exists but belongs to another user simply won't
// match, and we return the same 404 either way.
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
