<?php
declare(strict_types=1);

function get_db(): mysqli
{
    $config = __DIR__ . '/db_config.php';
    if (!is_file($config)) {
        throw new RuntimeException('Database configuration is missing.');
    }
    require_once $config;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $db = new mysqli(
        DB_HOST,
        DB_USER,
        DB_PASS,
        DB_NAME,
        defined('DB_PORT') ? (int) DB_PORT : 3306
    );
    $db->set_charset('utf8mb4');
    return $db;
}
