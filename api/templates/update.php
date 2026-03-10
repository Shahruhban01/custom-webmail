<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);

$id      = (int)($data['id']      ?? 0);
$name    = trim($data['name']    ?? '');
$subject = trim($data['subject'] ?? '');
$body    = trim($data['body']    ?? '');

if (!$id || !$name || !$subject || !$body) {
    http_response_code(422);
    echo json_encode(['message' => 'ID, name, subject and body are required.']);
    exit;
}

$db = getDB();
// Only allow editing own templates (not global user_id=1 unless IS user 1)
$check = $db->prepare("SELECT id FROM webmail_email_templates WHERE id=? AND user_id=?");
$check->execute([$id, $user['id']]);
if (!$check->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'Not authorized to edit this template.']);
    exit;
}

$db->prepare("UPDATE webmail_email_templates SET name=?, subject=?, body=? WHERE id=?")
   ->execute([$name, $subject, $body, $id]);

echo json_encode(['success' => true, 'message' => 'Template updated.']);
