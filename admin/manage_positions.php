<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Please try again.");
        redirect("manage_positions.php");
    }

    if (isset($_POST["add"])) {
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        if ($title !== "" && strlen($title) <= 100) {
            $pdo->prepare("INSERT INTO positions (title, description) VALUES (?, ?)")
                ->execute([$title, $description]);
            logAction($_SESSION["admin_id"], "Added unassigned position #" . $pdo->lastInsertId());
            setFlash("success", "Position added.");
        } else {
            setFlash("danger", "Enter a position title of 100 characters or fewer.");
        }
        redirect("manage_positions.php");
    }

    if (isset($_POST["delete"])) {
        $position_id = filter_var($_POST["position_id"] ?? null, FILTER_VALIDATE_INT);
        $position = $pdo->prepare("
            SELECT p.id FROM positions p
            WHERE p.id = ? AND p.election_id IS NULL
              AND NOT EXISTS (SELECT 1 FROM candidates c WHERE c.position_id = p.id)
        ");
        $position->execute([(int)$position_id]);
        if ($position_id && $position->fetch()) {
            $pdo->prepare("DELETE FROM positions WHERE id = ? AND election_id IS NULL")->execute([(int)$position_id]);
            logAction($_SESSION["admin_id"], "Deleted unassigned position #$position_id");
            setFlash("success", "Position deleted.");
        } else {
            setFlash("danger", "Only unassigned positions without candidates can be deleted here.");
        }
        redirect("manage_positions.php");
    }
}

$positions = $pdo->query("
    SELECT p.*, e.title AS election_title, e.status AS election_status
    FROM positions p
    LEFT JOIN elections e ON e.id = p.election_id
    ORDER BY p.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
                <div class="card-header bg-primary text-white">Add Unassigned Position</div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                        <div class="mb-3">
                            <label for="position_title" class="form-label">Title</label>
                            <input id="position_title" type="text" name="title" maxlength="100" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="position_description" class="form-label">Description</label>
                            <textarea id="position_description" name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" name="add" value="1" class="btn btn-primary w-100">Add</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Existing Positions</div>
                <div class="card-body table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Title</th><th>Description</th><th>Election</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($positions as $position): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($position["title"]); ?></td>
                                <td><?php echo htmlspecialchars($position["description"] ?? ""); ?></td>
                                <td><?php echo htmlspecialchars($position["election_title"] ?? "Unassigned"); ?></td>
                                <td>
                                    <?php if ($position["election_id"] === null && $position["election_title"] === null): ?>
                                        <form method="POST" onsubmit="return confirm('Delete this position?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                            <input type="hidden" name="position_id" value="<?php echo (int)$position["id"]; ?>">
                                            <button type="submit" name="delete" value="1" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">Managed by election</span>
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
