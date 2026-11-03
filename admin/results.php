<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

$elections = $pdo->query("SELECT id, title, status, results_published FROM elections ORDER BY created_at DESC")->fetchAll();
$election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
if (!$election_id && $elections) {
    $election_id = (int)$elections[0]["id"];
}
$election_stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ?");
$election_stmt->execute([(int)$election_id]);
$election = $election_stmt->fetch();
if ($election) {
    $election["status"] = getElectionStatus((int)$election["id"]);
}
$positions = [];
if ($election) {
    $position_stmt = $pdo->prepare("SELECT id, title, description FROM positions WHERE election_id = ? ORDER BY id");
    $position_stmt->execute([(int)$election["id"]]);
    $positions = $position_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Election Results Review</h2>
        <a href="manage_elections.php" class="btn btn-outline-secondary">Election Management</a>
    </div>
    <?php if (!$election): ?>
        <div class="alert alert-info">No elections have been created.</div>
    <?php else: ?>
        <form method="GET" class="mb-4">
            <label for="election_id" class="form-label">Election</label>
            <div class="d-flex gap-2">
                <select id="election_id" name="election_id" class="form-select">
                    <?php foreach ($elections as $item): ?>
                        <option value="<?php echo (int)$item["id"]; ?>" <?php echo (int)$item["id"] === (int)$election_id ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($item["title"] . " (" . strtoupper($item["status"]) . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="submit">View</button>
            </div>
        </form>
        <h3><?php echo htmlspecialchars($election["title"]); ?></h3>
        <p>Status: <strong><?php echo htmlspecialchars(strtoupper($election["status"])); ?></strong>
            · Results <?php echo !empty($election["results_published"]) ? "published" : "not published"; ?></p>
        <?php if (!$positions): ?>
            <div class="alert alert-info">No positions are configured for this election.</div>
        <?php endif; ?>
        <?php foreach ($positions as $position): ?>
            <?php
            $candidate_stmt = $pdo->prepare("
                SELECT c.id, c.name, COUNT(b.id) AS votes
                FROM candidates c
                LEFT JOIN ballots b ON b.candidate_id = c.id
                    AND b.election_id = ? AND b.position_id = c.position_id
                WHERE c.position_id = ?
                GROUP BY c.id, c.name
                ORDER BY votes DESC, c.name ASC
            ");
            $candidate_stmt->execute([(int)$election["id"], (int)$position["id"]]);
            $candidates = $candidate_stmt->fetchAll();
            $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM ballots WHERE election_id = ? AND position_id = ?");
            $total_stmt->execute([(int)$election["id"], (int)$position["id"]]);
            $total = (int)$total_stmt->fetchColumn();
            ?>
            <section class="card mb-4">
                <div class="card-header"><strong><?php echo htmlspecialchars($position["title"]); ?></strong></div>
                <div class="card-body">
                    <p class="text-muted"><?php echo $total; ?> ballots for this position</p>
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Candidate</th><th>Votes</th><th>Share</th></tr></thead>
                            <tbody>
                            <?php foreach ($candidates as $candidate): ?>
                                <?php $votes = (int)$candidate["votes"]; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate["name"]); ?></td>
                                    <td><?php echo $votes; ?></td>
                                    <td><?php echo $total ? round($votes * 100 / $total, 1) : 0; ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
