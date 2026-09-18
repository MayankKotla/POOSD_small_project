<?php
declare(strict_types=1);

// Shared JSON responses and input validation for the PHP endpoints.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function fail(string $message, int $status = 400): void
{
    respond(['success' => false, 'error' => $message], $status);
}

set_exception_handler(function (Throwable $error): void {
    // Keep database details and credentials out of HTTP responses and logs.
    error_log('API failure: ' . get_class($error) . ' (code ' . $error->getCode() . ')');
    fail('The server could not complete the request. Please try again.', 500);
});

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $method) {
        header('Allow: ' . $method);
        fail('Use ' . $method . ' for this endpoint.', 405);
    }
}

function read_json_body(): array
{
    require_method('POST');
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') {
        fail('Send the request as application/json.', 415);
    }

    // Read at most 16 KiB plus one byte to detect oversized input.
    $raw = file_get_contents('php://input', false, null, 0, 16385);
    if ($raw === false) {
        fail('Could not read the request.');
    }
    if (strlen($raw) > 16384) {
        fail('The request is too large.', 413);
    }

    try {
        $body = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        fail('Send a valid JSON object.');
    }
    if (!$body instanceof stdClass) {
        fail('Send a JSON object containing the required fields.');
    }
    return get_object_vars($body);
}

function required_string(array $body, string $key, string $label, bool $trim = true): string
{
    if (!isset($body[$key]) || !is_string($body[$key])) {
        fail($label . ' is required and must be text.');
    }
    $value = $trim ? trim($body[$key]) : $body[$key];
    if ($value === '') {
        fail($label . ' is required.');
    }
    return $value;
}

function read_name(array $body, string $key, string $label): string
{
    $name = required_string($body, $key, $label);
    // Count Unicode characters, matching MySQL VARCHAR(50), without mbstring.
    if (preg_match_all('/./us', $name) > 50 || preg_match('/[\x00-\x1f\x7f]/', $name)) {
        fail($label . ' must be at most 50 characters with no control characters.');
    }
    return $name;
}

function read_email(array $body): string
{
    $email = strtolower(required_string($body, 'email', 'Email'));
    if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        fail('Enter a valid email address of at most 50 characters.');
    }
    return $email;
}

function read_password(array $body): string
{
    // Passwords are never trimmed. Reject lengths bcrypt would silently truncate.
    $password = required_string($body, 'password', 'Password', false);
    if (preg_match_all('/./us', $password) < 8) {
        fail('Password must be at least 8 characters.');
    }
    if (strlen($password) > 72) {
        fail('Password is too long. Please choose a shorter password.');
    }
    if (strpos($password, "\0") !== false) {
        fail('Password contains an unsupported character.');
    }
    return $password;
}
