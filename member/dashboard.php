<?php
require_once "../config/functions.php";
if (!isMemberLoggedIn()) redirect("../login.php");

$member_id = $_SESSION["member_id"];
$member_name = $_SESSION["member_name"];
$positions = $pdo->query("SELECT * FROM positions")->fetchAll();
$active = isElectionActive();

// Get unread notifications
$notifications = getMemberNotifications($member_id, 10);
$unreadCount = count($notifications);

// Mark as read if requested
if (isset($_GET["mark_read"]) && is_numeric($_GET["mark_read"])) {
    markMemberNotificationRead((int)$_GET["mark_read"], $member_id);
    redirect("dashboard.php");
}
if (isset($_GET["mark_all"])) {
    markAllMemberNotificationsRead($member_id);
    redirect("dashboard.php");
}

// Get recent song requests and applications
$my_songs = getMemberSongRequests($member_id);
$stmt = $pdo->prepare("SELECT * FROM worship_applications WHERE member_id = ? ORDER BY applied_at DESC LIMIT 3");
$stmt->execute([$member_id]);
$my_applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - Nehiloth Worship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}.dash-card{border-radius:15px;border:none;box-shadow:0 5px 15px rgba(0,0,0,0.05);}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <!-- Welcome Header & Notification Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 bg-white p-3 p-md-4 rounded-4 shadow-sm">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-user-circle text-primary me-2"></i>Welcome, <?php echo htmlspecialchars($member_name); ?></h2>
            <p class="text-muted small mb-0">Nehiloth Worship Portal Member Dashboard</p>
        </div>

        <div class="d-flex align-items-center gap-2 mt-2 mt-md-0">
            <div class="dropdown">
                <button class="btn btn-outline-dark dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-bell me-1 text-warning"></i> Notifications
                    <?php if ($unreadCount > 0): ?><span class="badge bg-danger rounded-pill"><?php echo $unreadCount; ?></span><?php endif; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3" style="min-width: 280px;">
                    <?php if ($unreadCount == 0): ?>
                        <li><span class="dropdown-item text-muted text-center py-2">No unread notifications</span></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $n): ?>
                            <li>
                                <a class="dropdown-item py-2 border-bottom" href="?mark_read=<?php echo $n["id"]; ?>">
                                    <small><i class="fas fa-info-circle text-info me-1"></i> <?php echo htmlspecialchars($n["message"]); ?></small><br>
                                    <small class="text-muted" style="font-size:0.75rem;"><?php echo date("M d, H:i", strtotime($n["created_at"])); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <li><a class="dropdown-item text-center text-primary fw-bold py-2" href="?mark_all=1">Mark all as read</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <a href="../logout.php" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </div>

    <!-- Quick Navigation Bar -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="vote.php" class="card dash-card bg-primary text-white text-decoration-none p-3 text-center h-100">
                <i class="fas fa-vote-yea fa-2x mb-2"></i>
                <h6 class="fw-bold mb-0">Cast Vote</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="song_requests.php" class="card dash-card bg-warning text-dark text-decoration-none p-3 text-center h-100">
                <i class="fas fa-music fa-2x mb-2"></i>
                <h6 class="fw-bold mb-0">Song Requests</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="join_team.php" class="card dash-card bg-success text-white text-decoration-none p-3 text-center h-100">
                <i class="fas fa-user-plus fa-2x mb-2"></i>
                <h6 class="fw-bold mb-0">Join Worship Team</h6>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="../leadership.php" class="card dash-card bg-dark text-white text-decoration-none p-3 text-center h-100">
                <i class="fas fa-users-cog fa-2x mb-2"></i>
                <h6 class="fw-bold mb-0">Leadership</h6>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Elections & Voting Section -->
        <div class="col-lg-7">
            <div class="card dash-card bg-white p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-poll text-primary me-2"></i>Leadership Elections</h5>
                    <span class="badge <?php echo $active ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-3">
                        <?php echo $active ? 'ELECTION ACTIVE' : 'VOTING CLOSED'; ?>
                    </span>
                </div>

                <div class="alert alert-<?php echo $active ? 'info' : 'secondary'; ?> rounded-3">
                    <?php if ($active): ?>
                        Elections are currently live! Please support your candidates for the Nehiloth Worship Leadership Committee.
                        <div class="mt-2"><a href="vote.php" class="btn btn-primary btn-sm rounded-pill fw-bold">Proceed to Voting Ballot &rarr;</a></div>
                    <?php else: ?>
                        No active election currently in progress. Check back when the next leadership election is announced.
                    <?php endif; ?>
                </div>

                <h6 class="fw-bold text-secondary mt-3">Leadership Positions</h6>
                <div class="row g-2">
                    <?php foreach ($positions as $p):
                        $voted = hasVoted($_SESSION["member_id"], $p["id"]);
                        $cnt = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
                        $cnt->execute([$p["id"]]);
                    ?>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 d-flex flex-column justify-content-between">
                                <div>
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($p["title"]); ?></h6>
                                    <small class="text-muted d-block mb-2"><?php echo htmlspecialchars($p["description"]); ?></small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                                    <small class="text-secondary"><?php echo $cnt->fetchColumn(); ?> candidates</small>
                                    <?php if ($voted): ?>
                                        <span class="badge bg-success rounded-pill"><i class="fas fa-check-circle me-1"></i>Voted</span>
                                    <?php elseif ($active): ?>
                                        <a href="vote.php?position=<?php echo $p["id"]; ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">Vote</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary rounded-pill">Closed</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Song Requests & Join Team Applications Quick Summary -->
        <div class="col-lg-5">
            <!-- Song Requests Widget -->
            <div class="card dash-card bg-white p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-music text-warning me-2"></i>My Song Requests</h5>
                    <a href="song_requests.php" class="btn btn-sm btn-outline-warning text-dark rounded-pill">View All &rarr;</a>
                </div>
                <?php if (empty($my_songs)): ?>
                    <p class="text-muted small mb-0">No song requests submitted yet. <a href="song_requests.php">Submit a song request</a></p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($my_songs, 0, 3) as $ms): ?>
                            <div class="list-group-item px-0 py-2 border-bottom">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small"><?php echo htmlspecialchars($ms['song_title']); ?></span>
                                    <span class="badge bg-secondary rounded-pill"><?php echo strtoupper($ms['status']); ?></span>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($ms['artist']); ?> &bull; Upvotes: <?php echo $ms['votes_count']; ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Worship Team Application Widget -->
            <div class="card dash-card bg-white p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fas fa-user-plus text-success me-2"></i>Worship Team Status</h5>
                    <a href="join_team.php" class="btn btn-sm btn-outline-success rounded-pill">Apply Now &rarr;</a>
                </div>
                <?php if (empty($my_applications)): ?>
                    <p class="text-muted small mb-0">Interested in choir, band, tech, or media ministry? <a href="join_team.php" class="fw-bold">Apply to join Nehiloth Worship Team</a></p>
                <?php else: ?>
                    <?php foreach ($my_applications as $app): ?>
                        <div class="border rounded-3 p-3 bg-light mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="text-dark small"><?php echo htmlspecialchars($app['role_applied']); ?></strong>
                                <span class="badge bg-info text-dark rounded-pill"><?php echo strtoupper(str_replace('_', ' ', $app['status'])); ?></span>
                            </div>
                            <small class="text-muted d-block mt-1">Submitted on <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
