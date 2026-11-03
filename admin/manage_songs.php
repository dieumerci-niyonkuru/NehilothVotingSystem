<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle status updates
if (isset($_GET['action']) && isset($_GET['id'])) {
    $song_id = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act == 'approve') {
        updateSongStatus($song_id, 'approved');
        setFlash("success", "Song approved!");
    } elseif ($act == 'schedule') {
        updateSongStatus($song_id, 'scheduled');
        setFlash("info", "Song scheduled for service!");
    } elseif ($act == 'reject') {
        updateSongStatus($song_id, 'rejected');
        setFlash("warning", "Song request rejected.");
    } elseif ($act == 'delete') {
        deleteSongRequest($song_id);
        setFlash("danger", "Song request deleted.");
    }
    redirect("manage_songs.php");
}

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : null;
$songs = getSongRequests($status_filter);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Song Requests - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-music text-warning me-2"></i>Manage Song Requests</h2>
            <p class="text-muted small">Review, approve, schedule, or manage member song requests</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Filter Pills -->
    <div class="d-flex gap-2 mb-4">
        <a href="manage_songs.php" class="btn btn-outline-dark rounded-pill px-3 <?php echo empty($status_filter)?'active':''; ?>">All Songs</a>
        <a href="manage_songs.php?status=pending" class="btn btn-outline-warning rounded-pill px-3 <?php echo $status_filter=='pending'?'active':''; ?>">Pending</a>
        <a href="manage_songs.php?status=approved" class="btn btn-outline-success rounded-pill px-3 <?php echo $status_filter=='approved'?'active':''; ?>">Approved</a>
        <a href="manage_songs.php?status=scheduled" class="btn btn-outline-info rounded-pill px-3 <?php echo $status_filter=='scheduled'?'active':''; ?>">Scheduled</a>
        <a href="manage_songs.php?status=rejected" class="btn btn-outline-danger rounded-pill px-3 <?php echo $status_filter=='rejected'?'active':''; ?>">Rejected</a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Song Title</th>
                        <th>Artist</th>
                        <th>Category / Key</th>
                        <th>Requester</th>
                        <th>Upvotes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($songs)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No song requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($songs as $s): ?>
                            <tr>
                                <td>
                                    <strong class="text-dark"><?php echo htmlspecialchars($s['song_title']); ?></strong>
                                    <?php if (!empty($s['notes'])): ?><br><small class="text-muted"><em>"<?php echo htmlspecialchars($s['notes']); ?>"</em></small><?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($s['artist']); ?></td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($s['genre_category']); ?></span>
                                    <span class="badge bg-light text-dark ms-1">Key: <?php echo htmlspecialchars($s['key_note']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($s['requester_name']); ?></td>
                                <td><span class="fw-bold text-primary"><i class="fas fa-thumbs-up me-1"></i><?php echo $s['votes_count']; ?></span></td>
                                <td>
                                    <?php
                                        $st = $s['status'];
                                        $badge = $st=='approved'?'bg-success':($st=='scheduled'?'bg-info text-dark':($st=='rejected'?'bg-danger':'bg-warning text-dark'));
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo strtoupper($st); ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="manage_songs.php?action=approve&id=<?php echo $s['id']; ?>" class="btn btn-success" title="Approve"><i class="fas fa-check"></i></a>
                                        <a href="manage_songs.php?action=schedule&id=<?php echo $s['id']; ?>" class="btn btn-info text-dark" title="Schedule for Service"><i class="fas fa-calendar-check"></i></a>
                                        <a href="manage_songs.php?action=reject&id=<?php echo $s['id']; ?>" class="btn btn-warning" title="Reject"><i class="fas fa-times"></i></a>
                                        <a href="manage_songs.php?action=delete&id=<?php echo $s['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this song request?');" title="Delete"><i class="fas fa-trash"></i></a>
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
