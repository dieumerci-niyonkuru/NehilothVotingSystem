<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$day = isset($_GET['day']) ? sanitize($_GET['day']) : null;
$member_id = isMemberLoggedIn() ? (int)$_SESSION["member_id"] : 0;

$sql = "SELECT s.*, GROUP_CONCAT(CONCAT(m.name, ' (', sa.role_assigned, ')') SEPARATOR ', ') as assigned_members_list
        FROM weekly_schedules s
        LEFT JOIN schedule_assignments sa ON s.id = sa.schedule_id
        LEFT JOIN members m ON sa.member_id = m.id
        WHERE s.date_scheduled >= CURDATE()";

$params = [];
if ($day) {
    $sql .= " AND s.day_of_week = ?";
    $params[] = $day;
}

$sql .= " GROUP BY s.id ORDER BY s.date_scheduled ASC, s.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();
$setlistStmt = $pdo->query("SELECT rs.schedule_id, rs.sequence_no, sr.song_title, sr.artist, sr.media_url FROM rehearsal_songs rs JOIN song_requests sr ON sr.id = rs.song_id WHERE sr.status IN ('approved','scheduled') ORDER BY rs.schedule_id, rs.sequence_no");
$setlists = [];
foreach ($setlistStmt->fetchAll() as $setlistSong) {
    $setlists[(int)$setlistSong["schedule_id"]][] = $setlistSong;
}
foreach ($schedules as &$schedule) {
    $schedule["song_setlist"] = $setlists[(int)$schedule["id"]] ?? [];
}
unset($schedule);

// If member_id provided, get member's personal assigned activities
$my_assignments = [];
if ($member_id > 0) {
    $stmt2 = $pdo->prepare("SELECT s.*, sa.role_assigned, sa.status as assignment_status FROM schedule_assignments sa JOIN weekly_schedules s ON sa.schedule_id = s.id WHERE sa.member_id = ? AND s.date_scheduled >= CURDATE() ORDER BY s.date_scheduled ASC");
    $stmt2->execute([$member_id]);
    $my_assignments = $stmt2->fetchAll();
}

echo json_encode([
    "status" => "success",
    "schedules" => $schedules,
    "my_assigned_duties" => $my_assignments
]);
?>