<?php require_once "config/functions.php";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST["name"]);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phone = sanitize($_POST["phone"]);
    if (empty($name) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash("danger", "Valid name and email are required");
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO members (name, email, phone, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$name, $email, $phone]);
            setFlash("success", "Registration successful! Wait for admin approval.");
            redirect("login.php");
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) setFlash("danger", "Email already registered");
            else setFlash("danger", "Registration failed");
        }
    }
}
?>
<!DOCTYPE html>
<html><head><title>Register</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><style>body{background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;}.card{border-radius:20px;}</style></head>
<body><div class="container mt-5"><div class="row justify-content-center"><div class="col-md-5"><div class="card shadow"><div class="card-header bg-primary text-white text-center"><h4><i class="fas fa-user-plus"></i> Member Registration</h4></div><div class="card-body"><?php $f=getFlash();if($f):?><div class="alert alert-<?php echo $f["type"];?>"><?php echo $f["message"];?></div><?php endif;?><form method="POST"><div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" required></div><div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" required></div><div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control"></div><button type="submit" class="btn btn-primary w-100">Register</button></form><div class="mt-3 text-center"><a href="login.php">Already have access code? Login</a></div></div></div></div></div></div><?php include "footer.php"; ?></body></html>