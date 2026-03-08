<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$id   = (int)($_GET['id'] ?? 0);

if (!$id) {
    http_response_code(422);
    echo json_encode(['message' => 'Email ID is required.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare(
    "SELECT * FROM webmail_emails WHERE id=? AND user_id=? LIMIT 1"
);
$stmt->execute([$id, $user['id']]);
$email = $stmt->fetch();

if (!$email) {
    http_response_code(404);
    echo json_encode(['message' => 'Email not found.']);
    exit;
}

echo json_encode(['success' => true, 'email' => $email]);
