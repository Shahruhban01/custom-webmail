<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
echo json_encode([
    'success' => true,
    'user'    => [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'signature'  => $user['signature'] ?? '',
        'created_at' => $user['created_at'],
    ],
]);
