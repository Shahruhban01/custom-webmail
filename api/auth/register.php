<?php
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$data  = json_decode(file_get_contents('php://input'), true);
$name  = trim($data['name'] ?? '');
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
$pass  = $data['password'] ?? '';

if (!$name || !$email || !$pass) {
    http_response_code(422);
    echo json_encode(['message' => 'All fields are required.']);
    exit;
}
if (strlen($pass) < 8) {
    http_response_code(422);
    echo json_encode(['message' => 'Password must be at least 8 characters.']);
    exit;
}

$db = getDB();

// Check duplicate
$check = $db->prepare("SELECT id FROM webmail_users WHERE email=? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
    http_response_code(409);
    echo json_encode(['message' => 'An account with this email already exists.']);
    exit;
}

$hash  = password_hash($pass, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));
$exp   = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $db->prepare(
    "INSERT INTO webmail_users (name, email, password, api_token, token_expires)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->execute([$name, $email, $hash, $token, $exp]);
$userId = (int) $db->lastInsertId();

echo json_encode([
    'success' => true,
    'token'   => $token,
    'user'    => [
        'id'        => $userId,
        'name'      => $name,
        'email'     => $email,
        'signature' => '',
    ],
]);
