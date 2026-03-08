<?php
/**
 * API Authentication Middleware
 * Include this in every protected API endpoint
 */
function authenticate(): array {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    if (!str_starts_with($auth, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['message' => 'Unauthorized. Token missing.']);
        exit;
    }

    $token = trim(substr($auth, 7));
    $db    = getDB();
    $stmt  = $db->prepare(
        "SELECT * FROM webmail_users 
         WHERE api_token=? AND token_expires > NOW() LIMIT 1"
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(401);
        echo json_encode(['message' => 'Token invalid or expired. Please login again.']);
        exit;
    }

    return $user;
}
