<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Please try again.");
        redirect("manage_candidates.php");
    }

    if (isset($_POST["add"])) {
        $name = trim($_POST["name"] ?? "");
        $bio = trim($_POST["bio"] ?? "");
        $position_id = filter_var($_POST["position_id"] ?? null, FILTER_VALIDATE_INT);
        $position = $pdo->prepare("
            SELECT p.id FROM positions p
            LEFT JOIN elections e ON e.id = p.election_id
            WHERE p.id = ? AND (e.id IS NULL OR e.status = 'draft')
        ");
        $position->execute([(int)$position_id]);
        if ($position_id && $name !== "" && strlen($name) <= 100 && $position->fetch()) {
            $pdo->prepare("INSERT INTO candidates (name, bio, position_id) VALUES (?, ?, ?)")
                ->execute([$name, $bio, (int)$position_id]);
            logAction($_SESSION["admin_id"], "Added candidate to position #$position_id");
            setFlash("success", "Candidate added.");
        } else {
            setFlash("danger", "Choose a draft or unassigned position and enter a candidate name of 100 characters or fewer.");
        }
        redirect("manage_candidates.php");
    }

    if (isset($_POST["delete"])) {
        $candidate_id = filter_var($_POST["candidate_id"] ?? null, FILTER_VALIDATE_INT);
        $candidate = $pdo->prepare("
            SELECT c.id FROM candidates c
            JOIN positions p ON p.id = c.position_id
            LEFT JOIN elections e ON e.id = p.election_id
            WHERE c.id = ? AND (e.id IS NULL OR e.status = 'draft')
        ");
        $candidate->execute([(int)$candidate_id]);
        if ($candidate_id && $candidate->fetch()) {
            $pdo->prepare("DELETE FROM candidates WHERE id = ?")->execute([(int)$candidate_id]);
            logAction($_SESSION["admin_id"], "Deleted candidate #$candidate_id");
            setFlash("success", "Candidate deleted.");
        } else {
            setFlash("danger", "Candidates in published elections cannot be deleted.");
        }
        redirect("manage_candidates.php");
    }
}

$positions = $pdo->query("
    SELECT p.id, p.title FROM positions p
    LEFT JOIN elections e ON e.id = p.election_id
    WHERE e.id IS NULL OR e.status = 'draft'
    ORDER BY p.title
")->fetchAll();
$candidates = $pdo->query("
    SELECT c.*, p.title AS pos_title, e.status AS election_status
    FROM candidates c
    JOIN positions p ON c.position_id = p.id
    LEFT JOIN elections e ON e.id = p.election_id
    ORDER BY c.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php">Admin</a>
        <a href="dashboard.php" class="btn btn-light">Back</a>
    </div>
</nav>
<main class="container mt-3">
    <?php $flash = getFlash(); if ($flash): ?>
        <div class="alert alert-<?php echo htmlspecialchars($flash["type"]); ?>"><?php echo htmlspecialchars($flash["message"]); ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-success text-white">Add Candidate</div>
                <div class="card-body">
                    <?php if ($positions): ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                            <div class="mb-3">
                                <label for="candidate_name" class="form-label">Name</label>
                                <input id="candidate_name" type="text" name="name" class="form-control" maxlength="100" required>
                            </div>
                            <div class="mb-3">
                                <label for="candidate_bio" class="form-label">Bio</label>
                                <textarea id="candidate_bio" name="bio" class="form-control"></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="position_id" class="form-label">Position</label>
                                <select id="position_id" name="position_id" class="form-select" required>
                                    <option value="">Select</option>
                                    <?php foreach ($positions as $position): ?>
                                        <option value="<?php echo (int)$position["id"]; ?>"><?php echo htmlspecialchars($position["title"]); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" name="add" value="1" class="btn btn-success w-100">Add</button>
                        </form>
                    <?php else: ?>
                        <p class="text-muted mb-0">No draft or unassigned positions are available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Candidates List</div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead><tr><th>ID</th><th>Name</th><th>Position</th><th>Bio</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($candidates as $candidate): ?>
                            <tr>
                                <td><?php echo (int)$candidate["id"]; ?></td>
                                <td><?php echo htmlspecialchars($candidate["name"]); ?></td>
                                <td><?php echo htmlspecialchars($candidate["pos_title"]); ?></td>
                                <td><?php echo htmlspecialchars(substr($candidate["bio"] ?? "", 0, 50)); ?></td>
                                <td>
                                    <?php if ($candidate["election_status"] === null || $candidate["election_status"] === "draft"): ?>
                                        <form method="POST" onsubmit="return confirm('Delete this candidate?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                            <input type="hidden" name="candidate_id" value="<?php echo (int)$candidate["id"]; ?>">
                                            <button type="submit" name="delete" value="1" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Locked after publication</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
