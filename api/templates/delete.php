<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);
$id   = (int)($data['id'] ?? 0);

if (!$id) {
    http_response_code(422);
    echo json_encode(['message' => 'Template ID required.']);
    exit;
}

$db    = getDB();
$check = $db->prepare("SELECT id FROM webmail_email_templates WHERE id=? AND user_id=?");
$check->execute([$id, $user['id']]);
if (!$check->fetch()) {
    http_response_code(403);
    echo json_encode(['message' => 'Not authorized to delete this template.']);
    exit;
}

$db->prepare("DELETE FROM webmail_email_templates WHERE id=?")->execute([$id]);
echo json_encode(['success' => true]);
