<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/api.php';
require_once __DIR__ . '/../includes/db.php';

$body = read_json_body();
$firstName = read_name($body, 'firstName', 'First name');
$lastName = read_name($body, 'lastName', 'Last name');
$email = read_email($body);
$password = read_password($body);
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$db = get_db();
// The existing schema stores the account email in Users.Login.
$statement = $db->prepare('INSERT INTO Users (FirstName, LastName, Login, Password) VALUES (?, ?, ?, ?)');
$statement->bind_param('ssss', $firstName, $lastName, $email, $passwordHash);

try {
    $statement->execute();
} catch (mysqli_sql_exception $error) {
    // The unique Login index also handles simultaneous duplicate signups.
    if ($error->getCode() === 1062) {
        fail('An account with that email already exists.', 409);
    }
    throw $error;
}

$statement->close();
$db->close();
respond(['success' => true, 'error' => ''], 201);
