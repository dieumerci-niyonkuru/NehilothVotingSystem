<?php
require_once "../config/functions.php";
if (!isMemberLoggedIn()) redirect("../login.php");

$member_id = $_SESSION['member_id'];
$member_name = $_SESSION['member_name'];

// Handle new song request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("song_requests.php");
    }

    $action = $_POST["action"] ?? "";
    if ($action === "add_song") {
        $title = trim($_POST["song_title"] ?? "");
        $artist = trim($_POST["artist"] ?? "");
        $cat = $_POST["genre_category"] ?? "Worship";
        $key = trim($_POST["key_note"] ?? "");
        $link = trim($_POST["lyrics_link"] ?? "");
        $mediaLink = trim($_POST["media_url"] ?? "");
        $notes = trim($_POST["notes"] ?? "");
        $uploadPath = null;
        try {
            if ($title === "" || strlen($title) > 255 || strlen($artist) > 100 || strlen($key) > 20 ||
                strlen($notes) > 5000 || !in_array($cat, ["Worship", "Praise", "Hymn", "Special"], true) ||
                ($link !== "" && !isSafeMediaUrl($link)) || ($mediaLink !== "" && !isSafeMediaUrl($mediaLink))) {
                throw new RuntimeException("Check the song title, category, and HTTP(S) links.");
            }
            if (!empty($_FILES["audio_file"]["name"])) {
                if ($mediaLink !== "") {
                    throw new RuntimeException("Choose an audio upload or an audio link, not both.");
                }
                $uploadPath = storeSongAudio($_FILES["audio_file"]);
            }
            if (addSongRequest($member_id, $member_name, $title, $artist, $cat, $key, $link, $notes, $uploadPath ?: ($mediaLink ?: null))) {
                setFlash("success", "Worship song submitted for review.");
            } else {
                if ($uploadPath) {
                    $storedFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $uploadPath);
                    if (is_file($storedFile) && !unlink($storedFile)) {
                        error_log("Unable to remove unused uploaded song file: " . $storedFile);
                    }
                }
                setFlash("danger", "The song request could not be saved.");
            }
        } catch (RuntimeException $e) {
            setFlash("danger", $e->getMessage());
        }
    } elseif ($action === "delete_own_song") {
        $songId = filter_var($_POST["song_id"] ?? null, FILTER_VALIDATE_INT);
        $deleted = $songId ? deleteOwnPendingSongRequest($songId, $member_id) : false;
        setFlash($deleted ? "success" : "warning", $deleted ? "Pending song request removed." : "Only your pending song requests can be removed.");
    }
    redirect("song_requests.php");
}

$my_songs = getMemberSongRequests($member_id);
$all_songs = getPublicSongRequests(null, 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Requests Portal - Nehiloth Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-music text-warning me-2"></i>My Song Requests & Upvoting</h2>
            <p class="text-muted small">Request praise & worship songs for upcoming choir services</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-warning rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#newSongModal">
                <i class="fas fa-plus me-1"></i> Request Song
            </button>
        </div>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-list me-2"></i>My Submitted Requests</h5>
                <?php if (empty($my_songs)): ?>
                    <p class="text-muted small">You haven't requested any songs yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($my_songs as $ms): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($ms['song_title']); ?></h6>
                                    <span class="badge bg-secondary"><?php echo strtoupper($ms['status']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($ms['artist']); ?> &bull; Upvotes: <?php echo $ms['votes_count']; ?></small>
                                <?php if (!empty($ms["media_url"])): ?>
                                    <a class="small d-block mt-1" href="<?php echo htmlspecialchars($ms["media_url"]); ?>" target="_blank" rel="noopener noreferrer">Preview submitted recording/link</a>
                                <?php endif; ?>
                                <?php if ($ms["status"] === "pending"): ?>
                                    <form method="POST" class="mt-2" onsubmit="return confirm('Remove this pending song request?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="delete_own_song">
                                        <input type="hidden" name="song_id" value="<?php echo (int)$ms["id"]; ?>">
                                        <button class="btn btn-sm btn-outline-danger">Remove pending request</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                <h5 class="fw-bold mb-3 text-success"><i class="fas fa-fire me-2"></i>Top Voted Songs Catalog</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Song</th>
                                <th>Artist</th>
                                <th>Category</th>
                                <th>Upvotes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_songs as $as): ?>
                                <tr>
                                    <td class="fw-bold"><?php echo htmlspecialchars($as['song_title']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($as['artist']); ?>
                                        <?php if (!empty($as["media_url"])): ?>
                                            <?php $audioExtension = strtolower(pathinfo((string)parse_url($as["media_url"], PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
                                            <?php if (in_array($audioExtension, ["mp3", "m4a", "wav", "ogg", "oga", "webm"], true)): ?>
                                                <audio controls preload="none" class="d-block mt-2" style="max-width:220px"><source src="<?php echo htmlspecialchars($as["media_url"]); ?>"></audio>
                                            <?php else: ?>
                                                <a href="<?php echo htmlspecialchars($as["media_url"]); ?>" target="_blank" rel="noopener noreferrer" class="small d-block mt-1">Play song link</a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($as['genre_category']); ?></span></td>
                                    <td><span class="fw-bold text-primary"><?php echo $as['votes_count']; ?></span></td>
                                    <td>
                                        <form method="POST" action="../songs.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                            <input type="hidden" name="action" value="vote_song">
                                            <input type="hidden" name="song_id" value="<?php echo (int)$as['id']; ?>">
                                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($as['genre_category']); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill" aria-label="Upvote <?php echo htmlspecialchars($as['song_title']); ?>">
                                                <i class="fas fa-thumbs-up"></i>
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
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="newSongModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-music text-warning me-2"></i>New Worship Song Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <input type="hidden" name="action" value="add_song">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Song Title <span class="text-danger">*</span></label>
                        <input type="text" name="song_title" class="form-control" required placeholder="e.g. Way Maker">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Artist / Choir</label>
                        <input type="text" name="artist" class="form-control" placeholder="e.g. Sinach">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="genre_category" class="form-select">
                                <option value="Worship">Worship</option>
                                <option value="Praise">Praise</option>
                                <option value="Hymn">Hymn</option>
                                <option value="Special">Special Service</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Key Note</label>
                            <input type="text" name="key_note" class="form-control" placeholder="e.g. G Major">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Lyrics / reference link</label>
                        <input type="url" name="lyrics_link" class="form-control" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Public song link</label>
                        <input type="url" name="media_url" class="form-control" placeholder="https://">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Or upload your recording</label>
                        <input type="file" name="audio_file" class="form-control" accept=".mp3,.m4a,.wav,.ogg,.oga,.webm,audio/*">
                        <div class="form-text">MP3, M4A, WAV, OGG, or WebM, up to 32 MB. Recordings are reviewed before public playback.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes / Special Request Reason</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
