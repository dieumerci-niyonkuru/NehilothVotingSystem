<?php
require_once "../config/functions.php";
if (!isMemberLoggedIn()) redirect("../login.php");

$member_id = $_SESSION['member_id'];
$member_name = $_SESSION['member_name'];

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = sanitize($_POST['role_applied']);
    $exp = sanitize($_POST['experience']);
    $avail = sanitize($_POST['availability']);
    $notes = sanitize($_POST['notes']);

    if (!empty($role)) {
        if (submitWorshipApplication($member_id, $member['name'], $member['email'], $member['phone'], $role, $exp, $avail, $notes)) {
            setFlash("success", "Worship Team Application submitted! Check back for leadership audition updates.");
        } else {
            setFlash("danger", "Failed to submit application.");
        }
    }
    redirect("join_team.php");
}

// Get member applications
$stmt = $pdo->prepare("SELECT * FROM worship_applications WHERE member_id = ? ORDER BY applied_at DESC");
$stmt->execute([$member_id]);
$my_applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Worship Team - Member Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-user-plus text-success me-2"></i>Join Worship Team Application</h2>
            <p class="text-muted small">Apply to serve in vocals, instruments, sound, or media</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-edit me-2 text-primary"></i>Submit Application</h4>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Ministry Role / Talent</label>
                        <select name="role_applied" class="form-select" required>
                            <option value="Vocalist - Soprano">Vocalist - Soprano</option>
                            <option value="Vocalist - Alto">Vocalist - Alto</option>
                            <option value="Vocalist - Tenor">Vocalist - Tenor</option>
                            <option value="Vocalist - Bass">Vocalist - Bass</option>
                            <option value="Keyboardist / Pianist">Keyboardist / Pianist</option>
                            <option value="Acoustic / Electric Guitarist">Acoustic / Electric Guitarist</option>
                            <option value="Bassist">Bassist</option>
                            <option value="Drummer / Percussionist">Drummer / Percussionist</option>
                            <option value="Sound Engineer">Sound & Audio Engineer</option>
                            <option value="Media & Projection">Media / Visuals / Projection</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Experience Level</label>
                        <select name="experience" class="form-select" required>
                            <option value="Beginner / Enthusiast">Beginner / Enthusiast</option>
                            <option value="Intermediate (1-3 years)">Intermediate (1-3 years)</option>
                            <option value="Experienced (3+ years)">Experienced (3+ years)</option>
                            <option value="Ministry Leader / Professional">Ministry Leader / Professional</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Availability</label>
                        <select name="availability" class="form-select" required>
                            <option value="Sundays & Midweek Rehearsals">Sundays & Midweek Rehearsals</option>
                            <option value="Sundays Only">Sundays Only</option>
                            <option value="Midweek Rehearsals Only">Midweek Rehearsals Only</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Motivation / Additional Info</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tell us about your musical background or why you wish to serve..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success rounded-pill fw-bold w-100 py-2">
                        <i class="fas fa-paper-plane me-1"></i> Submit Application
                    </button>
                </form>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                <h4 class="fw-bold mb-3 text-dark"><i class="fas fa-tasks me-2 text-info"></i>Application Status</h4>
                <?php if (empty($my_applications)): ?>
                    <p class="text-muted">You haven't submitted a worship team application yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($my_applications as $app): ?>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($app['role_applied']); ?></h6>
                                    <?php
                                        $st = $app['status'];
                                        $badge = $st == 'approved' ? 'bg-success' : ($st == 'interview_scheduled' ? 'bg-info text-dark' : ($st == 'rejected' ? 'bg-danger' : 'bg-warning text-dark'));
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo strtoupper(str_replace('_', ' ', $st)); ?></span>
                                </div>
                                <small class="text-muted d-block">Experience: <?php echo htmlspecialchars($app['experience']); ?> &bull; Applied on: <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
