<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

$stats = getDashboardStats();

$recentVotes = $pdo->query("
    SELECT e.title, COUNT(r.id) AS ballot_count, MAX(r.submitted_at) AS latest_submission
    FROM elections e
    LEFT JOIN election_vote_receipts r ON r.election_id = e.id
    GROUP BY e.id, e.title
    ORDER BY e.created_at DESC
    LIMIT 5
")->fetchAll();
$recentSongs = getSongRequests('pending', null, 5);
$recentApplications = getWorshipApplications('pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NELILOTH WORSHIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; padding-bottom: 70px; }
        .stat-card { border-radius: 15px; border: none; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-4px); }
    </style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container-fluid py-4 px-md-5">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 bg-white p-3 p-md-4 rounded-4 shadow-sm">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-user-shield text-warning me-2"></i>NELILOTH Administrator Control Panel</h2>
            <p class="text-muted small mb-0">CEP Family - Rwanda Polytechnic, Tumba College Digital Platform Administration</p>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="reports.php" class="btn btn-success rounded-pill px-3"><i class="fas fa-file-download me-1"></i> Export Reports</a>
            <a href="logout.php" class="btn btn-danger rounded-pill px-3"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>
    </div>

    <!-- Quick Stats Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_members.php" class="card stat-card bg-primary text-white p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-0"><?php echo $stats['approved_members']; ?></h3>
                        <small class="text-white-50">Members</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_elections.php" class="card stat-card <?php echo $stats['election_active'] ? 'bg-danger text-white' : 'bg-secondary text-white'; ?> p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0"><?php echo $stats['election_active'] ? 'ACTIVE' : 'OFFLINE'; ?></h5>
                        <small class="text-white-50">Elections</small>
                    </div>
                    <i class="fas fa-vote-yea fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_schedule.php" class="card stat-card bg-success text-white p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Schedule</h5>
                        <small class="text-white-50">Weekly Worship</small>
                    </div>
                    <i class="fas fa-calendar-alt fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_announcements.php" class="card stat-card bg-warning text-dark p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">News</h5>
                        <small class="text-dark">Announcements</small>
                    </div>
                    <i class="fas fa-bullhorn fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_applications.php" class="card stat-card bg-info text-dark p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-0"><?php echo $stats['pending_applications']; ?></h3>
                        <small class="text-dark">Team Applicants</small>
                    </div>
                    <i class="fas fa-hands-helping fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
        <div class="col-6 col-md-4 col-xl-2">
            <a href="manage_gallery.php" class="card stat-card bg-dark text-white p-3 text-decoration-none shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="fw-bold mb-0">Gallery</h5>
                        <small class="text-white-50">Photos & Videos</small>
                    </div>
                    <i class="fas fa-images fa-2x opacity-75"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- Main Admin Controls Grid -->
    <div class="row g-4">
        <!-- Sidebar Navigation Buttons -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white mb-4">
                <h6 class="fw-bold text-muted mb-3"><i class="fas fa-cogs me-2"></i>Administration Modules</h6>
                <div class="d-grid gap-2">
                    <a href="manage_members.php" class="btn btn-outline-primary text-start fw-bold"><i class="fas fa-users me-2"></i> Member Management</a>
                    <a href="manage_elections.php" class="btn btn-outline-danger text-start fw-bold"><i class="fas fa-vote-yea me-2"></i> Committee Elections</a>
                    <a href="manage_schedule.php" class="btn btn-outline-success text-start fw-bold"><i class="fas fa-calendar-alt me-2"></i> Weekly Worship Schedule</a>
                    <a href="manage_announcements.php" class="btn btn-outline-warning text-dark text-start fw-bold"><i class="fas fa-bullhorn me-2"></i> News & Announcements</a>
                    <a href="manage_applications.php" class="btn btn-outline-info text-start fw-bold"><i class="fas fa-hands-helping me-2"></i> Worship Team Applications</a>
                    <a href="manage_songs.php" class="btn btn-outline-secondary text-start fw-bold"><i class="fas fa-music me-2"></i> Song Requests Manager</a>
                    <a href="manage_leadership.php" class="btn btn-outline-dark text-start fw-bold"><i class="fas fa-users-cog me-2"></i> Leadership Directory</a>
                    <a href="manage_gallery.php" class="btn btn-outline-danger text-start fw-bold"><i class="fas fa-images me-2"></i> Worship Gallery</a>
                    <a href="reports.php" class="btn btn-success text-start fw-bold"><i class="fas fa-file-export me-2"></i> Export CSV Reports</a>
                </div>
            </div>
        </div>

        <!-- Pending Items & Activity Tables -->
        <div class="col-lg-9">
            <div class="row g-4">
                <!-- Pending Worship Applications Queue -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-plus text-success me-2"></i>Pending Worship Applications</h6>
                            <a href="manage_applications.php" class="btn btn-sm btn-outline-success rounded-pill">View All</a>
                        </div>
                        <?php if (empty($recentApplications)): ?>
                            <p class="text-muted small py-3 text-center mb-0">No pending team applications.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($recentApplications, 0, 4) as $app): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark small"><?php echo htmlspecialchars($app['applicant_name']); ?></strong>
                                            <span class="badge bg-primary rounded-pill"><?php echo htmlspecialchars($app['role_applied']); ?></span>
                                        </div>
                                        <small class="text-muted"><?php echo htmlspecialchars($app['phone']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pending Song Requests Queue -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="fas fa-music text-warning me-2"></i>Pending Song Requests</h6>
                            <a href="manage_songs.php" class="btn btn-sm btn-outline-warning text-dark rounded-pill">View All</a>
                        </div>
                        <?php if (empty($recentSongs)): ?>
                            <p class="text-muted small py-3 text-center mb-0">No pending song requests.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentSongs as $s): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong class="text-dark small"><?php echo htmlspecialchars($s['song_title']); ?></strong>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($s['genre_category']); ?></span>
                                        </div>
                                        <small class="text-muted">By <?php echo htmlspecialchars($s['artist']); ?> &bull; Requested by <?php echo htmlspecialchars($s['requester_name']); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aggregate Election Participation -->
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-user-check text-primary me-2"></i>Election Participation (Aggregate)</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Election</th>
                                        <th>Ballots Submitted</th>
                                        <th>Latest Submission</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentVotes)): ?>
                                        <tr><td colspan="3" class="text-center py-3 text-muted">No election participation recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentVotes as $rv): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($rv["title"]); ?></td>
                                                <td><?php echo (int)$rv["ballot_count"]; ?></td>
                                                <td><small class="text-muted"><?php echo $rv["latest_submission"] ? htmlspecialchars(date("M d, H:i", strtotime($rv["latest_submission"]))) : "Not yet"; ?></small></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
