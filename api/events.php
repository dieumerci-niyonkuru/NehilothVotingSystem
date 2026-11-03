<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC")->fetchAll();

echo json_encode(["status" => "success", "events" => $events]);
?>