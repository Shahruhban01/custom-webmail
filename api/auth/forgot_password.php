<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../mailer.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$data  = json_decode(file_get_contents('php://input'), true);
$email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    http_response_code(422);
    echo json_encode(['message' => 'Valid email is required.']);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, name FROM webmail_users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Always return success to prevent email enumeration
if ($user) {
    $token   = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $db->prepare(
        "UPDATE webmail_users SET reset_token=?, reset_expires=? WHERE id=?"
    )->execute([$token, $expires, $user['id']]);

    $link = APP_URL . '/auth/reset_password.php?token=' . $token;
    sendMail([
        'senderName'  => APP_NAME,
        'senderEmail' => DEFAULT_SENDER_EMAIL,
        'to'          => $email,
        'subject'     => 'Reset your MailFlow password',
        'message'     => "Hi {$user['name']},\n\nClick the link below to reset your password (valid 1 hour):\n\n{$link}\n\nIf you didn't request this, ignore this email.\n\nMailFlow",
        'isHtml'      => false,
        'priority'    => 'normal',
    ]);
}

echo json_encode(['success' => true, 'message' => 'If that email exists, a reset link was sent.']);
