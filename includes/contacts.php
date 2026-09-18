<?php
declare(strict_types=1);
require_once __DIR__ . '/api.php';

function read_contact_id(array $body): int
{
    $id = $body['id'] ?? null;
    if ((!is_int($id) && !(is_string($id) && preg_match('/^[1-9][0-9]{0,9}$/D', $id)))
        || $id < 1 || $id > 2147483647) {
        fail('Enter a valid contact ID.');
    }
    return (int) $id;
}

function read_contact_fields(array $body, bool $partial = false): array
{
    $fields = [];
    foreach (['firstName' => 'First name', 'lastName' => 'Last name', 'phone' => 'Phone', 'email' => 'Email'] as $key => $label) {
        if ($partial && !array_key_exists($key, $body)) {
            continue;
        }
        $value = read_name($body, $key, $label);
        if ($key === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            fail('Enter a valid contact email address.');
        }
        $fields[$key] = $value;
    }
    if (!$fields) {
        fail('Provide at least one contact field to update.');
    }
    return $fields;
}

function read_page_number(string $key, int $default, int $min, int $max): int
{
    $value = $_GET[$key] ?? null;
    if ($value === null) {
        return $default;
    }
    if (!is_string($value) || !preg_match('/^(0|[1-9][0-9]{0,9})$/D', $value)
        || $value < $min || $value > $max) {
        fail('Invalid ' . $key . '.');
    }
    return (int) $value;
}

function contact_results(mysqli_stmt $stmt): array
{
    $stmt->bind_result($id, $firstName, $lastName, $phone, $email, $dateAdded);
    $contacts = [];
    while ($stmt->fetch()) {
        $contacts[] = ['id' => (int) $id, 'firstName' => $firstName, 'lastName' => $lastName,
            'phone' => $phone, 'email' => $email, 'dateAdded' => $dateAdded];
    }
    return $contacts;
}
