<?php
require_once "config/database.php";
$new_hash = password_hash("Admin@123", PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE email = 'admin@nehiloth.com'");
if ($stmt->execute([$new_hash])) {
    echo "<h2>Admin password updated successfully!</h2>";
    echo "<p>Email: <strong>admin@nehiloth.com</strong></p>";
    echo "<p>Password: <strong>Admin@123</strong></p>";
    echo "<a href='admin/login.php'>Click here to login</a>";
} else {
    echo "Update failed. Please import database.sql first.";
}
?>