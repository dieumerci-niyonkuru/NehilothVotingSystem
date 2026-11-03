<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : (isMemberLoggedIn() ? $_SESSION['member_id'] : 0);

// Announcements
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 5")->fetchAll();

// Schedules
$schedules = $pdo->query("SELECT * FROM weekly_schedules WHERE date_scheduled >= CURDATE() ORDER BY date_scheduled ASC, start_time ASC LIMIT 5")->fetchAll();

// Active Election
$election = $pdo->query("SELECT * FROM elections WHERE status = 'open' LIMIT 1")->fetch();

// Leadership
$leadership = $pdo->query("SELECT name, role, photo_url FROM leadership WHERE status = 'active' ORDER BY display_order ASC LIMIT 4")->fetchAll();

// Unread Notifications
$unread_count = 0;
if ($member_id > 0) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM member_notifications WHERE member_id = ? AND is_read = 0");
    $stmt->execute([$member_id]);
    $unread_count = (int)$stmt->fetchColumn();
}

echo json_encode([
    "status" => "success",
    "data" => [
        "announcements" => $announcements,
        "upcoming_schedules" => $schedules,
        "active_election" => $election ? $election : null,
        "leadership_team" => $leadership,
        "unread_notifications" => $unread_count,
        "organization" => "NELILOTH WORSHIP - CEP Family RP Tumba College"
    ]
]);
?>