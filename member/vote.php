<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "../config/functions.php";

if (!isMemberLoggedIn()) {
    redirect("../login.php");
    exit();
}

if (!isElectionActive()) {
    setFlash("danger", "Election is not active");
    redirect("dashboard.php");
    exit();
}

$pos_id = isset($_GET["position"]) ? (int)$_GET["position"] : 0;

// Handle vote submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["vote"])) {
    $candidate_id = (int)$_POST["candidate_id"];
    $position_id = (int)$_POST["position_id"];
    
    if (!hasVoted($_SESSION["member_id"], $position_id)) {
        $stmt = $pdo->prepare("
        addMemberNotification(\['member_id'], 'You successfully voted for position ID ' . \, 'success');
        $stmt->execute([$_SESSION["member_id"], $candidate_id, $position_id]);
        
        // Add notification for admin
        if (function_exists('addNotification')) {
            addNotification($_SESSION["member_name"] . " voted for candidate ID " . $candidate_id, "info");
        }
        
        setFlash("success", "Vote recorded successfully!");
        redirect("dashboard.php");
    } else {
        setFlash("danger", "You have already voted for this position");
        redirect("vote.php");
    }
}

$positions = $pdo->query("SELECT * FROM positions")->fetchAll();
$selectedPosition = null;
$candidates = [];

if ($pos_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM positions WHERE id = ?");
    $stmt->execute([$pos_id]);
    $selectedPosition = $stmt->fetch();
    
    if ($selectedPosition) {
        $stmt = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ?");
        $stmt->execute([$pos_id]);
        $candidates = $stmt->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - Nehiloth</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .card { border-radius: 20px; transition: 0.3s; }
        .card:hover { transform: translateY(-5px); }
        .btn-custom { border-radius: 50px; padding: 10px 25px; }
        .container { padding-bottom: 30px; }
    </style>
</head>
<body>
<nav class="navbar navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="dashboard.php"><i class="fas fa-church"></i> Nehiloth Voting</a>
        <a href="dashboard.php" class="btn btn-light btn-sm">â† Back to Dashboard</a>
    </div>
</nav>

<div class="container mt-4">
    <?php
    $flash = getFlash();
    if ($flash): ?>
        <div class="alert alert-<?php echo $flash["type"]; ?> alert-dismissible fade show" role="alert">
            <?php echo $flash["message"]; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($pos_id == 0): ?>
        <!-- Step 1: Choose a position -->
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4><i class="fas fa-list"></i> Select a Position to Vote</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($positions as $p):
                        $voted = hasVoted($_SESSION["member_id"], $p["id"]);
                        $candidateCount = $pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id = ?");
                        $candidateCount->execute([$p["id"]]);
                        $count = $candidateCount->fetchColumn();
                    ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 <?php echo $voted ? 'bg-light' : ''; ?>">
                                <div class="card-body text-center">
                                    <h5><?php echo htmlspecialchars($p["title"]); ?></h5>
                                    <p class="small text-muted"><?php echo htmlspecialchars($p["description"]); ?></p>
                                    <span class="badge bg-secondary"><?php echo $count; ?> candidate(s)</span>
                                    <?php if ($voted): ?>
                                        <div class="mt-2"><span class="badge bg-success"><i class="fas fa-check"></i> Already Voted</span></div>
                                    <?php else: ?>
                                        <a href="?position=<?php echo $p["id"]; ?>" class="btn btn-primary btn-sm mt-2">Vote Now</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php elseif ($selectedPosition && !hasVoted($_SESSION["member_id"], $pos_id)): ?>
        <!-- Step 2: Show candidates for the selected position -->
        <div class="card shadow">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-vote-yea"></i> Vote for <?php echo htmlspecialchars($selectedPosition["title"]); ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="position_id" value="<?php echo $pos_id; ?>">
                    <div class="row">
                        <?php foreach ($candidates as $c): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-2">
                                    <div class="card-body">
                                        <h5><?php echo htmlspecialchars($c["name"]); ?></h5>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($c["bio"])); ?></p>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="candidate_id" value="<?php echo $c["id"]; ?>" id="cand_<?php echo $c["id"]; ?>" required>
                                            <label class="form-check-label" for="cand_<?php echo $c["id"]; ?>">
                                                <strong>Select this candidate</strong>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <button type="submit" name="vote" class="btn btn-success btn-lg px-5" onclick="return confirm('Are you sure? Your vote cannot be changed later.')">
                            <i class="fas fa-check-circle"></i> Confirm Vote
                        </button>
                        <a href="vote.php" class="btn btn-secondary btn-lg">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Already voted or invalid position -->
        <div class="alert alert-warning text-center">
            <i class="fas fa-exclamation-triangle"></i> You have already voted for this position, or the position does not exist.
            <br><a href="dashboard.php" class="btn btn-primary mt-2">Go to Dashboard</a>
        </div>
    <?php endif; ?>
</div>

<?php include "../footer.php"; ?>
</body>
</html>