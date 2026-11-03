<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle Upload Media
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_gallery') {
    $title = sanitize($_POST['title']);
    $cat = sanitize($_POST['category']);
    $url = sanitize($_POST['media_url']);
    $desc = sanitize($_POST['description']);

    if (!empty($title) && !empty($url)) {
        $stmt = $pdo->prepare("INSERT INTO gallery (title, category, media_url, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $cat, $url, $desc]);
        setFlash("success", "Media added to worship gallery!");
    }
    redirect("manage_gallery.php");
}

// Delete Media
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM gallery WHERE id = ?")->execute([$id]);
    setFlash("danger", "Media item removed.");
    redirect("manage_gallery.php");
}

$gallery = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-images text-danger me-2"></i>Worship Gallery Manager</h2>
            <p class="text-muted small">Upload worship photos and video links to the public gallery</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-danger rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addGalModal">
                <i class="fas fa-upload me-1"></i> Upload Media
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
                        <th>Media</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Date Uploaded</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gallery as $g): ?>
                        <tr>
                            <td><img src="<?php echo htmlspecialchars($g['media_url']); ?>" style="width:60px;height:45px;object-fit:cover;border-radius:6px;"></td>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($g['title']); ?></strong></td>
                            <td><span class="badge bg-primary"><?php echo strtoupper($g['category']); ?></span></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars($g['description']); ?></small></td>
                            <td><small class="text-muted"><?php echo date('M d, Y', strtotime($g['uploaded_at'])); ?></small></td>
                            <td>
                                <a href="manage_gallery.php?delete=<?php echo $g['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this media?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Upload Media -->
<div class="modal fade" id="addGalModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Upload Worship Media</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_gallery">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Media Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="e.g. Sunday Concert Highlights">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category</label>
                        <select name="category" class="form-select">
                            <option value="photos">Photos</option>
                            <option value="videos">Videos</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Image / Media URL</label>
                        <input type="url" name="media_url" class="form-control" required placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger rounded-pill fw-bold">Save Media</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
