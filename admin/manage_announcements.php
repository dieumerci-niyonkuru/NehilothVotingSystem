<?php
require_once "../config/functions.php";
if (!canManageArea("announcements")) redirect(isMemberLoggedIn() ? "../member/dashboard.php" : "login.php");

// Handle Publish Announcement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_announcement') {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_announcements.php");
    }
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $author = sanitize($_POST['author_name']);
    $img = sanitize($_POST['image_url']);
    $pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $important = isset($_POST['is_important']) ? 1 : 0;

    if (!empty($title) && !empty($content)) {
        $stmt = $pdo->prepare("INSERT INTO announcements (title, content, author_name, image_url, is_pinned, is_important) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $content, $author, $img, $pinned, $important]);
        notifyAllMembers("New Announcement: $title", "info");
        setFlash("success", "Announcement published and notification sent to members!");
    }
    redirect("manage_announcements.php");
}

// Delete Announcement
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_announcement") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_announcements.php");
    }
    $id = (int)($_POST["id"] ?? 0);
    $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
    setFlash("danger", "Announcement removed.");
    redirect("manage_announcements.php");
}

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-bullhorn text-warning me-2"></i>Manage Announcements</h2>
            <p class="text-muted small">Publish announcements, pin news, and trigger member notifications</p>
        </div>
        <div>
            <a href="<?php echo isMemberLoggedIn() ? '../member/leadership_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-warning text-dark rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addNewsModal">
                <i class="fas fa-plus me-1"></i> New Announcement
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
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th>Author</th>
                        <th>Pinned / Important</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($announcements as $a): ?>
                        <tr>
                            <td><strong class="text-dark"><?php echo htmlspecialchars($a['title']); ?></strong></td>
                            <td><small class="text-muted"><?php echo htmlspecialchars(substr($a['content'], 0, 80)) . '...'; ?></small></td>
                            <td><small class="text-primary fw-bold"><?php echo htmlspecialchars($a['author_name']); ?></small></td>
                            <td>
                                <?php if ($a['is_pinned']): ?><span class="badge bg-warning text-dark">PINNED</span><?php endif; ?>
                                <?php if ($a['is_important']): ?><span class="badge bg-danger ms-1">IMPORTANT</span><?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?php echo date('M d, H:i', strtotime($a['created_at'])); ?></small></td>
                            <td>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete announcement?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="delete_announcement">
                                    <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                                    <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: New Announcement -->
<div class="modal fade" id="addNewsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Publish Announcement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <input type="hidden" name="action" value="add_announcement">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="Announcement headline">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Content Body</label>
                        <textarea name="content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Author Name</label>
                            <input type="text" name="author_name" class="form-control" value="Nehiloth Leadership">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Image URL (Optional)</label>
                            <input type="url" name="image_url" class="form-control" placeholder="https://">
                        </div>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="is_pinned" id="pinCheck">
                        <label class="form-check-label fw-semibold" for="pinCheck">Pin to top of news feed</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_important" id="impCheck">
                        <label class="form-check-label fw-semibold" for="impCheck">Mark as Urgent / Important</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill fw-bold">Publish News</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
