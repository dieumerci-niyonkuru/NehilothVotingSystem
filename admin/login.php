<?php require_once "../config/functions.php";
if (isAdminLoggedIn()) redirect("dashboard.php");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE email = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin["password"])) {
        $_SESSION["admin_id"] = $admin["id"];
        $_SESSION["admin_email"] = $admin["email"];
        logAction($admin["id"], "Admin logged in");
        redirect("dashboard.php");
    } else {
        setFlash("danger", "Invalid credentials");
    }
}
?>
<!DOCTYPE html>
<html><head><title>Admin Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><style>body{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);min-height:100vh;}.card{border-radius:20px;}</style></head>
<body><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-4"><div class="card shadow"><div class="card-header bg-dark text-white text-center"><h4><i class="fas fa-user-shield"></i> Admin Login</h4></div><div class="card-body"><?php $f=getFlash();if($f):?><div class="alert alert-<?php echo $f["type"];?>"><?php echo $f["message"];?></div><?php endif;?><form method="POST"><div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div><div class="mb-3"><label>Password</label><input type="password" name="password" class="form-control" required></div><button type="submit" class="btn btn-dark w-100">Login</button></form><div class="mt-3 text-center"><a href="../index.php">Back to Home</a></div></div></div></div></div></div></body></html>