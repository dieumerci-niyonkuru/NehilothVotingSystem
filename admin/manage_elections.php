<?php
require_once "../config/functions.php";
if (!canManageArea("elections")) redirect(isMemberLoggedIn() ? "../member/dashboard.php" : "login.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Please try again.");
        redirect("manage_elections.php");
    }

    $action = $_POST["action"] ?? "";
    if ($action === "transition") {
        $election_id = filter_var($_POST["election_id"] ?? null, FILTER_VALIDATE_INT);
        $transition = $_POST["transition"] ?? "";
        $result = $election_id ? transitionElection($election_id, $transition) : ["success" => false, "message" => "Invalid election."];
        setFlash($result["success"] ? "success" : "danger", $result["message"]);
        if ($result["success"]) {
            logAction($_SESSION["admin_id"] ?? 0, "Election #$election_id transition: $transition");
        }
        redirect("manage_elections.php");
    }

    if ($action === "create_election") {
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $timezone = new DateTimeZone("Africa/Kigali");
        $start = DateTimeImmutable::createFromFormat("Y-m-d\\TH:i", $_POST["start_time"] ?? "", $timezone);
        $start_errors = DateTimeImmutable::getLastErrors();
        $end = DateTimeImmutable::createFromFormat("Y-m-d\\TH:i", $_POST["end_time"] ?? "", $timezone);
        $end_errors = DateTimeImmutable::getLastErrors();
        $start_valid = $start && ($start_errors === false || ($start_errors["warning_count"] === 0 && $start_errors["error_count"] === 0));
        $end_valid = $end && ($end_errors === false || ($end_errors["warning_count"] === 0 && $end_errors["error_count"] === 0));
        if ($title === "" || strlen($title) > 255 || !$start_valid || !$end_valid || $end <= $start) {
            setFlash("danger", "Enter an election title and a valid period where closing is after opening.");
        } else {
            $stmt = $pdo->prepare("INSERT INTO elections (title, description, start_time, end_time, status) VALUES (?, ?, ?, ?, 'draft')");
            $stmt->execute([$title, $description, $start->format("Y-m-d H:i:s"), $end->format("Y-m-d H:i:s")]);
            logAction($_SESSION["admin_id"] ?? 0, "Created election #" . $pdo->lastInsertId());
            setFlash("success", "Election saved as a draft. Add positions and candidates, then publish it.");
        }
        redirect("manage_elections.php");
    }

    if ($action === "add_position") {
        $election_id = filter_var($_POST["election_id"] ?? null, FILTER_VALIDATE_INT);
        $title = trim($_POST["position_title"] ?? "");
        $description = trim($_POST["position_description"] ?? "");
        $election_stmt = $pdo->prepare("SELECT status FROM elections WHERE id = ?");
        $election_stmt->execute([(int)$election_id]);
        $election_status = $election_stmt->fetchColumn();
        if ($election_id && $title !== "" && strlen($title) <= 100 && $election_status === "draft") {
            $pdo->prepare("INSERT INTO positions (election_id, title, description) VALUES (?, ?, ?)")
                ->execute([(int)$election_id, $title, $description]);
            logAction($_SESSION["admin_id"] ?? 0, "Added position #" . $pdo->lastInsertId() . " to election #$election_id");
            setFlash("success", "Position added to election.");
        } else {
            setFlash("danger", "Enter a position title of 100 characters or fewer and select a draft election.");
        }
        redirect("manage_elections.php");
    }

    if ($action === "add_candidate") {
        $position_id = filter_var($_POST["position_id"] ?? null, FILTER_VALIDATE_INT);
        $name = trim($_POST["candidate_name"] ?? "");
        $position_stmt = $pdo->prepare("SELECT e.status FROM positions p JOIN elections e ON e.id = p.election_id WHERE p.id = ?");
        $position_stmt->execute([(int)$position_id]);
        $election_status = $position_stmt->fetchColumn();
        if ($position_id && $name !== "" && strlen($name) <= 100 && $election_status === "draft") {
            $stmt = $pdo->prepare("INSERT INTO candidates (position_id, name, vision_statement, manifesto, bio, photo_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                (int)$position_id,
                $name,
                trim($_POST["vision_statement"] ?? ""),
                trim($_POST["manifesto"] ?? ""),
                trim($_POST["bio"] ?? ""),
                trim($_POST["photo_url"] ?? "")
            ]);
            logAction($_SESSION["admin_id"] ?? 0, "Added candidate #" . $pdo->lastInsertId() . " to position #$position_id");
            setFlash("success", "Candidate registered for the position.");
        } else {
            setFlash("danger", "Enter a candidate name of 100 characters or fewer and select a position in a draft election.");
        }
        redirect("manage_elections.php");
    }
}

$elections = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC")->fetchAll();
$editable_elections = array_values(array_filter($elections, function ($election) {
    return $election["status"] === "draft";
}));
$all_positions = $pdo->query("SELECT p.*, e.title as election_title FROM positions p JOIN elections e ON p.election_id = e.id WHERE e.status = 'draft'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Elections - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-vote-yea text-primary me-2"></i>Election & Candidate Management</h2>
            <p class="text-muted small">Create elections, configure candidate manifestos, and review aggregate participation</p>
        </div>
        <div>
            <a href="<?php echo isMemberLoggedIn() ? '../member/leadership_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-primary rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#newElectionModal">
                <i class="fas fa-plus me-1"></i> Create Election
            </button>
        </div>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Action Bar for Positions & Candidates -->
    <div class="d-flex gap-2 mb-4">
        <button class="btn btn-success rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addPosModal"><i class="fas fa-plus me-1"></i> Add Position</button>
        <button class="btn btn-info text-dark rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#addCandModal"><i class="fas fa-user-plus me-1"></i> Register Candidate</button>
    </div>

    <!-- Active Elections Table -->
    <div class="card border-0 shadow-sm rounded-4 bg-white p-3 mb-4">
        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-list me-2 text-primary"></i>All Elections</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Election Title</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($elections as $el): ?>
                        <?php $el['status'] = getElectionStatus((int)$el['id']); ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($el['title']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($el['description']); ?></small>
                            </td>
                            <td><small><?php echo $el['start_time']; ?></small></td>
                            <td><small><?php echo $el['end_time']; ?></small></td>
                            <td>
                                <span class="badge <?php echo $el['status']=='open'?'bg-success':(in_array($el['status'], ['draft','upcoming','paused'], true)?'bg-warning text-dark':'bg-secondary'); ?> rounded-pill px-3">
                                    <?php echo strtoupper($el['status']); ?>
                                </span>
                                <?php if (!empty($el['results_published'])): ?><span class="badge bg-info text-dark">RESULTS PUBLISHED</span><?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    <?php
                                    $transitions = [];
                                    if ($el['status'] === 'draft') $transitions = ['publish' => 'Publish'];
                                    if ($el['status'] === 'open') $transitions = ['pause' => 'Pause', 'close' => 'Close'];
                                    if ($el['status'] === 'paused') $transitions = ['resume' => 'Resume', 'close' => 'Close'];
                                    if ($el['status'] === 'upcoming') $transitions = ['close' => 'Close', 'cancel' => 'Cancel'];
                                    if ($el['status'] === 'closed' && empty($el['results_published'])) $transitions = ['publish_results' => 'Publish results'];
                                    ?>
                                    <?php foreach ($transitions as $transition => $label): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                            <input type="hidden" name="action" value="transition">
                                            <input type="hidden" name="transition" value="<?php echo htmlspecialchars($transition); ?>">
                                            <input type="hidden" name="election_id" value="<?php echo (int)$el['id']; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $transition === 'close' || $transition === 'cancel' ? 'btn-outline-danger' : 'btn-outline-primary'; ?>"><?php echo htmlspecialchars($label); ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Create Election -->
<div class="modal fade" id="newElectionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Create New Election</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_election">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Election Title</label>
                        <input type="text" name="title" class="form-control" maxlength="255" required placeholder="Nehiloth Leadership Election 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Start Time (Africa/Kigali)</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">End Time (Africa/Kigali)</label>
                            <input type="datetime-local" name="end_time" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold">Create Election</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Add Position -->
<div class="modal fade" id="addPosModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Add Position</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_position">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Election</label>
                        <select name="election_id" class="form-select" required>
                            <?php foreach ($editable_elections as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Title</label>
                        <input type="text" name="position_title" class="form-control" maxlength="100" required placeholder="e.g. Secretary General">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="position_description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold">Add Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Register Candidate -->
<div class="modal fade" id="addCandModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Register Candidate</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_candidate">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Contested</label>
                        <select name="position_id" class="form-select" required>
                            <?php foreach ($all_positions as $pos): ?>
                                <option value="<?php echo $pos['id']; ?>"><?php echo htmlspecialchars($pos['election_title'] . ' -> ' . $pos['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Candidate Full Name</label>
                        <input type="text" name="candidate_name" class="form-control" maxlength="100" required placeholder="John Doe">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Leadership Vision</label>
                        <input type="text" name="vision_statement" class="form-control" placeholder="Short vision statement">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Manifesto / Key Objectives</label>
                        <textarea name="manifesto" class="form-control" rows="3" placeholder="1. Objective one&#10;2. Objective two"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Biography</label>
                        <textarea name="bio" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Photo URL</label>
                        <input type="url" name="photo_url" class="form-control" placeholder="https://">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info text-dark rounded-pill fw-bold">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
