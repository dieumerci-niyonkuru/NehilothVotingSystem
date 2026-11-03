<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle action status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act == 'schedule') {
        updateApplicationStatus($id, 'interview_scheduled');
        setFlash("info", "Audition / Interview scheduled for applicant!");
    } elseif ($act == 'approve') {
        updateApplicationStatus($id, 'approved');
        setFlash("success", "Applicant approved for Worship Team!");
    } elseif ($act == 'reject') {
        updateApplicationStatus($id, 'rejected');
        setFlash("warning", "Application declined.");
    }
    redirect("manage_applications.php");
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : null;
$applications = getWorshipApplications($status_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worship Team Applications - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-hands-helping text-success me-2"></i>Worship Team Applications</h2>
            <p class="text-muted small">Review candidate applications, schedule auditions, and approve team members</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Filter Pills -->
    <div class="d-flex gap-2 mb-4">
        <a href="manage_applications.php" class="btn btn-outline-dark rounded-pill px-3 <?php echo empty($status_filter)?'active':''; ?>">All Applications</a>
        <a href="manage_applications.php?status=pending" class="btn btn-outline-warning rounded-pill px-3 <?php echo $status_filter=='pending'?'active':''; ?>">Pending</a>
        <a href="manage_applications.php?status=interview_scheduled" class="btn btn-outline-info rounded-pill px-3 <?php echo $status_filter=='interview_scheduled'?'active':''; ?>">Audition Scheduled</a>
        <a href="manage_applications.php?status=approved" class="btn btn-outline-success rounded-pill px-3 <?php echo $status_filter=='approved'?'active':''; ?>">Approved</a>
        <a href="manage_applications.php?status=rejected" class="btn btn-outline-danger rounded-pill px-3 <?php echo $status_filter=='rejected'?'active':''; ?>">Rejected</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Role / Talent</th>
                        <th>Experience</th>
                        <th>Availability</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No applications found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($applications as $app): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark"><?php echo htmlspecialchars($app['applicant_name']); ?></strong>
                                    <?php if (!empty($app['notes'])): ?><br><small class="text-muted"><em>"<?php echo htmlspecialchars($app['notes']); ?>"</em></small><?php endif; ?>
                                </td>
                                <td>
                                    <small class="d-block"><i class="fas fa-envelope me-1 text-primary"></i><?php echo htmlspecialchars($app['email']); ?></small>
                                    <small class="d-block"><i class="fas fa-phone me-1 text-success"></i><?php echo htmlspecialchars($app['phone']); ?></small>
                                </td>
                                <td><span class="badge bg-primary rounded-pill px-3"><?php echo htmlspecialchars($app['role_applied']); ?></span></td>
                                <td><small class="text-secondary"><?php echo htmlspecialchars($app['experience']); ?></small></td>
                                <td><small class="text-secondary"><?php echo htmlspecialchars($app['availability']); ?></small></td>
                                <td>
                                    <?php
                                        $st = $app['status'];
                                        $badge = $st=='approved'?'bg-success':($st=='interview_scheduled'?'bg-info text-dark':($st=='rejected'?'bg-danger':'bg-warning text-dark'));
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo strtoupper(str_replace('_', ' ', $st)); ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="manage_applications.php?action=schedule&id=<?php echo $app['id']; ?>" class="btn btn-info text-dark" title="Schedule Audition"><i class="fas fa-calendar-alt"></i></a>
                                        <a href="manage_applications.php?action=approve&id=<?php echo $app['id']; ?>" class="btn btn-success" title="Approve Applicant"><i class="fas fa-user-check"></i></a>
                                        <a href="manage_applications.php?action=reject&id=<?php echo $app['id']; ?>" class="btn btn-danger" title="Decline"><i class="fas fa-user-times"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
