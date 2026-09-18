<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

const MAX_FIELD_LENGTH = 50;

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

    if (mb_strlen($value) > MAX_FIELD_LENGTH) {
        fail("$field must be " . MAX_FIELD_LENGTH . " characters or fewer.", 400);
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

$db = get_db();

// Existence/ownership check FIRST, separately from the update. This is
// the fix for the false "Contact not found": affected_rows from the
// UPDATE alone can be 0 either because the row doesn't exist/isn't
// yours, OR because the new values are identical to what's already
// there. Checking existence up front removes that ambiguity.
$checkStmt = $db->prepare('SELECT id FROM Contacts WHERE id = ? AND userId = ? LIMIT 1');
$checkStmt->bind_param('ii', $contactId, $userId);
$checkStmt->execute();
$checkStmt->store_result();
$exists = $checkStmt->num_rows > 0;
$checkStmt->close();

if (!$exists) {
    $db->close();
    fail('Contact not found.', 404);
}

// Append id and userId for the WHERE clause, in bind order.
$values[] = $contactId;
$values[] = $userId;
$types .= 'ii';

$sql = 'UPDATE Contacts SET ' . implode(', ', $setClauses) . ' WHERE id = ? AND userId = ?';
$stmt = $db->prepare($sql);
$stmt->bind_param($types, ...$values);

if (!$stmt->execute()) {
    $stmt->close();
    $db->close();
    fail('Could not update contact.', 500);
}

// We already confirmed the contact exists and belongs to this user,
// so this is a success regardless of affected_rows — a 0 here just
// means the submitted values matched what was already saved.
$stmt->close();
$db->close();

respond([
    'success' => true,
    'error' => '',
]);
