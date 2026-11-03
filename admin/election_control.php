<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

redirect("manage_elections.php");
?>
