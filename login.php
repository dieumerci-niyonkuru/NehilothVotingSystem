<?php
require_once "config/functions.php";

if (isMemberLoggedIn()) redirect("member/dashboard.php");
if (isAdminLoggedIn()) redirect("admin/dashboard.php");

$redirect_target = isset($_GET['redirect']) ? sanitize($_GET['redirect']) : 'member/dashboard.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'member_login') {
    $login_input = sanitize($_POST["login_input"]);
    $password = $_POST["password"];

    if (!empty($login_input) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM members WHERE phone = ? OR email = ? OR member_id_no = ? OR access_code = ?");
        $stmt->execute([$login_input, $login_input, $login_input, $login_input]);
        $member = $stmt->fetch();

        if ($member) {
            // Check password or legacy access_code match
            $pw_ok = password_verify($password, $member["password"]) || ($member["access_code"] && $password === $member["access_code"]);

            if ($pw_ok) {
                if ($member["status"] !== "approved") {
                    setFlash("danger", "Your account is currently pending administrator approval.");
                } else {
                    $_SESSION["member_id"] = $member["id"];
                    $_SESSION["member_name"] = $member["name"];
                    $_SESSION["member_phone"] = $member["phone"];
                    $_SESSION["member_email"] = $member["email"];

                    setFlash("success", "Welcome back, " . $member["name"] . "!");
                    redirect($redirect_target);
                }
            } else {
                setFlash("danger", "Incorrect phone number/email or password.");
            }
        } else {
            setFlash("danger", "We could not find an account associated with this login credential.");
        }
    } else {
        setFlash("warning", "Please fill in all login fields.");
    }
    redirect("login.php?redirect=" . urlencode($redirect_target));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login - NELILOTH WORSHIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); min-height: 100vh; padding-bottom: 70px; }
        .login-card { border-radius: 20px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .admin-card { border-radius: 20px; border: 2px dashed #ffc107; background: rgba(255,193,7,0.1); }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <?php $f = getFlash(); if ($f): ?>
                <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo $f['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Member Login Card -->
            <div class="card login-card bg-white p-4 p-md-5 mb-4">
                <div class="text-center mb-4">
                    <i class="fas fa-church fa-3x text-primary mb-2"></i>
                    <h3 class="fw-bold text-dark mb-1">Member Login</h3>
                    <p class="text-muted small">NELILOTH WORSHIP &bull; CEP Family RP Tumba</p>
                </div>

                <form method="POST">
                    <input type="hidden" name="action" value="member_login">

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number or Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-user text-muted"></i></span>
                            <input type="text" name="login_input" class="form-control" placeholder="+250 7XX XXX XXX or email" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">Password</label>
                            <a href="#" class="small text-decoration-none" data-bs-toggle="modal" data-bs-target="#otpModal">Forgot Password?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                            <input type="password" name="password" id="passwordInput" class="form-control" placeholder="Enter your password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn">
                                <i class="fas fa-eye" id="passwordEye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberCheck" checked>
                        <label class="form-check-label small text-secondary" for="rememberCheck">Remember my session</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fw-bold shadow-sm mb-3">
                        <i class="fas fa-sign-in-alt me-2"></i> Log In
                    </button>
                </form>

                <div class="text-center pt-3 border-top">
                    <p class="small text-muted mb-0">Not registered yet? <a href="register.php" class="fw-bold text-primary">Register Here</a></p>
                </div>
            </div>

            <!-- Prominent Admin Login Button Section -->
            <div class="card admin-card p-4 text-center shadow-sm">
                <h5 class="fw-bold text-white mb-2"><i class="fas fa-user-shield text-warning me-2"></i>Administrator Access</h5>
                <p class="small text-light opacity-75 mb-3">Are you an authorized NELILOTH committee administrator?</p>
                <a href="/admin/login.php" class="btn btn-warning btn-lg rounded-pill fw-bold w-100 text-dark shadow-sm">
                    <i class="fas fa-shield-alt me-2"></i> Admin Login Panel
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal: OTP Password Reset -->
<div class="modal fade" id="otpModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-key text-warning me-2"></i>Reset Password via OTP</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div id="otpStep1">
                    <p class="text-muted small">Enter your registered phone number to receive a 6-digit verification code.</p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone Number</label>
                        <input type="tel" id="otpPhone" class="form-control" placeholder="+250 7XX XXX XXX">
                    </div>
                    <button type="button" class="btn btn-primary w-100 rounded-pill fw-bold" onclick="requestOtp()">Send Verification Code</button>
                </div>

                <div id="otpStep2" style="display:none;">
                    <p class="text-success small fw-bold" id="otpMsg"></p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Enter 6-Digit OTP Code</label>
                        <input type="text" id="otpCode" class="form-control" placeholder="123456">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" id="otpNewPassword" class="form-control" minlength="6">
                    </div>
                    <button type="button" class="btn btn-success w-100 rounded-pill fw-bold" onclick="verifyOtpReset()">Reset Password</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle Password Visibility
    document.getElementById('togglePasswordBtn').addEventListener('click', function() {
        const input = document.getElementById('passwordInput');
        const eye = document.getElementById('passwordEye');
        if (input.type === 'password') {
            input.type = 'text';
            eye.classList.remove('fa-eye');
            eye.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            eye.classList.remove('fa-eye-slash');
            eye.classList.add('fa-eye');
        }
    });

    // Request OTP AJAX
    function requestOtp() {
        const phone = document.getElementById('otpPhone').value;
        if (!phone) { alert('Please enter phone number.'); return; }
        fetch('/api/auth.php?action=request_otp', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({phone: phone})
        }).then(res => res.json()).then(data => {
            if (data.status === 'success') {
                document.getElementById('otpStep1').style.display = 'none';
                document.getElementById('otpStep2').style.display = 'block';
                document.getElementById('otpMsg').innerText = data.message + (data.debug_otp ? ' (Code: ' + data.debug_otp + ')' : '');
            } else {
                alert(data.message);
            }
        });
    }

    // Verify OTP & Reset Password AJAX
    function verifyOtpReset() {
        const phone = document.getElementById('otpPhone').value;
        const code = document.getElementById('otpCode').value;
        const newPw = document.getElementById('otpNewPassword').value;

        fetch('/api/auth.php?action=verify_otp_reset', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({phone: phone, otp_code: code, new_password: newPw})
        }).then(res => res.json()).then(data => {
            alert(data.message);
            if (data.status === 'success') {
                location.reload();
            }
        });
    }
</script>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
