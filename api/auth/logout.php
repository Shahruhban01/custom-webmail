<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$db   = getDB();
$db->prepare("UPDATE webmail_users SET api_token=NULL, token_expires=NULL WHERE id=?")
   ->execute([$user['id']]);

echo json_encode(['success' => true]);
