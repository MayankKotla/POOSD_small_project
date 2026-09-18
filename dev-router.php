<?php
// Local use: php -S 127.0.0.1:8000 -t public dev-router.php
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/') {
    header('Location: /login.html');
    return;
}
if (preg_match('#^/api/([A-Za-z][A-Za-z0-9]*\.php)$#D', $path, $match)) {
    $endpoint = __DIR__ . '/api/' . $match[1];
    if (is_file($endpoint)) {
        require $endpoint;
        return;
    }
}
$public = realpath(__DIR__ . '/public');
$file = strpos($path, "\0") === false ? realpath($public . $path) : false;
if ($file !== false && str_starts_with($file, $public . DIRECTORY_SEPARATOR) && is_file($file)) {
    return false;
}
http_response_code(404);
echo 'Not found';
