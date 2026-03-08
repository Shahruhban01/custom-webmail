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
    echo json_encode(['message' => 'Email ID is required.']);
    exit;
}

$db = getDB();

// Verify ownership
$check = $db->prepare("SELECT attachments FROM webmail_emails WHERE id=? AND user_id=?");
$check->execute([$id, $user['id']]);
$row = $check->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['message' => 'Email not found.']);
    exit;
}

// Delete attachment files
if ($row['attachments']) {
    $files = json_decode($row['attachments'], true) ?? [];
    foreach ($files as $f) {
        if (file_exists($f)) @unlink($f);
    }
}

$db->prepare("DELETE FROM webmail_emails WHERE id=? AND user_id=?")
   ->execute([$id, $user['id']]);

echo json_encode(['success' => true]);
