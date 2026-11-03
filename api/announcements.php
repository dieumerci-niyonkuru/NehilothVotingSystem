<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC")->fetchAll();

echo json_encode([
    "status" => "success",
    "announcements" => $announcements
]);
?>