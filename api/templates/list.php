<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$db   = getDB();

$stmt = $db->prepare(
    "SELECT id, name, subject, body FROM webmail_email_templates
     WHERE user_id IN (1, ?) ORDER BY id"
);
$stmt->execute([$user['id']]);

echo json_encode(['success' => true, 'templates' => $stmt->fetchAll()]);
