<?php require_once "../config/functions.php";
if (!isMemberLoggedIn()) redirect("../login.php");
$members = $pdo->query("SELECT name, email, phone, created_at FROM members WHERE status='approved' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Members</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></head>
<body><nav class="navbar navbar-dark bg-success"><div class="container"><a class="navbar-brand" href="dashboard.php">Members</a><a href="dashboard.php" class="btn btn-light">Back</a></div></nav>
<div class="container mt-3"><div class="card"><div class="card-header bg-primary text-white">Registered Voting Members (<?php echo count($members);?>)</div><div class="card-body"><table class="table table-striped"><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th></tr></thead><tbody><?php foreach($members as $m):?><tr><td><?php echo htmlspecialchars($m["name"]);?></td><td><?php echo htmlspecialchars($m["email"]);?></td><td><?php echo htmlspecialchars($m["phone"]);?></td><td><?php echo date("M d, Y",strtotime($m["created_at"]));?></td></tr><?php endforeach;?></tbody></table></div></div></div><?php include "../footer.php"; ?></body></html>