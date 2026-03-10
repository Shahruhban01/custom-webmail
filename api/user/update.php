<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$sig  = $data['signature'] ?? null;

if (!$name) {
    http_response_code(422);
    echo json_encode(['message' => 'Name is required.']);
    exit;
}

$db = getDB();
$db->prepare("UPDATE webmail_users SET name=?, signature=? WHERE id=?")
   ->execute([$name, $sig, $user['id']]);

echo json_encode([
    'success' => true,
    'user'    => [
        'id'        => (int) $user['id'],
        'name'      => $name,
        'email'     => $user['email'],
        'signature' => $sig,
    ],
]);
