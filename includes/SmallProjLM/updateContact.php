<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Pulls the logged-in user's ID from the session cookie.
$userId = require_user_id();

$body = read_json_body();

$contactId = $body['id'] ?? null;
if (!is_numeric($contactId)) {
    fail('A valid contact id is required.', 400);
}
$contactId = (int) $contactId;

// Only the fields the client actually sent get updated — everything
// else on the row is left alone. Allow-list the columns so a client
// can't sneak in an update to some other column via the JSON body.
$allowedFields = ['firstName', 'lastName', 'phone', 'email'];

$setClauses = [];
$values = [];
$types = '';

foreach ($allowedFields as $field) {
    if (!array_key_exists($field, $body)) {
        continue; // field wasn't sent — leave it untouched
    }

    $value = trim((string) $body[$field]);

    if ($value === '') {
        fail("$field cannot be empty.", 400);
    }

    if ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        fail('Invalid email format.', 400);
    }

    $setClauses[] = "$field = ?";
    $values[] = $value;
    $types .= 's';
}

if (empty($setClauses)) {
    fail('At least one field (firstName, lastName, phone, email) must be provided.', 400);
}

// Append id and userId for the WHERE clause, in bind order.
$values[] = $contactId;
$values[] = $userId;
$types .= 'ii';

$db = get_db();

// Ownership check: only update the row if it belongs to the logged-in
// user. Scoping the WHERE clause by userId does this in one query —
// if the contact exists but belongs to someone else, affected_rows
// will be 0, same as if the id didn't exist at all. We don't leak
// which case it was.
$sql = 'UPDATE Contacts SET ' . implode(', ', $setClauses) . ' WHERE id = ? AND userId = ?';
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$values);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    fail('Could not update contact.', 500);
}

$updated = $stmt->affected_rows > 0;
$stmt->close();
$db->close();

if (!$updated) {
    fail('Contact not found.', 404);
}

respond([
    'success' => true,
    'error' => '',
]);
