<?php
require_once "../config/functions.php";

if (!isMemberLoggedIn()) {
    redirect("../login.php");
}

redirect("../voting.php");
?>
