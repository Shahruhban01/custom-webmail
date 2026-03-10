<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user   = authenticate();
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$db  = getDB();
$sql = "SELECT * FROM webmail_emails WHERE user_id = :uid";
$bind = [':uid' => $user['id']];

if ($filter !== 'all') {
    $sql .= " AND status = :status";
    $bind[':status'] = $filter;
}
if ($search !== '') {
    $sql .= " AND (subject LIKE :q OR receiver_email LIKE :q OR sender_name LIKE :q)";
    $bind[':q'] = '%' . $search . '%';
}

$sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($bind as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$emails = $stmt->fetchAll();

// Cast numeric fields for Flutter
$emails = array_map(function($e) {
    $e['id']      = (int) $e['id'];
    $e['user_id'] = (int) $e['user_id'];
    $e['is_html'] = (int) $e['is_html'];
    return $e;
}, $emails);

echo json_encode(['success' => true, 'emails' => $emails, 'page' => $page]);

// echo json_encode([
//     'success' => true,
//     'emails'  => $stmt->fetchAll(),
//     'page'    => $page,
// ]);
