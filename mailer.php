<?php
/**
 * Core email sending engine using native PHP mail()
 * Supports: HTML, plain text, CC, BCC, multiple attachments, priority headers
 */

require_once __DIR__ . '/config.php';

/**
 * Send email with full MIME support
 *
 * @param array $params {
 *   sender_name, sender_email, to, cc, bcc,
 *   subject, message, is_html, priority, attachments[]
 * }
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail(array $params): array {
    // Fix: use camelCase keys matching compose.php compact()
    $senderName  = clean($params['senderName'] ?? '');
    $senderEmail = filter_var($params['senderEmail'] ?? '', FILTER_VALIDATE_EMAIL);
    $to          = filter_var($params['to'] ?? '', FILTER_VALIDATE_EMAIL);
    $cc          = $params['cc'] ?? '';
    $bcc         = $params['bcc'] ?? '';
    $subject     = clean($params['subject'] ?? '');
    $message     = $params['message'] ?? '';
    $isHtml      = !empty($params['isHtml']);
    $priority    = $params['priority'] ?? 'normal';
    $attachments = $params['attachments'] ?? [];

    if (!$to || !$senderEmail) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }

    $boundary = '----=_Part_' . md5(uniqid('', true));

    $headers  = "From: {$senderName} <{$senderEmail}>\r\n";
    $headers .= "Reply-To: {$senderEmail}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "X-Mailer: MailFlow/1.0\r\n";
    $headers .= "Date: " . date('r') . "\r\n";

    switch ($priority) {
        case 'high':
            $headers .= "X-Priority: 1\r\nX-MSMail-Priority: High\r\nImportance: High\r\n";
            break;
        case 'low':
            $headers .= "X-Priority: 5\r\nX-MSMail-Priority: Low\r\nImportance: Low\r\n";
            break;
        default:
            $headers .= "X-Priority: 3\r\n";
    }

    if (!empty($cc)) {
        $validCC = array_filter(
            array_map('trim', explode(',', $cc)),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        );
        if (!empty($validCC)) $headers .= "Cc: " . implode(', ', $validCC) . "\r\n";
    }
    if (!empty($bcc)) {
        $validBCC = array_filter(
            array_map('trim', explode(',', $bcc)),
            fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        );
        if (!empty($validBCC)) $headers .= "Bcc: " . implode(', ', $validBCC) . "\r\n";
    }

    $hasAttachments = !empty($attachments);
    $contentType    = $isHtml ? 'text/html' : 'text/plain';

    if ($hasAttachments) {
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

        $body  = "--{$boundary}\r\n";
        $body .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $message . "\r\n";

        foreach ($attachments as $filePath) {
            if (!file_exists($filePath)) continue;
            $fileName = basename($filePath);
            $fileData = chunk_split(base64_encode(file_get_contents($filePath)));
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';

            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: {$mimeType}; name=\"{$fileName}\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
            $body .= $fileData . "\r\n";
        }
        $body .= "--{$boundary}--";
    } else {
        $headers .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $body = $message;
    }

    // DirectAdmin: -f flag must match From address
    // $additionalParams = '-f' . $senderEmail;
    // $sent = @mail($to, $subject, $body, $headers, $additionalParams);
    $sent = @mail($to, $subject, $body, $headers);

    return [
        'success' => $sent,
        'message' => $sent ? 'Email sent successfully.' : 'Failed to send email.'
    ];
}


/**
 * Save email record to DB
 */
function saveEmailRecord(array $data) {  // removed int|false for PHP 7.4 compat
    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO webmail_emails
            (user_id, sender_name, sender_email, receiver_email, cc, bcc, subject,
             message, is_html, priority, attachments, status, scheduled_at)
        VALUES
            (:user_id, :sender_name, :sender_email, :receiver_email, :cc, :bcc,
             :subject, :message, :is_html, :priority, :attachments, :status, :scheduled_at)
    ");
    $stmt->execute([
        ':user_id'        => $data['user_id'],
        ':sender_name'    => $data['senderName'],        // camelCase fix
        ':sender_email'   => $data['senderEmail'],       // camelCase fix
        ':receiver_email' => $data['to'],
        ':cc'             => $data['cc'] ?? null,
        ':bcc'            => $data['bcc'] ?? null,
        ':subject'        => $data['subject'],
        ':message'        => $data['message'],
        ':is_html'        => !empty($data['isHtml']) ? 1 : 0,  // camelCase fix
        ':priority'       => $data['priority'] ?? 'normal',
        ':attachments'    => !empty($data['saved_files']) ? json_encode($data['saved_files']) : null,
        ':status'         => $data['status'] ?? 'sent',
        ':scheduled_at'   => $data['scheduled_at'] ?? null,
    ]);
    return (int) $db->lastInsertId();
}

