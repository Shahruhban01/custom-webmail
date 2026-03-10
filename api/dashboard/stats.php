<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../middleware.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$user = authenticate();
$db   = getDB();
$uid  = $user['id'];

// ── Totals ──
$total = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails WHERE user_id=?"
);
$total->execute([$uid]);
$totalCount = (int)$total->fetchColumn();

$sent = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='sent'"
);
$sent->execute([$uid]);
$sentCount = (int)$sent->fetchColumn();

$draft = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='draft'"
);
$draft->execute([$uid]);
$draftCount = (int)$draft->fetchColumn();

$failed = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails WHERE user_id=? AND status='failed'"
);
$failed->execute([$uid]);
$failedCount = (int)$failed->fetchColumn();

// ── Today ──
$today = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails
     WHERE user_id=? AND DATE(created_at)=CURDATE() AND status='sent'"
);
$today->execute([$uid]);
$todayCount = (int)$today->fetchColumn();

// ── This week ──
$week = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails
     WHERE user_id=? AND YEARWEEK(created_at)=YEARWEEK(NOW()) AND status='sent'"
);
$week->execute([$uid]);
$weekCount = (int)$week->fetchColumn();

// ── This month ──
$month = $db->prepare(
    "SELECT COUNT(*) FROM webmail_emails
     WHERE user_id=? AND MONTH(created_at)=MONTH(NOW())
     AND YEAR(created_at)=YEAR(NOW()) AND status='sent'"
);
$month->execute([$uid]);
$monthCount = (int)$month->fetchColumn();

// ── Last 7 days chart data ──
$chart = $db->prepare(
    "SELECT DATE(created_at) as date, COUNT(*) as count
     FROM webmail_emails
     WHERE user_id=? AND status='sent'
       AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
     GROUP BY DATE(created_at)
     ORDER BY date ASC"
);
$chart->execute([$uid]);
$chartRaw = $chart->fetchAll();

// Fill missing days with 0
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $found = array_filter($chartRaw, fn($r) => $r['date'] === $d);
    $found = array_values($found);
    $chartData[] = [
        'date'  => $d,
        'label' => date('D', strtotime($d)),
        'count' => $found ? (int)$found[0]['count'] : 0,
    ];
}

// ── Templates count ──
$tpl = $db->prepare(
    "SELECT COUNT(*) FROM webmail_email_templates WHERE user_id=?"
);
$tpl->execute([$uid]);
$tplCount = (int)$tpl->fetchColumn();

// ── Recent 3 emails ──
$recent = $db->prepare(
    "SELECT id, subject, receiver_email, status, created_at
     FROM webmail_emails WHERE user_id=?
     ORDER BY created_at DESC LIMIT 3"
);
$recent->execute([$uid]);
$recentEmails = array_map(function($e) {
    $e['id'] = (int)$e['id'];
    return $e;
}, $recent->fetchAll());

// ── Success rate ──
$rate = $totalCount > 0
    ? round(($sentCount / $totalCount) * 100, 1)
    : 0;

echo json_encode([
    'success' => true,
    'stats'   => [
        'total'       => $totalCount,
        'sent'        => $sentCount,
        'draft'       => $draftCount,
        'failed'      => $failedCount,
        'today'       => $todayCount,
        'this_week'   => $weekCount,
        'this_month'  => $monthCount,
        'success_rate'=> $rate,
        'templates'   => $tplCount,
        'chart'       => $chartData,
        'recent'      => $recentEmails,
    ],
]);
