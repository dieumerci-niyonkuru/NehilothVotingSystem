<?php
require_once "../config/functions.php";
if (!canManageArea("songs")) redirect(isMemberLoggedIn() ? "../member/dashboard.php" : "login.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_songs.php");
    }
    $song_id = filter_var($_POST["id"] ?? null, FILTER_VALIDATE_INT);
    $act = $_POST["action"] ?? "";
    if (!$song_id) {
        setFlash("danger", "Invalid song request.");
    } elseif ($act === "edit_song") {
        $title = trim($_POST["song_title"] ?? "");
        $artist = trim($_POST["artist"] ?? "");
        $category = $_POST["genre_category"] ?? "";
        $key = trim($_POST["key_note"] ?? "");
        $mediaUrl = trim($_POST["media_url"] ?? "");
        $lyricsLink = trim($_POST["lyrics_link"] ?? "");
        $notes = trim($_POST["notes"] ?? "");
        if ($title === "" || strlen($title) > 255 || strlen($artist) > 100 || strlen($key) > 20 ||
            strlen($notes) > 5000 || !in_array($category, ["Worship", "Praise", "Hymn", "Special"], true) ||
            ($mediaUrl !== "" && !isSafeSongMediaUrl($mediaUrl)) || ($lyricsLink !== "" && !isSafeMediaUrl($lyricsLink))) {
            setFlash("danger", "Check the song details and valid HTTP(S) links.");
        } else {
            $update = $pdo->prepare("UPDATE song_requests SET song_title = ?, artist = ?, genre_category = ?, key_note = ?, media_url = ?, lyrics_link = ?, notes = ? WHERE id = ?");
            $update->execute([$title, $artist, $category, $key, $mediaUrl ?: null, $lyricsLink ?: null, $notes, $song_id]);
            setFlash("success", "Song details updated.");
        }
    } elseif ($act === 'approve') {
        updateSongStatus($song_id, 'approved');
        setFlash("success", "Song approved!");
    } elseif ($act === 'schedule') {
        updateSongStatus($song_id, 'scheduled');
        setFlash("info", "Song scheduled for service!");
    } elseif ($act === 'reject') {
        updateSongStatus($song_id, 'rejected');
        setFlash("warning", "Song request rejected.");
    } elseif ($act === 'delete') {
        deleteSongRequest($song_id);
        setFlash("danger", "Song request deleted.");
    } else {
        setFlash("danger", "Unknown song action.");
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
        <a href="<?php echo isMemberLoggedIn() ? '../member/leadership_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
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
                        <th>Recording / link</th>
                        <th>Upvotes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($songs)): ?>
                        <tr>                        <td colspan="8" class="text-center py-4 text-muted">No song requests found.</td></tr>
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
                                <td>
                                    <?php if (!empty($s["media_url"])): ?>
                                        <?php $audioExtension = strtolower(pathinfo((string)parse_url($s["media_url"], PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
                                        <?php if (in_array($audioExtension, ["mp3", "m4a", "wav", "ogg", "oga", "webm"], true)): ?>
                                            <audio controls preload="none" style="max-width:180px"><source src="<?php echo htmlspecialchars($s["media_url"]); ?>"></audio>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($s["media_url"]); ?>" target="_blank" rel="noopener noreferrer">Open link</a>
                                        <?php endif; ?>
                                    <?php else: ?><span class="text-muted">None</span><?php endif; ?>
                                </td>
                                <td><span class="fw-bold text-primary"><i class="fas fa-thumbs-up me-1"></i><?php echo (int)$s['votes_count']; ?></span></td>
                                <td>
                                    <?php
                                        $st = $s['status'];
                                        $badge = $st=='approved'?'bg-success':($st=='scheduled'?'bg-info text-dark':($st=='rejected'?'bg-danger':'bg-warning text-dark'));
                                    ?>
                                    <span class="badge <?php echo $badge; ?> rounded-pill px-3"><?php echo strtoupper($st); ?></span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#edit-song-<?php echo (int)$s["id"]; ?>" aria-expanded="false" aria-label="Edit song details"><i class="fas fa-pen"></i></button>
                                        <?php foreach (["approve" => "success", "schedule" => "info", "reject" => "warning", "delete" => "danger"] as $action => $color): ?>
                                            <form method="POST" class="d-inline" <?php echo $action === "delete" ? "onsubmit=\"return confirm('Delete this song request?');\"" : ""; ?>>
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                                <input type="hidden" name="id" value="<?php echo (int)$s["id"]; ?>">
                                                <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>">
                                                <button type="submit" class="btn btn-<?php echo $color; ?> <?php echo $action === "schedule" ? "text-dark" : ""; ?>" title="<?php echo htmlspecialchars(ucfirst($action)); ?>">
                                                    <i class="fas <?php echo $action === "approve" ? "fa-check" : ($action === "schedule" ? "fa-calendar-check" : ($action === "reject" ? "fa-times" : "fa-trash")); ?>"></i>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                            <tr class="collapse" id="edit-song-<?php echo (int)$s["id"]; ?>">
                                <td colspan="8">
                                    <form method="POST" class="row g-2 bg-light p-3 rounded-3">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="edit_song">
                                        <input type="hidden" name="id" value="<?php echo (int)$s["id"]; ?>">
                                        <div class="col-md-3"><label class="form-label">Title</label><input name="song_title" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($s["song_title"]); ?>" required></div>
                                        <div class="col-md-2"><label class="form-label">Artist</label><input name="artist" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($s["artist"]); ?>"></div>
                                        <div class="col-md-2"><label class="form-label">Category</label><select name="genre_category" class="form-select"><?php foreach (["Worship", "Praise", "Hymn", "Special"] as $category): ?><option value="<?php echo $category; ?>" <?php echo $s["genre_category"] === $category ? "selected" : ""; ?>><?php echo $category; ?></option><?php endforeach; ?></select></div>
                                        <div class="col-md-1"><label class="form-label">Key</label><input name="key_note" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($s["key_note"]); ?>"></div>
                                        <div class="col-md-4"><label class="form-label">Public audio/song URL</label><input type="url" name="media_url" class="form-control" value="<?php echo htmlspecialchars($s["media_url"] ?? ""); ?>"></div>
                                        <div class="col-md-6"><label class="form-label">Lyrics/reference URL</label><input type="url" name="lyrics_link" class="form-control" value="<?php echo htmlspecialchars($s["lyrics_link"] ?? ""); ?>"></div>
                                        <div class="col-md-6"><label class="form-label">Notes</label><input name="notes" class="form-control" maxlength="5000" value="<?php echo htmlspecialchars($s["notes"] ?? ""); ?>"></div>
                                        <div class="col-12"><button class="btn btn-primary btn-sm">Save song details</button></div>
                                    </form>
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
