<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/functions.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');

// Read raw JSON input if POST
$json_input = json_decode(file_get_contents("php://input"), true);
if (is_array($json_input)) {
    $_POST = array_merge($_POST, $json_input);
    if (isset($json_input['action'])) $action = $json_input['action'];
}

switch ($action) {
    case 'login':
        $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($phone) || empty($password)) {
            echo json_encode(["status" => "error", "message" => "Phone number and password required."]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT * FROM members WHERE phone = ? OR email = ?");
        $stmt->execute([$phone, $phone]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'approved') {
                echo json_encode(["status" => "error", "message" => "Account pending administrator approval."]);
                exit();
            }

            $_SESSION['member_id'] = $user['id'];
            $_SESSION['member_name'] = $user['name'];
            $_SESSION['member_role'] = $user['role'];
            if ($user['role'] === 'admin') {
                $adminAccount = $pdo->prepare("SELECT id FROM admin WHERE member_id = ? LIMIT 1");
                $adminAccount->execute([(int)$user['id']]);
                $adminId = $adminAccount->fetchColumn();
                if (!$adminId) {
                    unset($_SESSION['member_id'], $_SESSION['member_name'], $_SESSION['member_role']);
                    http_response_code(403);
                    echo json_encode(["status" => "error", "message" => "Administrator access is not linked to this account."]);
                    exit();
                }
                $_SESSION['admin_id'] = (int)$adminId;
            }

            echo json_encode([
                "status" => "success",
                "message" => "Welcome back to Nehiloth Worship Team!",
                "csrf_token" => csrfToken(),
                "leadership_dashboard" => isLeadershipLoggedIn() && !isAdminLoggedIn() ? "/member/leadership_dashboard.php" : null,
                "user" => [
                    "id" => (int)$user['id'],
                    "name" => $user['name'],
                    "phone" => $user['phone'],
                    "email" => $user['email'],
                    "role" => $user['role'],
                    "profile_pic" => $user['profile_pic'],
                    "group_dept" => $user['group_dept']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Incorrect phone number or password."]);
        }
        break;

    case 'request_otp':
        $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
        if (empty($phone)) {
            echo json_encode(["status" => "error", "message" => "Phone number required."]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM members WHERE phone = ?");
        $stmt->execute([$phone]);
        if (!$stmt->fetch()) {
            echo json_encode(["status" => "error", "message" => "We could not find an account associated with this phone number."]);
            exit();
        }

        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $expires_at = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        $pdo->prepare("INSERT INTO otp_verifications (phone, otp_code, expires_at) VALUES (?, ?, ?)")->execute([$phone, $otp, $expires_at]);

        echo json_encode([
            "status" => "success",
            "message" => "OTP verification code sent via SMS.",
            "debug_otp" => $otp // In production, sent via SMS gateway
        ]);
        break;

    case 'verify_otp_reset':
        $phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
        $otp = isset($_POST['otp_code']) ? sanitize($_POST['otp_code']) : '';
        $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';

        if (empty($phone) || empty($otp) || empty($new_password)) {
            echo json_encode(["status" => "error", "message" => "Phone, OTP code, and new password required."]);
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM otp_verifications WHERE phone = ? AND otp_code = ? AND is_used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$phone, $otp]);
        $row = $stmt->fetch();

        if ($row) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE members SET password = ? WHERE phone = ?")->execute([$hashed, $phone]);
            $pdo->prepare("UPDATE otp_verifications SET is_used = 1 WHERE id = ?")->execute([$row['id']]);

            echo json_encode(["status" => "success", "message" => "Password successfully reset! You can now log in."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid or expired verification code."]);
        }
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid auth action."]);
        break;
}
?>