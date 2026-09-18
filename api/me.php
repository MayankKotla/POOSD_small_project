<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_method('GET');
respond(['success' => true, 'id' => require_user_id(), 'error' => '']);
