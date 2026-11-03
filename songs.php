<?php
require_once "config/functions.php";

$category = isset($_GET['category']) ? sanitize($_GET['category']) : null;
$songs = getPublicSongRequests($category);

// Handle anonymous or quick song request submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "submit_song") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("songs.php");
    }

    $requester = isMemberLoggedIn() ? ($_SESSION["member_name"] ?? "") : trim($_POST["requester_name"] ?? "");
    $title = trim($_POST["song_title"] ?? "");
    $artist = trim($_POST["artist"] ?? "");
    $cat = $_POST["genre_category"] ?? "Worship";
    $key = trim($_POST["key_note"] ?? "");
    $link = trim($_POST["lyrics_link"] ?? "");
    $media_link = trim($_POST["media_url"] ?? "");
    $notes = trim($_POST["notes"] ?? "");
    $member_id = isMemberLoggedIn() ? $_SESSION['member_id'] : null;

    $uploadPath = null;
    try {
        if ($title === "" || strlen($title) > 255 || $requester === "" || strlen($requester) > 100 ||
            strlen($artist) > 100 || strlen($key) > 20 || strlen($notes) > 5000 ||
            !in_array($cat, ["Worship", "Praise", "Hymn", "Special"], true) ||
            ($link !== "" && !isSafeMediaUrl($link)) || ($media_link !== "" && !isSafeMediaUrl($media_link))) {
            throw new RuntimeException("Check the required song title, category, and HTTP(S) links.");
        }

        if (!empty($_FILES["audio_file"]["name"])) {
            if (!$member_id) {
                throw new RuntimeException("Log in as a member to upload audio. You can still submit a public song link.");
            }
            if ($media_link !== "") {
                throw new RuntimeException("Choose either an audio upload or an audio link, not both.");
            }
            $uploadPath = storeSongAudio($_FILES["audio_file"]);
        }

        if (addSongRequest($member_id, $requester, $title, $artist, $cat, $key, $link, $notes, $uploadPath ?: ($media_link ?: null))) {
            setFlash("success", "Song submitted for leadership review. Approved songs will appear in the public player.");
        } else {
            if ($uploadPath) {
                $storedFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $uploadPath);
                if (is_file($storedFile) && !unlink($storedFile)) {
                    error_log("Unable to remove unused uploaded song file: " . $storedFile);
                }
            }
            setFlash("danger", "The song could not be saved. Please try again.");
        }
    } catch (RuntimeException $e) {
        setFlash("danger", $e->getMessage());
    }
    redirect("songs.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "vote_song") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
    } elseif (!isMemberLoggedIn()) {
        setFlash("info", "Please log in as a member to upvote song requests.");
    } else {
        $song_id = filter_var($_POST["song_id"] ?? null, FILTER_VALIDATE_INT);
        $member_id = (int)$_SESSION["member_id"];
        if (!$song_id || hasVotedForSong($song_id, $member_id)) {
            setFlash("warning", "This song was already upvoted or is no longer available.");
        } elseif (voteForSong($song_id, $member_id)) {
            setFlash("success", "Upvote recorded!");
        } else {
            setFlash("danger", "The upvote could not be saved.");
        }
    }
    redirect("songs.php" . (!empty($_POST["category"]) ? "?category=" . urlencode($_POST["category"]) : ""));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Song Requests & Worship Catalog - Nehiloth Worship</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#172b55">
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; }
        .hero-banner { background: linear-gradient(135deg, #172b55 0%, #283f73 72%, #b99c50 150%); color: white; padding: 40px 0; }
        .song-card { border-radius: 15px; border: none; transition: transform 0.2s, shadow 0.2s; }
        .song-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .badge-status { font-size: 0.8rem; padding: 6px 12px; border-radius: 50px; }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-music text-warning me-2"></i>Nehiloth Song Library</h1>
        <p class="lead mb-3">Listen to approved worship songs, share a public link or member recording, and help prepare the next rehearsal.</p>
        <button class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#requestModal">
            <i class="fas fa-plus-circle me-1"></i> Submit New Song Request
        </button>
    </div>
</div>

<div class="container mb-5">
    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $f['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Category Filter Buttons -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <a href="songs.php" class="btn btn-outline-dark rounded-pill px-3 <?php echo empty($category) ? 'active' : ''; ?>">All Categories</a>
        <a href="songs.php?category=Praise" class="btn btn-outline-primary rounded-pill px-3 <?php echo $category == 'Praise' ? 'active' : ''; ?>">Praise</a>
        <a href="songs.php?category=Worship" class="btn btn-outline-purple rounded-pill px-3 <?php echo $category == 'Worship' ? 'active' : ''; ?>" style="border-color:#6f42c1; color:#6f42c1;">Worship</a>
        <a href="songs.php?category=Hymn" class="btn btn-outline-success rounded-pill px-3 <?php echo $category == 'Hymn' ? 'active' : ''; ?>">Hymns</a>
        <a href="songs.php?category=Special" class="btn btn-outline-danger rounded-pill px-3 <?php echo $category == 'Special' ? 'active' : ''; ?>">Special Service</a>
    </div>

    <!-- Song List Grid -->
    <div class="row g-4">
        <?php if (empty($songs)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-compact-disc fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No song requests found in this category.</h4>
                <p>Be the first to submit a worship song request!</p>
            </div>
        <?php else: ?>
            <?php foreach ($songs as $s): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card song-card shadow-sm h-100 p-3 bg-white">
                        <div class="card-body d-flex flex-column justify-content-between p-2">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary rounded-pill px-3"><?php echo htmlspecialchars($s['genre_category']); ?></span>
                                    <?php
                                        $st = $s['status'];
                                        $badgeClass = $st == 'approved' ? 'bg-success' : ($st == 'scheduled' ? 'bg-info text-dark' : ($st == 'rejected' ? 'bg-danger' : 'bg-warning text-dark'));
                                    ?>
                                    <span class="badge badge-status <?php echo $badgeClass; ?>"><?php echo strtoupper($st); ?></span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><i class="fas fa-play-circle text-primary me-2"></i><?php echo htmlspecialchars($s['song_title']); ?></h5>
                                <p class="text-muted mb-2"><i class="fas fa-user-tag me-1"></i>Artist: <strong><?php echo htmlspecialchars($s['artist']); ?></strong></p>
                                <p class="small text-secondary mb-2"><i class="fas fa-key me-1"></i>Key: <span class="fw-bold text-dark"><?php echo htmlspecialchars($s['key_note']); ?></span></p>

                                <?php if (!empty($s["media_url"])): ?>
                                    <?php $mediaExtension = strtolower(pathinfo((string)parse_url($s["media_url"], PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
                                    <?php if (in_array($mediaExtension, ["mp3", "m4a", "wav", "ogg", "oga", "webm"], true)): ?>
                                        <audio controls preload="none" class="w-100 mb-3">
                                            <source src="<?php echo htmlspecialchars($s["media_url"]); ?>">
                                            Your browser cannot play this audio format.
                                        </audio>
                                    <?php else: ?>
                                        <a class="btn btn-sm btn-outline-primary mb-3" href="<?php echo htmlspecialchars($s["media_url"]); ?>" target="_blank" rel="noopener noreferrer"><i class="fas fa-external-link-alt me-1"></i>Play song link</a>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if (!empty($s["lyrics_link"])): ?>
                                    <a class="small d-block mb-2" href="<?php echo htmlspecialchars($s["lyrics_link"]); ?>" target="_blank" rel="noopener noreferrer">Lyrics / reference link</a>
                                <?php endif; ?>

                                <?php if (!empty($s['notes'])): ?>
                                    <p class="small bg-light p-2 rounded text-muted mb-2"><em>"<?php echo htmlspecialchars($s['notes']); ?>"</em></p>
                                <?php endif; ?>
                            </div>

                            <div class="pt-3 border-top mt-3 d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="fas fa-user me-1"></i>Requested by <?php echo htmlspecialchars($s['requester_name']); ?></small>
                                <?php if (isMemberLoggedIn()): ?>
                                    <form method="POST" class="m-0">
                                        <input type="hidden" name="action" value="vote_song">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="song_id" value="<?php echo (int)$s["id"]; ?>">
                                        <input type="hidden" name="category" value="<?php echo htmlspecialchars($category ?? ""); ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                                            <i class="fas fa-thumbs-up me-1"></i> <?php echo (int)$s['votes_count']; ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="login.php?redirect=songs.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold" aria-label="Log in to upvote">
                                        <i class="fas fa-thumbs-up me-1"></i> <?php echo (int)$s['votes_count']; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Song Request Modal -->
<div class="modal fade" id="requestModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-music text-warning me-2"></i>Submit Worship Song Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="submit_song">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <?php if (!isMemberLoggedIn()): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Your Name</label>
                        <input type="text" name="requester_name" class="form-control" maxlength="100" required placeholder="Enter your full name">
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Song Title</label>
                        <input type="text" name="song_title" class="form-control" required placeholder="e.g. Way Maker">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Artist / Band</label>
                            <input type="text" name="artist" class="form-control" placeholder="e.g. Sinach">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Category</label>
                            <select name="genre_category" class="form-select">
                                <option value="Worship">Worship</option>
                                <option value="Praise">Praise</option>
                                <option value="Hymn">Hymn</option>
                                <option value="Special">Special Service</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Key Note (Optional)</label>
                            <input type="text" name="key_note" class="form-control" placeholder="e.g. G Major">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Public song / YouTube link</label>
                            <input type="url" name="media_url" class="form-control" placeholder="https://">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Lyrics / reference link</label>
                            <input type="url" name="lyrics_link" class="form-control" placeholder="https://">
                        </div>
                    </div>
                    <?php if (isMemberLoggedIn()): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Or upload your recording</label>
                        <input type="file" name="audio_file" class="form-control" accept=".mp3,.m4a,.wav,.ogg,.oga,.webm,audio/*">
                        <div class="form-text">MP3, M4A, WAV, OGG, or WebM, up to 32 MB. Uploaded recordings are reviewed before appearing publicly.</div>
                    </div>
                    <?php else: ?>
                    <p class="small text-muted">To upload a recording, <a href="login.php">sign in as an approved member</a>. Public song links may be submitted without signing in.</p>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Additional Notes / Remarks</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Why would you love to sing this song?"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
