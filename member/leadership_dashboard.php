<?php
require_once "../config/functions.php";
if (!isLeadershipLoggedIn()) {
    redirect("dashboard.php");
}

$roleLabels = [
    "president" => "President",
    "secretary" => "Secretary",
    "schedule_coordinator" => "Schedule Coordinator",
    "election_manager" => "Election Manager"
];
$areas = [
    "songs" => ["title" => "Song library", "url" => "../admin/manage_songs.php", "icon" => "fa-music"],
    "schedule" => ["title" => "Rehearsals & schedule", "url" => "../admin/manage_schedule.php", "icon" => "fa-calendar-alt"],
    "announcements" => ["title" => "Announcements", "url" => "../admin/manage_announcements.php", "icon" => "fa-bullhorn"],
    "leadership" => ["title" => "Leadership directory", "url" => "../admin/manage_leadership.php", "icon" => "fa-users-cog"],
    "elections" => ["title" => "Election management", "url" => "../admin/manage_elections.php", "icon" => "fa-vote-yea"]
];
$activity = $pdo->prepare("SELECT action, created_at FROM leadership_activity WHERE member_id = ? ORDER BY created_at DESC LIMIT 10");
$activity->execute([(int)$_SESSION["member_id"]]);
$recentActivity = $activity->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leadership Dashboard - Nehiloth Worship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f5f6f8;min-height:100vh;padding-bottom:70px}.brand-hero{background:linear-gradient(135deg,#18294f,#304b7a);color:white}.role-card{border:0;border-radius:1rem;transition:transform .2s}.role-card:hover{transform:translateY(-3px)}</style>
</head>
<body>
<?php include "../navbar.php"; ?>
<main class="container py-4">
    <section class="brand-hero rounded-4 p-4 p-md-5 mb-4 shadow-sm">
        <span class="badge bg-warning text-dark mb-2">LEADERSHIP PORTAL</span>
        <h1 class="h2 fw-bold">Welcome, <?php echo htmlspecialchars($_SESSION["member_name"] ?? "Leader"); ?></h1>
        <p class="mb-0">Assigned role: <strong><?php echo htmlspecialchars($roleLabels[getMemberRole()] ?? ucfirst(str_replace("_", " ", getMemberRole()))); ?></strong>. Your tools are limited to your assigned responsibilities.</p>
    </section>

    <?php if (getMemberRole() === "admin"): ?>
        <div class="alert alert-warning">Your account has full administrator access. <a href="../admin/dashboard.php" class="alert-link">Open the administrator dashboard</a>.</div>
    <?php endif; ?>

    <div class="row g-3">
        <?php foreach ($areas as $area => $card): if (!canManageArea($area)) continue; ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <a href="<?php echo htmlspecialchars($card["url"]); ?>" class="card role-card shadow-sm h-100 text-decoration-none text-dark">
                    <div class="card-body p-4">
                        <i class="fas <?php echo htmlspecialchars($card["icon"]); ?> fa-2x text-warning mb-3"></i>
                        <h2 class="h5 fw-bold"><?php echo htmlspecialchars($card["title"]); ?></h2>
                        <span class="small text-primary">Open management tools <i class="fas fa-arrow-right ms-1"></i></span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="../schedule.php" class="card role-card shadow-sm h-100 text-decoration-none text-dark">
                <div class="card-body p-4"><i class="fas fa-calendar-check fa-2x text-primary mb-3"></i><h2 class="h5 fw-bold">View public schedule</h2><span class="small text-primary">Rehearsal plans and song setlists</span></div>
            </a>
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <a href="../songs.php" class="card role-card shadow-sm h-100 text-decoration-none text-dark">
                <div class="card-body p-4"><i class="fas fa-headphones fa-2x text-success mb-3"></i><h2 class="h5 fw-bold">Listen to the library</h2><span class="small text-primary">Approved recordings and song links</span></div>
            </a>
        </div>
    </div>

    <section class="card border-0 shadow-sm rounded-4 mt-4">
        <div class="card-body p-4">
            <h2 class="h5 fw-bold">Recent leadership activity</h2>
            <?php if (!$recentActivity): ?>
                <p class="text-muted mb-0">No activity recorded yet.</p>
            <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($recentActivity as $item): ?>
                        <li class="list-group-item px-0 d-flex justify-content-between gap-3">
                            <span><?php echo htmlspecialchars($item["action"]); ?></span>
                            <time class="small text-muted text-nowrap"><?php echo htmlspecialchars(date("M j, Y H:i", strtotime($item["created_at"]))); ?></time>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </section>
</main>
<?php include "../mobile_nav.php"; ?>
<?php include "../footer.php"; ?>
</body>
</html>
