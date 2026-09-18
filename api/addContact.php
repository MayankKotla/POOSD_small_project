<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';

require_method('POST');
$userId = require_user_id();
$fields = read_contact_fields(read_json_body());
$db = get_db();
$stmt = $db->prepare('INSERT INTO Contacts (FirstName, LastName, Phone, Email, UserID) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('ssssi', $fields['firstName'], $fields['lastName'], $fields['phone'], $fields['email'], $userId);
$stmt->execute();
$id = $db->insert_id;
$stmt->close();
$db->close();
respond(['success' => true, 'id' => $id, 'error' => ''], 201);
