<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");

require_once __DIR__ . "/../config/functions.php";

$category = isset($_GET['category']) ? sanitize($_GET['category']) : null;
$sql = "SELECT * FROM gallery WHERE 1=1";
$params = [];

if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY uploaded_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$gallery = $stmt->fetchAll();

echo json_encode(["status" => "success", "gallery" => $gallery]);
?>