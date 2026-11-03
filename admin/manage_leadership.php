<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle Add Leader
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_leader') {
    $name = sanitize($_POST['name']);
    $role = sanitize($_POST['role']);
    $bio = sanitize($_POST['bio']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = sanitize($_POST['phone']);
    $photo = sanitize($_POST['photo_url']);
    $order = (int)$_POST['display_order'];

    if (!empty($name) && !empty($role)) {
        if (addLeadershipMember($name, $role, $bio, $email, $phone, $photo, $order)) {
            setFlash("success", "New leadership member added!");
        } else {
            setFlash("danger", "Failed to add leadership entry.");
        }
    }
    redirect("manage_leadership.php");
}

// Handle Delete Leader
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    deleteLeadershipMember($id);
    setFlash("danger", "Leadership member removed.");
    redirect("manage_leadership.php");
}

$leaders = getLeadershipTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Leadership - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-users-cog text-danger me-2"></i>Manage Leadership Directory</h2>
            <p class="text-muted small">Add, edit, or organize worship team leadership members</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-danger rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addLeaderModal">
                <i class="fas fa-plus me-1"></i> Add Leader
            </button>
        </div>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Order</th>
                        <th>Leader Name</th>
                        <th>Role / Title</th>
                        <th>Bio Summary</th>
                        <th>Contact</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaders)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No leadership entries found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($leaders as $l): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?php echo $l['display_order']; ?></span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?php echo htmlspecialchars($l['photo_url']); ?>" style="width:40px;height:40px;object-fit:cover;border-radius:50%;">
                                        <strong class="text-dark"><?php echo htmlspecialchars($l['name']); ?></strong>
                                    </div>
                                </td>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($l['role']); ?></span></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars(substr($l['bio'], 0, 60)) . '...'; ?></small></td>
                                <td>
                                    <small class="d-block"><i class="fas fa-envelope text-primary me-1"></i><?php echo htmlspecialchars($l['email']); ?></small>
                                    <small class="d-block"><i class="fas fa-phone text-success me-1"></i><?php echo htmlspecialchars($l['phone']); ?></small>
                                </td>
                                <td>
                                    <a href="manage_leadership.php?delete=<?php echo $l['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Remove this leadership member?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Leader Modal -->
<div class="modal fade" id="addLeaderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus text-warning me-2"></i>Add Leadership Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_leader">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Pastor John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role / Title <span class="text-danger">*</span></label>
                        <input type="text" name="role" class="form-control" required placeholder="e.g. Worship Director">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Short Bio</label>
                        <textarea name="bio" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8 mb-3">
                            <label class="form-label fw-semibold">Photo URL</label>
                            <input type="url" name="photo_url" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-4 mb-3">
                            <label class="form-label fw-semibold">Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill fw-bold">Save Leader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
