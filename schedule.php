<?php
require_once "config/functions.php";

$day_filter = isset($_GET['day']) ? sanitize($_GET['day']) : null;

$sql = "SELECT s.*, GROUP_CONCAT(CONCAT(m.name, ' (', sa.role_assigned, ')') SEPARATOR ', ') as assigned_list
        FROM weekly_schedules s
        LEFT JOIN schedule_assignments sa ON s.id = sa.schedule_id
        LEFT JOIN members m ON sa.member_id = m.id
        WHERE 1=1";

$params = [];
if ($day_filter) {
    $sql .= " AND s.day_of_week = ?";
    $params[] = $day_filter;
}

$sql .= " GROUP BY s.id ORDER BY s.date_scheduled ASC, s.start_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$schedules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Worship Schedule - NELILOTH WORSHIP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.hero-banner{background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%);color:white;padding:35px 0;}.sched-card{border-radius:18px;border:none;transition:transform 0.2s;}.sched-card:hover{transform:translateY(-3px);}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-calendar-alt me-2"></i>Weekly Worship Schedule</h1>
        <p class="lead mb-0">Assigned worship duties, rehearsals, prayer meetings, and Sunday service details</p>
    </div>
</div>

<div class="container mb-5">
    <!-- Day Filter Pills -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
        <a href="schedule.php" class="btn btn-outline-dark rounded-pill px-3 <?php echo empty($day_filter)?'active':''; ?>">All Days</a>
        <a href="schedule.php?day=Monday" class="btn btn-outline-primary rounded-pill px-3 <?php echo $day_filter=='Monday'?'active':''; ?>">Monday</a>
        <a href="schedule.php?day=Wednesday" class="btn btn-outline-info rounded-pill px-3 <?php echo $day_filter=='Wednesday'?'active':''; ?>">Wednesday</a>
        <a href="schedule.php?day=Friday" class="btn btn-outline-warning rounded-pill px-3 <?php echo $day_filter=='Friday'?'active':''; ?>">Friday</a>
        <a href="schedule.php?day=Sunday" class="btn btn-outline-success rounded-pill px-3 <?php echo $day_filter=='Sunday'?'active':''; ?>">Sunday</a>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if (empty($schedules)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No worship schedule entries found for this filter.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($schedules as $s): ?>
                <div class="col-md-8">
                    <div class="card sched-card shadow-sm p-4 bg-white border-start border-4 border-success">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-calendar-day me-1"></i><?php echo htmlspecialchars($s['day_of_week']); ?> &bull; <?php echo date('M d, Y', strtotime($s['date_scheduled'])); ?></span>
                            <span class="badge bg-light text-dark fw-bold border"><i class="fas fa-clock me-1 text-primary"></i><?php echo date('H:i', strtotime($s['start_time'])) . ' - ' . date('H:i', strtotime($s['end_time'])); ?></span>
                        </div>

                        <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($s['activity_title']); ?></h4>
                        <p class="text-secondary mb-3"><i class="fas fa-map-marker-alt text-danger me-2"></i>Location: <strong><?php echo htmlspecialchars($s['location']); ?></strong></p>

                        <?php if (!empty($s['description'])): ?>
                            <p class="small bg-light p-3 rounded-3 text-muted mb-3"><?php echo htmlspecialchars($s['description']); ?></p>
                        <?php endif; ?>

                        <div class="row pt-2 border-top">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted d-block"><i class="fas fa-user-tie me-1 text-primary"></i>Responsible Leader:</small>
                                <strong class="text-dark"><?php echo htmlspecialchars($s['responsible_leader']); ?></strong>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted d-block"><i class="fas fa-users me-1 text-success"></i>Assigned Duties:</small>
                                <span class="fw-bold text-primary"><?php echo !empty($s['assigned_list']) ? htmlspecialchars($s['assigned_list']) : 'General Congregation / Worship Team'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
