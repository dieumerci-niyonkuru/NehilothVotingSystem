<?php
require_once "config/functions.php";
$email = $_GET["email"] ?? "";
$code = $_GET["code"] ?? "";
if ($email && $code) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND access_code = ? AND status = 'approved'");
    $stmt->execute([$email, $code]);
    $member = $stmt->fetch();
    if ($member) {
        $_SESSION["member_id"] = $member["id"];
        $_SESSION["member_name"] = $member["name"];
        $_SESSION["member_email"] = $member["email"];
        redirect("member/dashboard.php");
    } else {
        setFlash("danger", "Invalid QR code");
        redirect("login.php");
    }
} else {
    redirect("login.php");
}
?>