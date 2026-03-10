<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);

$name    = trim($data['name']    ?? '');
$subject = trim($data['subject'] ?? '');
$body    = trim($data['body']    ?? '');

if (!$name || !$subject || !$body) {
    http_response_code(422);
    echo json_encode(['message' => 'Name, subject and body are required.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "INSERT INTO webmail_email_templates (user_id, name, subject, body) VALUES (?,?,?,?)"
);
$stmt->execute([$user['id'], $name, $subject, $body]);

echo json_encode([
    'success'  => true,
    'template' => [
        'id'      => (int) $db->lastInsertId(),
        'name'    => $name,
        'subject' => $subject,
        'body'    => $body,
    ],
]);
