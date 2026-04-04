<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");
if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["add"])) {
    $title = sanitize($_POST["title"]);
    $desc = sanitize($_POST["description"]);
    $pdo->prepare("INSERT INTO positions (title, description) VALUES (?,?)")->execute([$title, $desc]);
    setFlash("success", "Position added");
    redirect("manage_positions.php");
}
if (isset($_GET["del"])) {
    $pdo->prepare("DELETE FROM positions WHERE id=?")->execute([$_GET["del"]]);
    redirect("manage_positions.php");
}
$positions = $pdo->query("SELECT * FROM positions ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Manage Positions</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></head>
<body><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php">Admin</a><a href="dashboard.php" class="btn btn-light">Back</a></div></nav>
<div class="container mt-3"><div class="row"><div class="col-md-4"><div class="card"><div class="card-header bg-primary text-white">Add Position</div><div class="card-body"><form method="POST"><div class="mb-3"><label>Title</label><input type="text" name="title" class="form-control" required></div><div class="mb-3"><label>Description</label><textarea name="description" class="form-control"></textarea></div><button type="submit" name="add" class="btn btn-primary w-100">Add</button></form></div></div></div>
<div class="col-md-8"><div class="card"><div class="card-header">Existing Positions</div><div class="card-body"><table class="table"><thead><tr><th>ID</th><th>Title</th><th>Description</th><th>Action</th></tr></thead><tbody><?php foreach($positions as $p):?><tr><td><?php echo $p["id"];?></td><td><?php echo htmlspecialchars($p["title"]);?></td><td><?php echo htmlspecialchars($p["description"]);?></td><td><a href="?del=<?php echo $p["id"];?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endforeach;?></tbody></table></div></div></div></div></div></body></html>