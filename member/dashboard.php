<?php require_once "../config/functions.php";
if (!isMemberLoggedIn()) redirect("../login.php");

$member_id = $_SESSION["member_id"];
$member_name = $_SESSION["member_name"];
$positions = $pdo->query("SELECT * FROM positions")->fetchAll();
$active = isElectionActive();

// Get unread notifications
$notifications = getMemberNotifications($member_id, 10);
$unreadCount = count($notifications);

// Mark as read if requested
if (isset($_GET["mark_read"]) && is_numeric($_GET["mark_read"])) {
    markMemberNotificationRead((int)$_GET["mark_read"], $member_id);
    redirect("dashboard.php");
}
if (isset($_GET["mark_all"])) {
    markAllMemberNotificationsRead($member_id);
    redirect("dashboard.php");
}
?>
<!DOCTYPE html>
<html><head><title>Member Dashboard</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><style>body{background:#f0f2f5;}.notification-badge{position:relative;top:-10px;right:5px;background:red;color:white;border-radius:50%;padding:2px 6px;font-size:12px;}</style></head>
<body><nav class="navbar navbar-dark bg-success"><div class="container"><a class="navbar-brand" href="dashboard.php"><i class="fas fa-church"></i> Nehiloth Voting</a>
<div class="ms-auto dropdown">
    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
        <i class="fas fa-bell"></i> <?php if($unreadCount > 0): ?><span class="notification-badge"><?php echo $unreadCount; ?></span><?php endif; ?>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" style="min-width: 250px;">
        <?php if($unreadCount == 0): ?>
            <li><span class="dropdown-item text-muted">No new notifications</span></li>
        <?php else: ?>
            <?php foreach($notifications as $n): ?>
                <li><a class="dropdown-item" href="?mark_read=<?php echo $n["id"]; ?>"><small><i class="fas fa-<?php echo $n["type"]=="success"?"check-circle":($n["type"]=="danger"?"exclamation-triangle":"info-circle"); ?>"></i> <?php echo htmlspecialchars($n["message"]); ?></small><br><small class="text-muted"><?php echo date("H:i", strtotime($n["created_at"])); ?></small></a></li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-center" href="?mark_all=1">Mark all as read</a></li>
        <?php endif; ?>
    </ul>
    <span class="text-white ms-3">Welcome, <?php echo $member_name; ?></span>
    <a href="../logout.php" class="btn btn-danger btn-sm ms-2">Logout</a>
</div></div></nav>
<div class="container mt-4"><div class="row"><div class="col-md-3"><div class="list-group"><a href="dashboard.php" class="list-group-item active">Dashboard</a><a href="vote.php" class="list-group-item">Cast Vote</a><a href="results.php" class="list-group-item">Results</a><a href="members_list.php" class="list-group-item">Members</a></div></div>
<div class="col-md-9"><div class="card"><div class="card-header bg-info text-white">Welcome</div><div class="card-body"><div class="alert alert-<?php echo $active?"success":"danger";?>">Election Status: <strong><?php echo $active?"ACTIVE - You can vote":"INACTIVE - Voting closed";?></strong><?php if($active):?><br><a href="vote.php" class="btn btn-primary mt-2">Cast Your Vote</a><?php endif;?></div>
<h5>Available Positions</h5><div class="row"><?php foreach($positions as $p): $voted=hasVoted($_SESSION["member_id"],$p["id"]); $cnt=$pdo->prepare("SELECT COUNT(*) FROM candidates WHERE position_id=?"); $cnt->execute([$p["id"]]);?><div class="col-md-6 mb-3"><div class="card"><div class="card-body"><h5><?php echo htmlspecialchars($p["title"]);?></h5><p><?php echo htmlspecialchars($p["description"]);?></p><small><?php echo $cnt->fetchColumn();?> candidates</small><br><?php if($voted):?><span class="badge bg-success">Voted</span><?php elseif($active):?><a href="vote.php?position=<?php echo $p["id"];?>" class="btn btn-sm btn-primary mt-2">Vote</a><?php endif;?></div></div></div><?php endforeach;?></div></div></div></div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body></html>