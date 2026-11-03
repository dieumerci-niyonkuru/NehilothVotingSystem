<?php
require_once "../config/functions.php";
if (!isMemberLoggedIn()) {
    redirect("../login.php");
}

$elections = $pdo->query("
    SELECT id, title, status, results_published
    FROM elections
    WHERE results_published = 1 AND status NOT IN ('draft', 'cancelled')
    ORDER BY end_time DESC
")->fetchAll();
$election = null;
foreach ($elections as $item) {
    $item["status"] = getElectionStatus((int)$item["id"]);
    if ($item["status"] === "closed") {
        $election = $item;
        break;
    }
}
$positions = [];
if ($election) {
    $position_stmt = $pdo->prepare("SELECT id, title FROM positions WHERE election_id = ? ORDER BY id");
    $position_stmt->execute([(int)$election["id"]]);
    $positions = $position_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Election Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include "../navbar.php"; ?>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Official Election Results</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Member Dashboard</a>
    </div>
    <?php if (!$election): ?>
        <div class="alert alert-info">No verified election results have been published yet.</div>
    <?php else: ?>
        <h3><?php echo htmlspecialchars($election["title"]); ?></h3>
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
            <section class="card my-4">
                <div class="card-header"><strong><?php echo htmlspecialchars($position["title"]); ?></strong></div>
                <div class="card-body">
                    <p class="text-muted"><?php echo $total; ?> ballots recorded</p>
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
</main>
<?php include "../footer.php"; ?>
</body>
</html>
