<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/contacts.php';

require_method('GET');
$userId = require_user_id();
$search = $_GET['search'] ?? '';
if (!is_string($search) || !preg_match('//u', $search)) {
    fail('Search must be valid text.');
}
$search = trim($search);
if (preg_match_all('/./us', $search) > 100 || preg_match('/[\x00-\x1f\x7f]/', $search)) {
    fail('Search must be at most 100 characters with no control characters.');
}
$limit = read_page_number('limit', 50, 1, 100);
$offset = read_page_number('offset', 0, 0, 2147483647);
// Treat SQL wildcard characters as literal search text.
$pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
$db = get_db();
$stmt = $db->prepare("SELECT ID, FirstName, LastName, Phone, Email, DateAdded FROM Contacts
    WHERE UserID = ? AND (FirstName LIKE ? ESCAPE '!' OR LastName LIKE ? ESCAPE '!'
    OR CONCAT(FirstName, ' ', LastName) LIKE ? ESCAPE '!' OR Phone LIKE ? ESCAPE '!'
    OR Email LIKE ? ESCAPE '!') ORDER BY LastName, FirstName, ID LIMIT ? OFFSET ?");
$stmt->bind_param('isssssii', $userId, $pattern, $pattern, $pattern, $pattern, $pattern, $limit, $offset);
$stmt->execute();
$contacts = contact_results($stmt);
$stmt->close();
$db->close();
respond($contacts);
