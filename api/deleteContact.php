<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';

require_method('POST');
$userId = require_user_id();
$id = read_contact_id(read_json_body());
$db = get_db();
$stmt = $db->prepare('DELETE FROM Contacts WHERE ID = ? AND UserID = ?');
$stmt->bind_param('ii', $id, $userId);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();
$db->close();
if ($deleted === 0) {
    fail('Contact not found.', 404);
}
respond(['success' => true, 'error' => '']);
