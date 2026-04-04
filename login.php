<?php require_once "config/functions.php";
if (isMemberLoggedIn()) redirect("member/dashboard.php");
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $code = sanitize($_POST["access_code"]);
    $stmt = $pdo->prepare("SELECT * FROM members WHERE email = ? AND access_code = ? AND status = 'approved'");
    $stmt->execute([$email, $code]);
    $member = $stmt->fetch();
    if ($member) {
        $_SESSION["member_id"] = $member["id"];
        $_SESSION["member_name"] = $member["name"];
        $_SESSION["member_email"] = $member["email"];
        redirect("member/dashboard.php");
    } else {
        setFlash("danger", "Invalid credentials or not approved");
    }
}
?>
<!DOCTYPE html>
<html><head><title>Member Login</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;}.card{border-radius:20px;}</style></head>
<body><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-4"><div class="card shadow"><div class="card-header bg-success text-white text-center"><h4><i class="fas fa-sign-in-alt"></i> Member Login</h4></div><div class="card-body"><?php $f=getFlash();if($f):?><div class="alert alert-<?php echo $f["type"];?>"><?php echo $f["message"];?></div><?php endif;?><form method="POST"><div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div><div class="mb-3"><label>Access Code</label><input type="text" name="access_code" class="form-control" placeholder="XXXX-XXXX" required></div><button type="submit" class="btn btn-success w-100">Login</button></form><div class="mt-3 text-center"><a href="register.php">Not registered? Register</a> | <a href="index.php">Home</a></div></div></div></div></div></div><?php include "footer.php"; ?></body></html>