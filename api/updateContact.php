<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';

require_method('POST');
$userId = require_user_id();
$body = read_json_body();
$id = read_contact_id($body);
$fields = read_contact_fields($body, true);
$columns = ['firstName' => 'FirstName', 'lastName' => 'LastName', 'phone' => 'Phone', 'email' => 'Email'];
$assignments = [];
foreach ($fields as $key => $value) {
    $assignments[] = $columns[$key] . ' = ?';
}
$values = array_values($fields);
$values[] = $id;
$values[] = $userId;
$db = get_db();
$db->begin_transaction();
try {
    // Lock the owned row so an unchanged update succeeds without a delete race.
    $check = $db->prepare('SELECT ID FROM Contacts WHERE ID = ? AND UserID = ? FOR UPDATE');
    $check->bind_param('ii', $id, $userId);
    $check->execute();
    $check->store_result();
    $found = $check->num_rows > 0;
    $check->close();
    if (!$found) {
        $db->rollback();
        $db->close();
        fail('Contact not found.', 404);
    }
    $stmt = $db->prepare('UPDATE Contacts SET ' . implode(', ', $assignments) . ' WHERE ID = ? AND UserID = ?');
    $stmt->bind_param(str_repeat('s', count($fields)) . 'ii', ...$values);
    $stmt->execute();
    $stmt->close();
    $db->commit();
} catch (Throwable $error) {
    $db->rollback();
    throw $error;
}
$db->close();
respond(['success' => true, 'error' => '']);
