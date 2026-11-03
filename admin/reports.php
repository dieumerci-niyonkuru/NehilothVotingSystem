<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) {
    redirect("login.php");
}

$elections = $pdo->query("SELECT id, title FROM elections ORDER BY created_at DESC")->fetchAll();
$election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
if (!$election_id && $elections) {
    $election_id = (int)$elections[0]["id"];
}
$election_stmt = $pdo->prepare("SELECT id, title, status, results_published FROM elections WHERE id = ?");
$election_stmt->execute([(int)$election_id]);
$election = $election_stmt->fetch();
if ($election) {
    $election["status"] = getElectionStatus((int)$election["id"]);
}

$report = [];
if ($election) {
    $position_stmt = $pdo->prepare("SELECT id, title FROM positions WHERE election_id = ? ORDER BY id");
    $position_stmt->execute([(int)$election["id"]]);
    foreach ($position_stmt->fetchAll() as $position) {
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
        $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM ballots WHERE election_id = ? AND position_id = ?");
        $total_stmt->execute([(int)$election["id"], (int)$position["id"]]);
        $report[] = [
            "title" => $position["title"],
            "total" => (int)$total_stmt->fetchColumn(),
            "candidates" => $candidate_stmt->fetchAll()
        ];
    }
}

if (isset($_GET["export"]) && $election) {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=election_report_" . (int)$election["id"] . ".csv");
    $output = fopen("php://output", "w");
    fputcsv($output, ["Election", "Position", "Candidate", "Votes", "Percentage"]);
    foreach ($report as $position) {
        foreach ($position["candidates"] as $candidate) {
            $votes = (int)$candidate["votes"];
            $percentage = $position["total"] > 0 ? round($votes * 100 / $position["total"], 1) : 0;
            fputcsv($output, [$election["title"], $position["title"], $candidate["name"], $votes, $percentage]);
        }
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Election Reports</h2>
        <a href="dashboard.php" class="btn btn-outline-secondary">Admin Dashboard</a>
    </div>
    <?php if (!$election): ?>
        <div class="alert alert-info">No elections are available.</div>
    <?php else: ?>
        <form method="GET" class="d-flex gap-2 mb-4">
            <select name="election_id" class="form-select" aria-label="Select election">
                <?php foreach ($elections as $item): ?>
                    <option value="<?php echo (int)$item["id"]; ?>" <?php echo (int)$item["id"] === (int)$election_id ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($item["title"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">View</button>
            <button type="submit" name="export" value="1" class="btn btn-success">Export CSV</button>
        </form>
        <h3><?php echo htmlspecialchars($election["title"]); ?></h3>
        <p>Status: <?php echo htmlspecialchars(strtoupper($election["status"])); ?>.
            Results <?php echo !empty($election["results_published"]) ? "published" : "not published"; ?>.</p>
        <p class="text-muted">Reports contain aggregate candidate totals only; individual voter identities and selections are not included.</p>
        <?php foreach ($report as $position): ?>
            <section class="card mb-4">
                <div class="card-header d-flex justify-content-between">
                    <strong><?php echo htmlspecialchars($position["title"]); ?></strong>
                    <span><?php echo $position["total"]; ?> ballots</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Candidate</th><th>Votes</th><th>Percentage</th></tr></thead>
                            <tbody>
                            <?php foreach ($position["candidates"] as $candidate): ?>
                                <?php
                                $votes = (int)$candidate["votes"];
                                $percentage = $position["total"] > 0 ? round($votes * 100 / $position["total"], 1) : 0;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($candidate["name"]); ?></td>
                                    <td><?php echo $votes; ?></td>
                                    <td><?php echo $percentage; ?>%</td>
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
</body>
</html>
