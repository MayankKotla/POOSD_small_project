<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';

require_method('GET');
$userId = require_user_id();
$limit = read_page_number('limit', 50, 1, 100);
$offset = read_page_number('offset', 0, 0, 2147483647);
$db = get_db();
$stmt = $db->prepare('SELECT ID, FirstName, LastName, Phone, Email, DateAdded FROM Contacts WHERE UserID = ? ORDER BY LastName, FirstName, ID LIMIT ? OFFSET ?');
$stmt->bind_param('iii', $userId, $limit, $offset);
$stmt->execute();
$contacts = contact_results($stmt);
$stmt->close();
$db->close();
respond($contacts);
