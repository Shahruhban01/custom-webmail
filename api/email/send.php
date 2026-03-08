<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../mailer.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();

// Parse multipart OR json
$senderName  = clean($_POST['sender_name']  ?? $user['name']);
$senderEmail = filter_var($_POST['sender_email'] ?? '', FILTER_VALIDATE_EMAIL);
$to          = filter_var($_POST['to'] ?? '', FILTER_VALIDATE_EMAIL);
$cc          = $_POST['cc']      ?? '';
$bcc         = $_POST['bcc']     ?? '';
$subject     = clean($_POST['subject'] ?? '');
$message     = $_POST['message'] ?? '';
$isHtml      = ($_POST['is_html'] ?? '0') === '1';
$priority    = $_POST['priority'] ?? 'normal';
$action      = $_POST['action']  ?? 'send';

if (!$senderEmail || !$to || !$subject || !$message) {
    http_response_code(422);
    echo json_encode(['message' => 'sender_email, to, subject, and message are required.']);
    exit;
}

// Handle file attachments
$savedFiles = [];
if (!empty($_FILES['attachments'])) {
    $files = $_FILES['attachments'];
    // Normalize single vs multiple
    if (!is_array($files['name'])) {
        foreach ($files as $k => $v) $files[$k] = [$v];
    }
    foreach ($files['tmp_name'] as $i => $tmp) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i] > MAX_FILE_SIZE) continue;
        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) continue;
        $safe = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($files['name'][$i]));
        $dest = UPLOAD_DIR . $safe;
        if (move_uploaded_file($tmp, $dest)) $savedFiles[] = $dest;
    }
}

$params = [
    'senderName'  => $senderName,
    'senderEmail' => $senderEmail,
    'to'          => $to,
    'cc'          => $cc,
    'bcc'         => $bcc,
    'subject'     => $subject,
    'message'     => $message,
    'isHtml'      => $isHtml,
    'priority'    => $priority,
    'attachments' => $savedFiles,
];

$status = 'draft';
if ($action === 'send') {
    $result = sendMail($params);
    $status = $result['success'] ? 'sent' : 'failed';
}

$record = array_merge($params, [
    'user_id'     => $user['id'],
    'saved_files' => $savedFiles,
    'status'      => $status,
    'scheduled_at'=> null,
]);
$emailId = saveEmailRecord($record);

echo json_encode([
    'success'  => $status === 'sent' || $status === 'draft',
    'status'   => $status,
    'email_id' => $emailId,
    'message'  => $status === 'sent' ? 'Email sent successfully.' : 'Draft saved.',
]);
