<?php
require_once "config/functions.php";

$requested_election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
$elections = $pdo->query("
    SELECT id, title, status FROM elections
    WHERE status NOT IN ('draft','cancelled')
    ORDER BY start_time DESC, id DESC
")->fetchAll();
$election = null;
if ($requested_election_id) {
    $selected = $pdo->prepare("SELECT * FROM elections WHERE id = ? AND status NOT IN ('draft','cancelled')");
    $selected->execute([(int)$requested_election_id]);
    $election = $selected->fetch();
} else {
    foreach ($elections as $available_election) {
        $available_status = getElectionStatus((int)$available_election["id"]);
        if (in_array($available_status, ["upcoming", "open", "paused"], true)) {
            $selected = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
            $selected->execute([(int)$available_election["id"]]);
            $election = $selected->fetch();
            break;
        }
    }
}
if (!$election) {
    $election = $pdo->query("SELECT * FROM elections WHERE status = 'closed' ORDER BY end_time DESC, id DESC LIMIT 1")->fetch();
}

$election_id = $election ? (int)$election['id'] : 0;
$status = $election ? strtoupper(getElectionStatus($election_id)) : 'CLOSED';
$election = $election_id ? $pdo->query("SELECT * FROM elections WHERE id = " . $election_id)->fetch() : null;
$memberHasVoted = $election_id && isMemberLoggedIn() ? hasVotedInElection($_SESSION['member_id'], $election_id) : false;
$memberEligible = false;
if ($election_id && isMemberLoggedIn()) {
    $eligibility = $pdo->prepare("SELECT 1 FROM election_eligible_voters WHERE election_id = ? AND member_id = ?");
    $eligibility->execute([$election_id, (int)$_SESSION['member_id']]);
    $memberEligible = (bool)$eligibility->fetchColumn();
}

// Fetch positions & candidates
$positions = [];
if ($election_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE election_id = ?");
    $stmt->execute([$election_id]);
    $positions = $stmt->fetchAll();
}

// Handle Vote Submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "submit_ballot") {
    if (!isMemberLoggedIn()) {
        setFlash("warning", "Please log in with your phone number and password to cast your vote.");
        redirect("login.php?redirect=voting.php");
    }

    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and review your ballot again.");
        redirect("voting.php?election_id=" . $election_id);
    }

    $result = submitElectionBallot(
        (int)$_SESSION['member_id'],
        $election_id,
        $_POST["selections"] ?? [],
        $_POST["idempotency_key"] ?? ""
    );
    if (!empty($result["success"]) && !empty($result["receipt"])) {
        $result["message"] .= " Receipt: " . $result["receipt"];
    }
    if (!empty($result["success"])) {
        unset($_SESSION["ballot_idempotency_keys"][$election_id]);
    }
    setFlash(!empty($result["success"]) ? "success" : "danger", $result["message"]);
    redirect("voting.php?election_id=" . $election_id);
}

if ($election_id && isMemberLoggedIn() && empty($_SESSION["ballot_idempotency_keys"][$election_id])) {
    $_SESSION["ballot_idempotency_keys"][$election_id] = bin2hex(random_bytes(16));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership Election & Voting - NELILOTH WORSHIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; padding-bottom: 70px; }
        .hero-banner { background: linear-gradient(135deg, #4b6cb7 0%, #182848 100%); color: white; padding: 40px 0; }
        .candidate-card { border-radius: 20px; border: none; transition: transform 0.2s, box-shadow 0.2s; }
        .candidate-card:hover { transform: translateY(-4px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .cand-img { height: 180px; object-fit: cover; width: 100%; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <span class="badge <?php echo $status=='OPEN'?'bg-success':($status=='UPCOMING'?'bg-warning text-dark':'bg-danger'); ?> rounded-pill px-4 py-2 mb-2 fw-bold">
            <i class="fas fa-vote-yea me-1"></i> ELECTION STATUS: <?php echo $status; ?>
        </span>
        <h1 class="fw-bold mb-1"><?php echo $election ? htmlspecialchars($election['title']) : 'NELILOTH Leadership Election'; ?></h1>
        <p class="lead mb-0 text-light"><?php echo $election ? htmlspecialchars($election['description']) : 'Transparent Digital Voting System for Committee Leadership.'; ?></p>
    </div>
</div>

<div class="container mb-5">
    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
            <?php echo $f['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (count($elections) > 1): ?>
        <form method="GET" action="voting.php" class="bg-white rounded-4 shadow-sm p-3 mb-4 d-flex flex-wrap align-items-end gap-3">
            <div class="flex-grow-1">
                <label for="election_id" class="form-label fw-bold mb-1">Choose an election</label>
                <select id="election_id" name="election_id" class="form-select">
                    <?php foreach ($elections as $available_election): ?>
                        <?php $option_status = strtoupper(getElectionStatus((int)$available_election["id"])); ?>
                        <option value="<?php echo (int)$available_election["id"]; ?>" <?php echo (int)$available_election["id"] === $election_id ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($available_election["title"]); ?> (<?php echo htmlspecialchars($option_status); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary rounded-pill px-4">View election</button>
        </form>
    <?php endif; ?>

    <!-- Login Required Prompt Banner if not authenticated -->
    <?php if (!isMemberLoggedIn() && !isAdminLoggedIn()): ?>
        <div class="bg-dark text-white rounded-4 p-4 shadow-sm mb-4 border border-warning">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-warning mb-1"><i class="fas fa-lock me-2"></i>Member Authentication Required to Vote</h5>
                    <p class="small text-light mb-0">Every member must log in with their phone number and password before casting a secret ballot.</p>
                </div>
                <div class="d-flex gap-2 mt-3 mt-md-0">
                    <a href="login.php?redirect=voting.php" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-sign-in-alt me-1"></i> Member Login
                    </a>
                    <a href="admin/login.php" class="btn btn-warning text-dark rounded-pill px-4 fw-bold">
                        <i class="fas fa-user-shield me-1"></i> Admin Login
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Results Link Bar -->
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-0"><i class="fas fa-user-check text-primary me-2"></i>Secret Ballot System</h5>
            <small class="text-muted">One complete ballot per eligible member. Voter eligibility and ballot selections are stored separately; cryptographic anonymity is not provided.</small>
        </div>
        <?php if ($election && $status === 'CLOSED' && !empty($election['results_published'])): ?>
            <button class="btn btn-outline-primary rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#resultsModal">
                <i class="fas fa-chart-bar me-1"></i> View Official Results
            </button>
        <?php else: ?>
            <span class="badge bg-secondary rounded-pill px-3 py-2">Results available after verification</span>
        <?php endif; ?>
    </div>

    <!-- Committee Positions & Candidate Manifestos -->
    <?php if (empty($positions)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-poll-h fa-4x text-muted mb-3"></i>
            <h4 class="text-secondary">No positions configured for this election.</h4>
        </div>
    <?php else: ?>
        <form method="POST" action="voting.php?election_id=<?php echo $election_id; ?>">
            <input type="hidden" name="action" value="submit_ballot">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
            <input type="hidden" name="idempotency_key" value="<?php echo htmlspecialchars($_SESSION["ballot_idempotency_keys"][$election_id] ?? ""); ?>">
        <?php foreach ($positions as $p):
            $cands = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ?");
            $cands->execute([$p['id']]);
            $cand_list = $cands->fetchAll();

            $voted = $memberHasVoted;
        ?>
            <div class="bg-white rounded-4 p-4 shadow-sm mb-5">
                <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                    <div>
                        <h3 class="fw-bold text-dark mb-0"><i class="fas fa-award text-warning me-2"></i><?php echo htmlspecialchars($p['title']); ?></h3>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($p['description']); ?></p>
                    </div>
                    <?php if ($voted): ?>
                        <span class="badge bg-success rounded-pill px-4 py-2 fs-6"><i class="fas fa-check-circle me-1"></i>You Voted for this Position</span>
                    <?php endif; ?>
                </div>

                <div class="row g-4">
                    <?php foreach ($cand_list as $c): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card candidate-card shadow-sm h-100 bg-white">
                                <img src="<?php echo htmlspecialchars($c['photo_url']); ?>" class="cand-img" alt="<?php echo htmlspecialchars($c['name']); ?>">
                                <div class="card-body p-4 d-flex flex-column justify-content-between">
                                    <div>
                                        <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($c['name']); ?></h4>
                                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($c['bio']); ?></p>

                                        <?php if (!empty($c['vision_statement'])): ?>
                                            <div class="bg-light p-3 rounded-3 mb-3">
                                                <strong class="text-primary small d-block mb-1"><i class="fas fa-eye me-1"></i>Leadership Vision:</strong>
                                                <small class="text-dark">"<?php echo htmlspecialchars($c['vision_statement']); ?>"</small>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($c['manifesto'])): ?>
                                            <div class="mb-3">
                                                <strong class="text-secondary small d-block mb-1"><i class="fas fa-list-ol me-1"></i>Manifesto / Objectives:</strong>
                                                <small class="text-muted"><?php echo nl2br(htmlspecialchars($c['manifesto'])); ?></small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="pt-3 border-top mt-3">
                                        <?php if ($memberHasVoted): ?>
                                            <span class="badge bg-success w-100 p-2"><i class="fas fa-check me-1"></i> Ballot submitted</span>
                                        <?php elseif (!isMemberLoggedIn()): ?>
                                            <a href="login.php?redirect=voting.php" class="btn btn-outline-primary w-100 rounded-pill fw-bold">
                                                <i class="fas fa-sign-in-alt me-1"></i> Log In to Vote
                                            </a>
                                        <?php elseif (!$memberEligible): ?>
                                            <span class="badge bg-secondary w-100 p-2">Not on the eligible-voter list</span>
                                        <?php elseif ($status == 'OPEN'): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="selections[<?php echo (int)$p['id']; ?>]" value="<?php echo (int)$c['id']; ?>" id="candidate_<?php echo (int)$p['id']; ?>_<?php echo (int)$c['id']; ?>" required>
                                                <label class="form-check-label fw-semibold" for="candidate_<?php echo (int)$p['id']; ?>_<?php echo (int)$c['id']; ?>">Select this candidate</label>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary w-100 p-2">Voting <?php echo htmlspecialchars(strtolower($status)); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
            <?php if ($status === 'OPEN' && isMemberLoggedIn() && $memberEligible && !$memberHasVoted): ?>
                <div class="text-center mb-5">
                    <p class="text-muted">One ballot per election. Your selections cannot be changed after submission.</p>
                    <button type="submit" class="btn btn-success btn-lg rounded-pill px-5" onclick="return confirm('Submit your complete ballot? You cannot vote again in this election.')">
                        <i class="fas fa-lock me-1"></i> Submit Complete Ballot
                    </button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</div>

<!-- Live Results Modal -->
<div class="modal fade" id="resultsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header bg-dark text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-chart-bar text-warning me-2"></i>Official Election Results</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if ($election && $status === 'CLOSED' && !empty($election['results_published'])): ?>
                <?php foreach ($positions as $p):
                    $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM ballots WHERE election_id = ? AND position_id = ?");
                    $total_stmt->execute([$election_id, (int)$p['id']]);
                    $pos_votes = (int)$total_stmt->fetchColumn();
                    $cands_stmt = $pdo->prepare("SELECT c.*, COUNT(b.id) as cnt FROM candidates c LEFT JOIN ballots b ON c.id = b.candidate_id AND b.election_id = ? WHERE c.position_id = ? GROUP BY c.id ORDER BY cnt DESC");
                    $cands_stmt->execute([$election_id, (int)$p['id']]);
                    $cands_res = $cands_stmt->fetchAll();
                ?>
                    <div class="mb-4 p-3 border rounded-3 bg-light">
                        <h5 class="fw-bold text-primary mb-1"><?php echo htmlspecialchars($p['title']); ?></h5>
                        <small class="text-muted d-block mb-3">Total Ballots Cast: <?php echo $pos_votes; ?></small>

                        <?php foreach ($cands_res as $cr):
                            $pct = $pos_votes > 0 ? round(($cr['cnt'] / $pos_votes) * 100, 1) : 0;
                        ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="text-dark"><?php echo htmlspecialchars($cr['name']); ?></strong>
                                    <span class="fw-bold text-primary"><?php echo $cr['cnt']; ?> votes (<?php echo $pct; ?>%)</span>
                                </div>
                                <div class="progress" style="height: 12px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pct; ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted mb-0">Official results will appear here after the election is closed and results are published.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
