<?php
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$data  = json_decode(file_get_contents('php://input'), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$pass  = $data['password'] ?? '';

if (!$email || !$pass) {
    http_response_code(422);
    echo json_encode(['message' => 'Email and password are required.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM webmail_users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Invalid credentials.']);
    exit;
}

// Generate + store token
$token = bin2hex(random_bytes(32));
$exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
$db->prepare("UPDATE webmail_users SET api_token=?, token_expires=? WHERE id=?")
   ->execute([$token, $exp, $user['id']]);

echo json_encode([
    'success' => true,
    'token'   => $token,
    'user'    => [
        'id'        => $user['id'],
        'name'      => $user['name'],
        'email'     => $user['email'],
        'signature' => $user['signature'] ?? '',
    ],
]);
