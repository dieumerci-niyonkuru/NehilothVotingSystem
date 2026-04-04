<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");
$report = generateVoteReport();
if (isset($_GET["export"])) {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=voting_report_" . date("Y-m-d") . ".csv");
    $output = fopen("php://output", "w");
    fputcsv($output, ["Position", "Candidate", "Votes", "Voter Name", "Voter Email", "Vote Time"]);
    foreach ($report as $pos) {
        foreach ($pos["candidates"] as $c) {
            if (empty($c["voters"])) {
                fputcsv($output, [$pos["title"], $c["name"], $c["votes"], "No votes", "", ""]);
            } else {
                foreach ($c["voters"] as $voter) {
                    fputcsv($output, [$pos["title"], $c["name"], $c["votes"], $voter["member_name"], $voter["email"], $voter["created_at"]]);
                }
            }
        }
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html><head><title>Voting Reports</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><style>body{background:#f0f2f5;}.card{border-radius:15px;margin-bottom:20px;}</style></head>
<body><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php"><i class="fas fa-church"></i> Nehiloth Admin</a><div class="ms-auto"><a href="dashboard.php" class="btn btn-light">Back</a> <a href="?export=1" class="btn btn-success"><i class="fas fa-download"></i> Export CSV</a></div></div></nav>
<div class="container mt-4"><h2 class="mb-4"><i class="fas fa-chart-pie"></i> Detailed Voting Report</h2><?php foreach($report as $pos):?><div class="card"><div class="card-header bg-primary text-white"><h4><?php echo htmlspecialchars($pos["title"]);?></h4></div><div class="card-body"><?php if($pos["winner"]):?><div class="alert alert-success"><i class="fas fa-trophy"></i> <strong>Winner:</strong> <?php echo htmlspecialchars($pos["winner"]["name"]);?> (<?php echo $pos["winner"]["votes"];?> votes)</div><?php endif;?>
<table class="table table-bordered"><thead><tr><th>Candidate</th><th>Votes</th><th>Voter Name</th><th>Voter Email</th><th>Voted At</th></tr></thead><tbody><?php foreach($pos["candidates"] as $c): if(empty($c["voters"])):?><tr><td rowspan="1"><?php echo htmlspecialchars($c["name"]);?></td><td rowspan="1">0</td><td colspan="3">No votes yet</td></tr><?php else: $first=true; foreach($c["voters"] as $v):?><tr><?php if($first):?><td rowspan="<?php echo count($c["voters"]);?>"><?php echo htmlspecialchars($c["name"]);?></td><td rowspan="<?php echo count($c["voters"]);?>"><?php echo $c["votes"];?></td><?php $first=false; endif;?><td><?php echo htmlspecialchars($v["member_name"]);?></td><td><?php echo htmlspecialchars($v["email"]);?></td><td><?php echo date("Y-m-d H:i:s", strtotime($v["created_at"]));?></td></tr><?php endforeach; endif; endforeach;?></tbody></table></div></div><?php endforeach;?></div></body></html>