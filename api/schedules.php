<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$day = isset($_GET['day']) ? sanitize($_GET['day']) : null;
$member_id = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;

$sql = "SELECT s.*, GROUP_CONCAT(CONCAT(m.name, ' (', sa.role_assigned, ')') SEPARATOR ', ') as assigned_members_list
        FROM weekly_schedules s
        LEFT JOIN schedule_assignments sa ON s.id = sa.schedule_id
        LEFT JOIN members m ON sa.member_id = m.id
        WHERE 1=1";

$params = [];
if ($day) {
    $sql .= " AND s.day_of_week = ?";
    $params[] = $day;
}

$sql .= " GROUP BY s.id ORDER BY s.date_scheduled ASC, s.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();

// If member_id provided, get member's personal assigned activities
$my_assignments = [];
if ($member_id > 0) {
    $stmt2 = $pdo->prepare("SELECT s.*, sa.role_assigned, sa.status as assignment_status FROM schedule_assignments sa JOIN weekly_schedules s ON sa.schedule_id = s.id WHERE sa.member_id = ? ORDER BY s.date_scheduled ASC");
    $stmt2->execute([$member_id]);
    $my_assignments = $stmt2->fetchAll();
}

echo json_encode([
    "status" => "success",
    "schedules" => $schedules,
    "my_assigned_duties" => $my_assignments
]);
?>