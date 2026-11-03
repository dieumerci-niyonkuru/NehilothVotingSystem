<?php
require_once "config/functions.php";
$stats = getDashboardStats();
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC LIMIT 3")->fetchAll();
$schedules = $pdo->query("SELECT * FROM weekly_schedules WHERE date_scheduled >= CURDATE() ORDER BY date_scheduled ASC, start_time ASC LIMIT 3")->fetchAll();
$featuredSongs = getPublicSongRequests(null, 4);
$election = $pdo->query("SELECT * FROM elections WHERE status = 'open' LIMIT 1")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nehiloth Worship Team - Songs, Rehearsals & Community</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#172b55">
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; padding-bottom: 70px; }
        .hero-section { background: linear-gradient(135deg, #172b55 0%, #283f73 75%, #b99c50 150%); color: white; padding: 42px 0 50px 0; border-radius: 0 0 30px 30px; }
        .feature-card { border-radius: 20px; border: none; transition: transform 0.3s, box-shadow 0.3s; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        .stat-box { background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); border-radius: 15px; padding: 15px; }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<!-- Hero Splash Section -->
<div class="hero-section text-center shadow mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <img src="/logo/logo.jpg" alt="Nehiloth Worship Team logo" class="img-fluid rounded-circle bg-white p-2 mb-3 shadow" style="width:120px;height:120px;object-fit:contain">
                <h1 class="display-4 fw-bold mb-2">NEHILOTH WORSHIP TEAM</h1>
                <p class="lead fw-normal text-warning mb-3">Rwanda Polytechnic &bull; Tumba College</p>
                <p class="fs-5 fst-italic text-light mb-4">One family, one purpose, one voice in worship.</p>

                <!-- Live Metrics Bar -->
                <div class="row g-3 justify-content-center mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <h2 class="fw-bold mb-0 text-warning"><?php echo $stats['approved_members']; ?></h2>
                            <small class="text-light">Active Members</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <h2 class="fw-bold mb-0 text-info"><?php echo $stats['total_song_requests']; ?></h2>
                            <small class="text-light">Song Requests</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <h2 class="fw-bold mb-0 text-success"><?php echo $stats['leadership_count']; ?></h2>
                            <small class="text-light">Worship Leaders</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-box">
                            <h2 class="fw-bold mb-0 <?php echo $stats['election_active'] ? 'text-danger' : 'text-light'; ?>">
                                <?php echo $stats['election_active'] ? 'OPEN' : 'OFFLINE'; ?>
                            </h2>
                            <small class="text-light">Voting Status</small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="voting.php" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-vote-yea me-2"></i> Committee Voting
                    </a>
                    <a href="schedule.php" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-calendar-alt me-2"></i> Worship Schedule
                    </a>
                    <a href="join_team.php" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                        <i class="fas fa-user-plus me-2"></i> Join Worship Team
                    </a>
                    <a href="songs.php" class="btn btn-outline-warning btn-lg rounded-pill px-4 fw-bold">
                        <i class="fas fa-headphones me-2"></i> Listen to Songs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Sections Grid -->
<div class="container mb-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark">Platform Sections & Portals</h2>
        <p class="text-muted">Explore weekly schedules, announcements, committee elections, and worship media</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-6 col-md-4 col-lg-3">
            <a href="voting.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-vote-yea fa-3x text-primary mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Voting Engine</h5>
                <p class="text-muted small mb-0">Transparent committee elections</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="schedule.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Weekly Schedule</h5>
                <p class="text-muted small mb-0">Rehearsals & Sunday duties</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="announcements.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-bullhorn fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">News & Updates</h5>
                <p class="text-muted small mb-0">News from the Nehiloth Worship Team</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="leadership.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-users-cog fa-3x text-danger mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Leadership</h5>
                <p class="text-muted small mb-0">Committee leaders & roles</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="songs.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-music fa-3x text-info mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Song Requests</h5>
                <p class="text-muted small mb-0">Request & upvote songs</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="join_team.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-hands-helping fa-3x text-success mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Join Ministry</h5>
                <p class="text-muted small mb-0">Apply for choir or band</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="gallery.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-images fa-3x text-secondary mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">Gallery</h5>
                <p class="text-muted small mb-0">Worship photos & videos</p>
            </a>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <a href="about.php" class="card feature-card shadow-sm h-100 p-4 text-center bg-white text-decoration-none">
                <i class="fas fa-university fa-3x text-dark mb-3"></i>
                <h5 class="fw-bold text-dark mb-1">About Nehiloth</h5>
                <p class="text-muted small mb-0">Our worship community, purpose & mission</p>
            </a>
        </div>
    </div>

    <!-- Active Election Banner if Open -->
    <?php if ($election): ?>
        <div class="bg-primary text-white rounded-4 p-4 shadow mb-5">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <span class="badge bg-warning text-dark fw-bold mb-2">ELECTION IS NOW OPEN</span>
                    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($election['title']); ?></h3>
                    <p class="mb-0 text-white-50">Participate in electing the next Nehiloth Worship Team leadership committee.</p>
                </div>
                <a href="voting.php" class="btn btn-warning text-dark btn-lg rounded-pill px-4 fw-bold mt-3 mt-md-0">
                    <i class="fas fa-vote-yea me-1"></i> Cast Vote Now &rarr;
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Latest News & Upcoming Worship Schedules Grid -->
    <div class="row g-4">
        <!-- News Feed Preview -->
        <div class="col-lg-6">
            <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h4 class="fw-bold text-dark mb-0"><i class="fas fa-bullhorn text-warning me-2"></i>Latest Announcements</h4>
                    <a href="announcements.php" class="btn btn-sm btn-outline-dark rounded-pill">View All &rarr;</a>
                </div>
                <?php if (empty($announcements)): ?>
                    <p class="text-muted small">No announcements published yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($announcements as $a): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <strong class="text-dark small d-block"><?php echo htmlspecialchars($a['title']); ?></strong>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($a['content'], 0, 90)) . '...'; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Weekly Schedule Preview -->
        <div class="col-lg-6">
            <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h4 class="fw-bold text-dark mb-0"><i class="fas fa-calendar-alt text-success me-2"></i>Upcoming Worship Schedule</h4>
                    <a href="schedule.php" class="btn btn-sm btn-outline-success rounded-pill">View Full Schedule &rarr;</a>
                </div>
                <?php if (empty($schedules)): ?>
                    <p class="text-muted small">No upcoming worship activities scheduled.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($schedules as $sc): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark small"><?php echo htmlspecialchars($sc['activity_title']); ?></strong>
                                    <span class="badge bg-success rounded-pill"><?php echo $sc['day_of_week']; ?></span>
                                </div>
                                <small class="text-muted"><i class="fas fa-clock me-1 text-primary"></i><?php echo date('H:i', strtotime($sc['start_time'])); ?> at <?php echo htmlspecialchars($sc['location']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <section class="bg-white rounded-4 p-4 shadow-sm mt-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div><h2 class="h4 fw-bold mb-1"><i class="fas fa-headphones text-warning me-2"></i>Song Library & Rehearsal Picks</h2><p class="text-muted small mb-0">Play approved recordings and discover songs being prepared by the team.</p></div>
                <a href="songs.php" class="btn btn-outline-primary">Open song library</a>
            </div>
            <div class="row g-3">
                <?php foreach ($featuredSongs as $song): ?>
                    <div class="col-md-6 col-lg-3">
                        <article class="border rounded-3 p-3 h-100">
                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($song["genre_category"]); ?></span>
                            <h3 class="h6 fw-bold mt-2 mb-1"><?php echo htmlspecialchars($song["song_title"]); ?></h3>
                            <p class="small text-muted"><?php echo htmlspecialchars($song["artist"]); ?></p>
                            <?php if (!empty($song["media_url"])): ?>
                                <?php $audioExtension = strtolower(pathinfo((string)parse_url($song["media_url"], PHP_URL_PATH), PATHINFO_EXTENSION)); ?>
                                <?php if (in_array($audioExtension, ["mp3", "m4a", "wav", "ogg", "oga", "webm"], true)): ?>
                                    <audio controls preload="none" class="w-100"><source src="<?php echo htmlspecialchars($song["media_url"]); ?>"></audio>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars($song["media_url"]); ?>" target="_blank" rel="noopener noreferrer" class="small">Play song link</a>
                                <?php endif; ?>
                            <?php else: ?><span class="small text-muted">Recording not added yet</span><?php endif; ?>
                        </article>
                    </div>
                <?php endforeach; ?>
                <?php if (!$featuredSongs): ?><p class="text-muted mb-0">Approved songs will appear here after team review.</p><?php endif; ?>
            </div>
        </section>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
