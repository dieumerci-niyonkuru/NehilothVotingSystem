<?php
require_once "config/functions.php";
if (!isMemberLoggedIn()) redirect("login.php");

$member_id = $_SESSION['member_id'];

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

// Handle Password Change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (password_verify($current, $member['password'])) {
        if ($new === $confirm && strlen($new) >= 6) {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE members SET password = ? WHERE id = ?")->execute([$hashed, $member_id]);
            setFlash("success", "Password updated successfully!");
        } else {
            setFlash("danger", "New passwords do not match or are too short (min 6 characters).");
        }
    } else {
        setFlash("danger", "Current password is incorrect.");
    }
    redirect("profile.php");
}

// Get assigned weekly duties
$stmt2 = $pdo->prepare("SELECT s.*, sa.role_assigned FROM schedule_assignments sa JOIN weekly_schedules s ON sa.schedule_id = s.id WHERE sa.member_id = ? ORDER BY s.date_scheduled ASC");
$stmt2->execute([$member_id]);
$assigned_duties = $stmt2->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Profile - NELILOTH WORSHIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.profile-img{width:110px;height:110px;object-fit:cover;border-radius:50%;border:4px solid #fff;box-shadow:0 5px 15px rgba(0,0,0,0.1);}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="container py-4">
    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $f['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Profile Overview Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 text-center">
                <img src="<?php echo htmlspecialchars($member['profile_pic']); ?>" class="profile-img mx-auto mb-3" alt="Profile">
                <h4 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($member['name']); ?></h4>
                <span class="badge bg-primary rounded-pill px-3 mb-3"><?php echo strtoupper(htmlspecialchars($member['role'])); ?></span>

                <div class="text-start border-top pt-3 text-secondary small">
                    <div class="mb-2"><i class="fas fa-phone text-success me-2"></i><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></div>
                    <div class="mb-2"><i class="fas fa-envelope text-primary me-2"></i><strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?></div>
                    <div class="mb-2"><i class="fas fa-users text-warning me-2"></i><strong>Group / Ministry:</strong> <?php echo htmlspecialchars($member['group_dept']); ?></div>
                    <div class="mb-2"><i class="fas fa-shield-alt text-info me-2"></i><strong>Account Status:</strong> <span class="badge bg-success"><?php echo strtoupper($member['status']); ?></span></div>
                </div>

                <a href="/logout.php" class="btn btn-outline-danger w-100 rounded-pill mt-3 fw-bold"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
            </div>
        </div>

        <!-- Assigned Duties & Password Management -->
        <div class="col-lg-8">
            <!-- Assigned Weekly Worship Duties -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-tasks text-success me-2"></i>My Assigned Worship Duties</h5>
                <?php if (empty($assigned_duties)): ?>
                    <p class="text-muted small mb-0">No specific weekly duties assigned to your profile this week.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($assigned_duties as $ad): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark small"><?php echo htmlspecialchars($ad['activity_title']); ?></strong>
                                    <span class="badge bg-info text-dark rounded-pill"><?php echo htmlspecialchars($ad['role_assigned']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($ad['day_of_week']); ?> &bull; <?php echo date('H:i', strtotime($ad['start_time'])); ?> at <?php echo htmlspecialchars($ad['location']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Change Password Card -->
            <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fas fa-key text-warning me-2"></i>Change Password</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="change_password">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold"><i class="fas fa-save me-1"></i> Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
