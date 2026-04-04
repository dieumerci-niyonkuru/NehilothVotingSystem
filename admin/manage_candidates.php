<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");
$positions = $pdo->query("SELECT id,title FROM positions")->fetchAll();
if ($_SERVER["REQUEST_METHOD"]=="POST" && isset($_POST["add"])) {
    $name = sanitize($_POST["name"]);
    $bio = sanitize($_POST["bio"]);
    $pos_id = (int)$_POST["position_id"];
    $pdo->prepare("INSERT INTO candidates (name, bio, position_id) VALUES (?,?,?)")->execute([$name, $bio, $pos_id]);
    setFlash("success", "Candidate added");
    redirect("manage_candidates.php");
}
if (isset($_GET["del"])) {
    $pdo->prepare("DELETE FROM candidates WHERE id=?")->execute([$_GET["del"]]);
    redirect("manage_candidates.php");
}
$candidates = $pdo->query("SELECT c.*, p.title as pos_title FROM candidates c JOIN positions p ON c.position_id=p.id ORDER BY c.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Manage Candidates</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"></head>
<body><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php">Admin</a><a href="dashboard.php" class="btn btn-light">Back</a></div></nav>
<div class="container mt-3"><div class="row"><div class="col-md-4"><div class="card"><div class="card-header bg-success text-white">Add Candidate</div><div class="card-body"><form method="POST"><div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control" required></div><div class="mb-3"><label>Bio</label><textarea name="bio" class="form-control"></textarea></div><div class="mb-3"><label>Position</label><select name="position_id" class="form-control" required><option value="">Select</option><?php foreach($positions as $p):?><option value="<?php echo $p["id"];?>"><?php echo htmlspecialchars($p["title"]);?></option><?php endforeach;?></select></div><button type="submit" name="add" class="btn btn-success w-100">Add</button></form></div></div></div>
<div class="col-md-8"><div class="card"><div class="card-header">Candidates List</div><div class="card-body"><table class="table table-bordered"><thead><tr><th>ID</th><th>Name</th><th>Position</th><th>Bio</th><th>Action</th></tr></thead><tbody><?php foreach($candidates as $c):?><tr><td><?php echo $c["id"];?></td><td><?php echo htmlspecialchars($c["name"]);?></td><td><?php echo htmlspecialchars($c["pos_title"]);?></td><td><?php echo htmlspecialchars(substr($c["bio"],0,50));?></td><td><a href="?del=<?php echo $c["id"];?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a></td></tr><?php endforeach;?></tbody></table></div></div></div></div></div></body></html>