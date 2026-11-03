<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle Add Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_schedule') {
    $title = sanitize($_POST['activity_title']);
    $day = sanitize($_POST['day_of_week']);
    $start = sanitize($_POST['start_time']);
    $end = sanitize($_POST['end_time']);
    $loc = sanitize($_POST['location']);
    $leader = sanitize($_POST['responsible_leader']);
    $desc = sanitize($_POST['description']);
    $date_sched = sanitize($_POST['date_scheduled']);

    if (!empty($title) && !empty($date_sched)) {
        $stmt = $pdo->prepare("INSERT INTO weekly_schedules (activity_title, day_of_week, start_time, end_time, location, responsible_leader, description, date_scheduled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $day, $start, $end, $loc, $leader, $desc, $date_sched]);
        setFlash("success", "New weekly worship schedule entry added!");
    }
    redirect("manage_schedule.php");
}

// Handle Assign Duty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_duty') {
    $sched_id = (int)$_POST['schedule_id'];
    $mem_id = (int)$_POST['member_id'];
    $role = sanitize($_POST['role_assigned']);

    if ($sched_id > 0 && $mem_id > 0 && !empty($role)) {
        $stmt = $pdo->prepare("INSERT INTO schedule_assignments (schedule_id, member_id, role_assigned) VALUES (?, ?, ?)");
        $stmt->execute([$sched_id, $mem_id, $role]);
        addMemberNotification($mem_id, "You have been assigned duty: $role in worship activity", "info");
        setFlash("success", "Member duty assigned!");
    }
    redirect("manage_schedule.php");
}

// Delete Schedule
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM weekly_schedules WHERE id = ?")->execute([$id]);
    setFlash("danger", "Schedule entry deleted.");
    redirect("manage_schedule.php");
}

$schedules = $pdo->query("SELECT s.*, GROUP_CONCAT(CONCAT(m.name, ' (', sa.role_assigned, ')') SEPARATOR ', ') as assigned_list FROM weekly_schedules s LEFT JOIN schedule_assignments sa ON s.id = sa.schedule_id LEFT JOIN members m ON sa.member_id = m.id GROUP BY s.id ORDER BY s.date_scheduled ASC, s.start_time ASC")->fetchAll();
$members = $pdo->query("SELECT id, name, group_dept FROM members WHERE status = 'approved'")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Worship Schedule - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-calendar-alt text-success me-2"></i>Weekly Worship Schedule Manager</h2>
            <p class="text-muted small">Create schedules, rehearsals, prayer meetings, and assign member duties</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
            <button class="btn btn-success rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#addSchedModal">
                <i class="fas fa-plus me-1"></i> Add Schedule
            </button>
            <button class="btn btn-info text-dark rounded-pill px-3 ms-2 fw-bold" data-bs-toggle="modal" data-bs-target="#assignDutyModal">
                <i class="fas fa-user-tag me-1"></i> Assign Duty
            </button>
        </div>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show"><?php echo $f['message']; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Date & Day</th>
                        <th>Activity Title</th>
                        <th>Time & Location</th>
                        <th>Responsible Leader</th>
                        <th>Assigned Member Duties</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $s): ?>
                        <tr>
                            <td>
                                <strong class="text-dark"><?php echo $s['date_scheduled']; ?></strong><br>
                                <span class="badge bg-secondary"><?php echo $s['day_of_week']; ?></span>
                            </td>
                            <td>
                                <strong class="text-dark"><?php echo htmlspecialchars($s['activity_title']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($s['description']); ?></small>
                            </td>
                            <td>
                                <small class="d-block"><i class="fas fa-clock text-primary me-1"></i><?php echo date('H:i', strtotime($s['start_time'])) . ' - ' . date('H:i', strtotime($s['end_time'])); ?></small>
                                <small class="d-block text-muted"><i class="fas fa-map-marker-alt text-danger me-1"></i><?php echo htmlspecialchars($s['location']); ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['responsible_leader']); ?></span></td>
                            <td><small class="text-primary fw-bold"><?php echo !empty($s['assigned_list']) ? htmlspecialchars($s['assigned_list']) : 'None assigned'; ?></small></td>
                            <td>
                                <a href="manage_schedule.php?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this schedule entry?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Add Schedule -->
<div class="modal fade" id="addSchedModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">New Worship Activity</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_schedule">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Activity Title</label>
                        <input type="text" name="activity_title" class="form-control" required placeholder="Sunday Worship Service">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Day of Week</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="Sunday">Sunday</option>
                                <option value="Monday">Monday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Friday">Friday</option>
                            </select>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Scheduled Date</label>
                            <input type="date" name="date_scheduled" class="form-control" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-semibold">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location</label>
                        <input type="text" name="location" class="form-control" value="RP Tumba College Chapel">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Responsible Leader</label>
                        <input type="text" name="responsible_leader" class="form-control" placeholder="Pastor David Nshimiyimana">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success rounded-pill fw-bold">Save Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Assign Duty -->
<div class="modal fade" id="assignDutyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title fw-bold">Assign Duty to Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_duty">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Worship Activity</label>
                        <select name="schedule_id" class="form-select" required>
                            <?php foreach ($schedules as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['date_scheduled'] . ' - ' . $s['activity_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Member</label>
                        <select name="member_id" class="form-select" required>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['name'] . ' (' . $m['group_dept'] . ')'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role / Assigned Responsibility</label>
                        <input type="text" name="role_assigned" class="form-control" required placeholder="e.g. Keynote Singer / Sound Technician">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-info text-dark rounded-pill fw-bold">Assign Duty</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
