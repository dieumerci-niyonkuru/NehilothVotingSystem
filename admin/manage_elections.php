<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle Create Election
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'create_election') {
    $title = sanitize($_POST['title']);
    $desc = sanitize($_POST['description']);
    $start = sanitize($_POST['start_time']);
    $end = sanitize($_POST['end_time']);

    if (!empty($title) && !empty($start) && !empty($end)) {
        $stmt = $pdo->prepare("INSERT INTO elections (title, description, start_time, end_time, status) VALUES (?, ?, ?, ?, 'open')");
        $stmt->execute([$title, $desc, $start, $end]);
        setFlash("success", "New election created and opened!");
    }
    redirect("manage_elections.php");
}

// Handle Add Position
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_position') {
    $election_id = (int)$_POST['election_id'];
    $title = sanitize($_POST['position_title']);
    $desc = sanitize($_POST['position_description']);

    if ($election_id > 0 && !empty($title)) {
        $stmt = $pdo->prepare("INSERT INTO positions (election_id, title, description) VALUES (?, ?, ?)");
        $stmt->execute([$election_id, $title, $desc]);
        setFlash("success", "Position added to election!");
    }
    redirect("manage_elections.php");
}

// Handle Add Candidate with Manifesto
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_candidate') {
    $pos_id = (int)$_POST['position_id'];
    $name = sanitize($_POST['candidate_name']);
    $vision = sanitize($_POST['vision_statement']);
    $manifesto = sanitize($_POST['manifesto']);
    $bio = sanitize($_POST['bio']);
    $photo = sanitize($_POST['photo_url']);

    if ($pos_id > 0 && !empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO candidates (position_id, name, vision_statement, manifesto, bio, photo_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$pos_id, $name, $vision, $manifesto, $bio, $photo]);
        setFlash("success", "Candidate registered for position!");
    }
    redirect("manage_elections.php");
}

// Handle Election Status Toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $new_status = $_GET['toggle_status'];
    $pdo->prepare("UPDATE elections SET status = ? WHERE id = ?")->execute([$new_status, $id]);
    setFlash("info", "Election status updated to $new_status!");
    redirect("manage_elections.php");
}

$elections = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC")->fetchAll();
$all_positions = $pdo->query("SELECT p.*, e.title as election_title FROM positions p JOIN elections e ON p.election_id = e.id")->fetchAll();
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
            <p class="text-muted small">Create elections, configure candidate manifestos, and monitor secret ballots</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
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
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($el['title']); ?></strong>
                                <br><small class="text-muted"><?php echo htmlspecialchars($el['description']); ?></small>
                            </td>
                            <td><small><?php echo $el['start_time']; ?></small></td>
                            <td><small><?php echo $el['end_time']; ?></small></td>
                            <td>
                                <span class="badge <?php echo $el['status']=='open'?'bg-success':($el['status']=='upcoming'?'bg-warning text-dark':'bg-danger'); ?> rounded-pill px-3">
                                    <?php echo strtoupper($el['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="manage_elections.php?toggle_status=open&id=<?php echo $el['id']; ?>" class="btn btn-success" title="Open Election"><i class="fas fa-play"></i></a>
                                    <a href="manage_elections.php?toggle_status=closed&id=<?php echo $el['id']; ?>" class="btn btn-danger" title="Close Election"><i class="fas fa-stop"></i></a>
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Election Title</label>
                        <input type="text" name="title" class="form-control" required placeholder="NELILOTH Leadership Election 2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Start Time</label>
                            <input type="datetime-local" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">End Time</label>
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Election</label>
                        <select name="election_id" class="form-select" required>
                            <?php foreach ($elections as $e): ?>
                                <option value="<?php echo $e['id']; ?>"><?php echo htmlspecialchars($e['title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Position Title</label>
                        <input type="text" name="position_title" class="form-control" required placeholder="e.g. Secretary General">
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
                        <input type="text" name="candidate_name" class="form-control" required placeholder="John Doe">
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
