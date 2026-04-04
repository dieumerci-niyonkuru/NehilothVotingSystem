<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new = ($_POST["action"] == "start") ? 1 : 0;
    $pdo->prepare("UPDATE settings SET election_active = ? WHERE id=1")->execute([$new]);
    logAction($_SESSION["admin_id"], "Election " . ($new ? "started" : "stopped"));
    setFlash("success", "Election " . ($new ? "started" : "stopped"));
    redirect("election_control.php");
}
$active = isElectionActive();
?>
<!DOCTYPE html>
<html><head><title>Election Control</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></head>
<body><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-6"><div class="card shadow"><div class="card-header bg-dark text-white text-center"><h4>Election Control</h4></div><div class="card-body text-center"><h3>Status: <span class="badge bg-<?php echo $active?"success":"danger";?>"><?php echo $active?"ACTIVE":"INACTIVE";?></span></h3><hr><?php if(!$active):?><form method="POST"><input type="hidden" name="action" value="start"><button class="btn btn-success btn-lg">START ELECTION</button></form><?php else:?><form method="POST"><input type="hidden" name="action" value="stop"><button class="btn btn-danger btn-lg">STOP ELECTION</button></form><?php endif;?><div class="mt-3"><a href="dashboard.php" class="btn btn-secondary">Back</a></div></div></div></div></div></div></body></html>