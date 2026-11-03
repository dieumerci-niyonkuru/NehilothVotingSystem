<?php
require_once "../config/functions.php";
if (!canManageArea("schedule")) redirect(isMemberLoggedIn() ? "../member/dashboard.php" : "login.php");

// Handle Add Schedule
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_schedule') {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_schedule.php");
    }
    $title = trim($_POST['activity_title'] ?? "");
    $start = trim($_POST['start_time'] ?? "");
    $end = trim($_POST['end_time'] ?? "");
    $loc = trim($_POST['location'] ?? "");
    $leader = trim($_POST['responsible_leader'] ?? "");
    $desc = trim($_POST['description'] ?? "");
    $date_sched = trim($_POST['date_scheduled'] ?? "");
    $parsedDate = DateTimeImmutable::createFromFormat("!Y-m-d", $date_sched);
    $parsedStart = DateTimeImmutable::createFromFormat("!H:i", $start);
    $parsedEnd = DateTimeImmutable::createFromFormat("!H:i", $end);

    if ($title !== "" && strlen($title) <= 255 && $parsedDate && $parsedDate->format("Y-m-d") === $date_sched &&
        $parsedStart && $parsedStart->format("H:i") === $start && $parsedEnd && $parsedEnd->format("H:i") === $end &&
        $parsedStart < $parsedEnd && strlen($loc) <= 255 && strlen($leader) <= 100 && strlen($desc) <= 5000) {
        $stmt = $pdo->prepare("INSERT INTO weekly_schedules (activity_title, day_of_week, start_time, end_time, location, responsible_leader, description, date_scheduled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $parsedDate->format("l"), $start, $end, $loc, $leader, $desc, $date_sched]);
        $scheduleId = (int)$pdo->lastInsertId();
        $songIds = $_POST["song_ids"] ?? [];
        if (is_array($songIds)) {
            $availableSongIds = array_map("intval", array_column($pdo->query("SELECT id FROM song_requests WHERE status IN ('approved','scheduled')")->fetchAll(), "id"));
            $validSongIds = array_values(array_unique(array_filter(array_map("intval", $songIds), function ($songId) use ($availableSongIds) {
                return in_array($songId, $availableSongIds, true);
            })));
            $setSong = $pdo->prepare("INSERT INTO rehearsal_songs (schedule_id, song_id, sequence_no) VALUES (?, ?, ?)");
            foreach ($validSongIds as $index => $songId) {
                $setSong->execute([$scheduleId, $songId, $index + 1]);
            }
        }
        setFlash("success", "New weekly worship schedule entry added!");
    } else {
        setFlash("danger", "Check the schedule title, date, time range, and field limits.");
    }
    redirect("manage_schedule.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_schedule") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_schedule.php");
    }
    $scheduleId = filter_var($_POST["schedule_id"] ?? null, FILTER_VALIDATE_INT);
    $title = trim($_POST["activity_title"] ?? "");
    $date = trim($_POST["date_scheduled"] ?? "");
    $start = trim($_POST["start_time"] ?? "");
    $end = trim($_POST["end_time"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $leader = trim($_POST["responsible_leader"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $parsedDate = DateTimeImmutable::createFromFormat("!Y-m-d", $date);
    $parsedStart = DateTimeImmutable::createFromFormat("!H:i", $start);
    $parsedEnd = DateTimeImmutable::createFromFormat("!H:i", $end);
    if (!$scheduleId || $title === "" || strlen($title) > 255 || !$parsedDate || $parsedDate->format("Y-m-d") !== $date ||
        !$parsedStart || $parsedStart->format("H:i") !== $start || !$parsedEnd || $parsedEnd->format("H:i") !== $end ||
        $parsedStart >= $parsedEnd || strlen($location) > 255 || strlen($leader) > 100 || strlen($description) > 5000) {
        setFlash("danger", "Check the schedule title, date, time range, and field limits.");
        redirect("manage_schedule.php");
    }
    $update = $pdo->prepare("UPDATE weekly_schedules SET activity_title = ?, day_of_week = ?, start_time = ?, end_time = ?, location = ?, responsible_leader = ?, description = ?, date_scheduled = ? WHERE id = ?");
    $update->execute([$title, $parsedDate->format("l"), $start, $end, $location, $leader, $description, $date, $scheduleId]);
    logAction($_SESSION["admin_id"] ?? 0, "Updated worship schedule #$scheduleId");
    setFlash("success", "Worship schedule updated.");
    redirect("manage_schedule.php");
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "save_rehearsal_songs") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_schedule.php");
    }
    $scheduleId = filter_var($_POST["schedule_id"] ?? null, FILTER_VALIDATE_INT);
    $songIds = $_POST["song_ids"] ?? [];
    if (!$scheduleId || !is_array($songIds)) {
        setFlash("danger", "Choose a schedule and valid songs.");
        redirect("manage_schedule.php");
    }

    $availableSongs = $pdo->query("SELECT id FROM song_requests WHERE status IN ('approved','scheduled')")->fetchAll(PDO::FETCH_COLUMN);
    $availableIds = array_map("intval", $availableSongs);
    $selectedIds = array_values(array_unique(array_filter(array_map("intval", $songIds), function ($songId) use ($availableIds) {
        return in_array($songId, $availableIds, true);
    })));

    try {
        $pdo->beginTransaction();
        $check = $pdo->prepare("SELECT id FROM weekly_schedules WHERE id = ? FOR UPDATE");
        $check->execute([$scheduleId]);
        if (!$check->fetch()) {
            $pdo->rollBack();
            setFlash("danger", "Schedule entry was not found.");
            redirect("manage_schedule.php");
        }
        $pdo->prepare("DELETE FROM rehearsal_songs WHERE schedule_id = ?")->execute([$scheduleId]);
        $insert = $pdo->prepare("INSERT INTO rehearsal_songs (schedule_id, song_id, sequence_no) VALUES (?, ?, ?)");
        foreach ($selectedIds as $index => $songId) {
            $insert->execute([$scheduleId, $songId, $index + 1]);
        }
        $pdo->commit();
        logAction($_SESSION["admin_id"] ?? 0, "Updated rehearsal setlist for schedule $scheduleId");
        setFlash("success", "Rehearsal song list updated.");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Unable to update rehearsal setlist: " . $e->getMessage());
        setFlash("danger", "The song list could not be saved.");
    }
    redirect("manage_schedule.php");
}

// Handle Assign Duty
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'assign_duty') {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_schedule.php");
    }
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_schedule") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_schedule.php");
    }
    $id = (int)($_POST["schedule_id"] ?? 0);
    $pdo->prepare("DELETE FROM weekly_schedules WHERE id = ?")->execute([$id]);
    setFlash("danger", "Schedule entry deleted.");
    redirect("manage_schedule.php");
}

$schedules = $pdo->query("SELECT s.*, GROUP_CONCAT(CONCAT(m.name, ' (', sa.role_assigned, ')') SEPARATOR ', ') as assigned_list FROM weekly_schedules s LEFT JOIN schedule_assignments sa ON s.id = sa.schedule_id LEFT JOIN members m ON sa.member_id = m.id GROUP BY s.id ORDER BY s.date_scheduled ASC, s.start_time ASC")->fetchAll();
$members = $pdo->query("SELECT id, name, group_dept FROM members WHERE status = 'approved'")->fetchAll();
$availableSongs = $pdo->query("SELECT id, song_title, artist FROM song_requests WHERE status IN ('approved','scheduled') ORDER BY song_title ASC")->fetchAll();
$setlistStmt = $pdo->query("SELECT rs.schedule_id, rs.song_id, rs.sequence_no, sr.song_title FROM rehearsal_songs rs JOIN song_requests sr ON sr.id = rs.song_id ORDER BY rs.schedule_id, rs.sequence_no");
$setlists = [];
foreach ($setlistStmt->fetchAll() as $setlistSong) {
    $setlists[(int)$setlistSong["schedule_id"]][] = $setlistSong;
}
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
            <a href="<?php echo isMemberLoggedIn() ? '../member/leadership_dashboard.php' : 'dashboard.php'; ?>" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
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
                        <th>Rehearsal Setlist</th>
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
                            <td>
                                <?php if (!empty($setlists[(int)$s["id"]])): ?>
                                    <ol class="small ps-3 mb-2"><?php foreach ($setlists[(int)$s["id"]] as $setlistSong): ?><li><?php echo htmlspecialchars($setlistSong["song_title"]); ?></li><?php endforeach; ?></ol>
                                <?php else: ?><span class="small text-muted">No songs selected</span><?php endif; ?>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="save_rehearsal_songs">
                                    <input type="hidden" name="schedule_id" value="<?php echo (int)$s["id"]; ?>">
                                    <select class="form-select form-select-sm mb-2" name="song_ids[]" multiple size="3" aria-label="Setlist for <?php echo htmlspecialchars($s["activity_title"]); ?>">
                                        <?php foreach ($availableSongs as $choice): ?>
                                            <option value="<?php echo (int)$choice["id"]; ?>" <?php echo in_array((int)$choice["id"], array_map("intval", array_column($setlists[(int)$s["id"]] ?? [], "song_id")), true) ? "selected" : ""; ?>>
                                                <?php echo htmlspecialchars($choice["song_title"] . " — " . $choice["artist"]); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Save songs</button>
                                </form>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($s['responsible_leader']); ?></span></td>
                            <td><small class="text-primary fw-bold"><?php echo !empty($s['assigned_list']) ? htmlspecialchars($s['assigned_list']) : 'None assigned'; ?></small></td>
                            <td>
                                <details>
                                    <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                    <form method="POST" class="bg-light rounded-3 p-3 mt-2" style="min-width:min(86vw,440px)">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="update_schedule">
                                        <input type="hidden" name="schedule_id" value="<?php echo (int)$s["id"]; ?>">
                                        <label class="form-label">Activity title</label><input name="activity_title" class="form-control mb-2" maxlength="255" value="<?php echo htmlspecialchars($s["activity_title"]); ?>" required>
                                        <label class="form-label">Date</label><input type="date" name="date_scheduled" class="form-control mb-2" value="<?php echo htmlspecialchars($s["date_scheduled"]); ?>" required>
                                        <div class="row"><div class="col-6"><label class="form-label">Start</label><input type="time" name="start_time" class="form-control mb-2" value="<?php echo htmlspecialchars(substr($s["start_time"], 0, 5)); ?>" required></div><div class="col-6"><label class="form-label">End</label><input type="time" name="end_time" class="form-control mb-2" value="<?php echo htmlspecialchars(substr($s["end_time"], 0, 5)); ?>" required></div></div>
                                        <label class="form-label">Location</label><input name="location" class="form-control mb-2" maxlength="255" value="<?php echo htmlspecialchars($s["location"]); ?>">
                                        <label class="form-label">Responsible leader</label><input name="responsible_leader" class="form-control mb-2" maxlength="100" value="<?php echo htmlspecialchars($s["responsible_leader"] ?? ""); ?>">
                                        <label class="form-label">Description</label><textarea name="description" class="form-control mb-2" maxlength="5000"><?php echo htmlspecialchars($s["description"] ?? ""); ?></textarea>
                                        <button class="btn btn-primary btn-sm">Save changes</button>
                                    </form>
                                </details>
                                <form method="POST" class="mt-2" onsubmit="return confirm('Delete this schedule entry?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <input type="hidden" name="schedule_id" value="<?php echo (int)$s["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-trash"></i></button>
                                </form>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
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
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rehearsal songs (optional)</label>
                        <select name="song_ids[]" class="form-select" multiple size="5">
                            <?php foreach ($availableSongs as $song): ?>
                                <option value="<?php echo (int)$song["id"]; ?>"><?php echo htmlspecialchars($song["song_title"] . " — " . $song["artist"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Only approved songs appear here. Hold Ctrl (Windows) or Command (Mac) to select several songs.</div>
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
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
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
