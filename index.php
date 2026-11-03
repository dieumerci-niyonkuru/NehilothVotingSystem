<?php
require_once "config/functions.php";
$stats = getDashboardStats();
$topSongs = getSongRequests(null, null, 3);
$leaders = getLeadershipTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nehiloth Worship & Voting System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; }
        .hero-section { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 70px 0; border-radius: 0 0 30px 30px; }
        .feature-card { border-radius: 20px; border: none; transition: transform 0.3s, box-shadow 0.3s; }
        .feature-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.1) !important; }
        .stat-box { background: rgba(255,255,255,0.15); backdrop-filter: blur(5px); border-radius: 15px; padding: 15px; }
        .leader-avatar { width: 80px; height: 80px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<!-- Hero Section -->
<div class="hero-section text-center shadow">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <i class="fas fa-church fa-4x text-warning mb-3"></i>
                <h1 class="display-4 fw-bold mb-3">Nehiloth Worship Portal</h1>
                <p class="lead mb-4">Empowering praise & worship, song requests, leadership elections, and ministry service.</p>

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
                                <?php echo $stats['election_active'] ? 'ACTIVE' : 'OFFLINE'; ?>
                            </h2>
                            <small class="text-light">Elections</small>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a href="songs.php" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-music me-2"></i> Request Song
                    </a>
                    <a href="join_team.php" class="btn btn-success btn-lg rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-user-plus me-2"></i> Join Worship Team
                    </a>
                    <a href="login.php" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                        <i class="fas fa-vote-yea me-2"></i> Member Elections
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Features Grid -->
<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold text-dark">Ministry Features & Portals</h2>
        <p class="text-muted">Everything you need to engage with Nehiloth Worship Team</p>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-6 col-lg-3">
            <div class="card feature-card shadow-sm h-100 p-4 text-center bg-white">
                <i class="fas fa-music fa-3x text-warning mb-3"></i>
                <h5 class="fw-bold text-dark mb-2">Song Requests</h5>
                <p class="text-muted small mb-4">Request praise and worship songs and upvote your favorite tracks for Sunday services.</p>
                <a href="songs.php" class="btn btn-outline-warning text-dark rounded-pill mt-auto fw-bold">Explore Songs &rarr;</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card feature-card shadow-sm h-100 p-4 text-center bg-white">
                <i class="fas fa-hands-helping fa-3x text-success mb-3"></i>
                <h5 class="fw-bold text-dark mb-2">Join Worship Team</h5>
                <p class="text-muted small mb-4">Apply for choir vocalists, band musicians, sound engineering, or media ministry.</p>
                <a href="join_team.php" class="btn btn-outline-success rounded-pill mt-auto fw-bold">Apply Now &rarr;</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card feature-card shadow-sm h-100 p-4 text-center bg-white">
                <i class="fas fa-users-cog fa-3x text-danger mb-3"></i>
                <h5 class="fw-bold text-dark mb-2">Leadership Team</h5>
                <p class="text-muted small mb-4">Meet the directors, worship leaders, and technical coordinators serving the ministry.</p>
                <a href="leadership.php" class="btn btn-outline-danger rounded-pill mt-auto fw-bold">Meet Leaders &rarr;</a>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card feature-card shadow-sm h-100 p-4 text-center bg-white">
                <i class="fas fa-vote-yea fa-3x text-primary mb-3"></i>
                <h5 class="fw-bold text-dark mb-2">Voting & Elections</h5>
                <p class="text-muted small mb-4">Participate in democratic voting for worship committee leadership roles.</p>
                <a href="login.php" class="btn btn-outline-primary rounded-pill mt-auto fw-bold">Member Portal &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Top Voted Songs Preview -->
    <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold text-dark mb-0"><i class="fas fa-fire text-warning me-2"></i>Top Requested Songs</h3>
                <p class="text-muted small mb-0">Most upvoted songs for upcoming worship services</p>
            </div>
            <a href="songs.php" class="btn btn-outline-dark rounded-pill px-4 fw-bold">View Full Catalog &rarr;</a>
        </div>

        <div class="row g-3">
            <?php if (empty($topSongs)): ?>
                <p class="text-muted">No song requests submitted yet.</p>
            <?php else: ?>
                <?php foreach ($topSongs as $ts): ?>
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($ts['genre_category']); ?></span>
                                    <span class="badge bg-success rounded-pill"><i class="fas fa-thumbs-up me-1"></i><?php echo $ts['votes_count']; ?></span>
                                </div>
                                <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ts['song_title']); ?></h5>
                                <p class="text-muted small mb-0">Artist: <strong><?php echo htmlspecialchars($ts['artist']); ?></strong></p>
                            </div>
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-secondary">Requested by <?php echo htmlspecialchars($ts['requester_name']); ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leadership Highlights Section -->
    <div class="text-center mb-4">
        <h3 class="fw-bold text-dark"><i class="fas fa-user-tie text-danger me-2"></i>Leadership Highlights</h3>
        <p class="text-muted">Serving with excellence and spiritual passion</p>
    </div>

    <div class="row g-4 justify-content-center mb-5">
        <?php foreach (array_slice($leaders, 0, 3) as $ld): ?>
            <div class="col-md-4 text-center">
                <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                    <img src="<?php echo htmlspecialchars($ld['photo_url']); ?>" class="leader-avatar mb-3 shadow-sm" alt="<?php echo htmlspecialchars($ld['name']); ?>">
                    <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($ld['name']); ?></h5>
                    <span class="badge bg-danger rounded-pill px-3 mb-2"><?php echo htmlspecialchars($ld['role']); ?></span>
                    <p class="text-muted small mb-0"><?php echo htmlspecialchars(substr($ld['bio'], 0, 90)) . '...'; ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
