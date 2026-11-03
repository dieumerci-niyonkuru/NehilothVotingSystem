<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

if ($_SERVER["REQUEST_METHOD"] === "POST" && in_array($_POST["action"] ?? "", ["approve", "reject", "regenerate", "delete"], true)) {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_members.php");
    }
    $id = filter_var($_POST["member_id"] ?? null, FILTER_VALIDATE_INT);
    if (!$id) {
        setFlash("danger", "Invalid member account.");
        redirect("manage_members.php");
    }
    $action = $_POST["action"];
    if ($action === "approve") {
        $code = generateAccessCode();
        $pdo->prepare("UPDATE members SET status = 'approved', access_code = ? WHERE id = ?")->execute([$code, $id]);
        addMemberNotification($id, "Your account has been approved! Your access code is: $code", "success");
        logAction($_SESSION["admin_id"], "Approved member $id");
        setFlash("success", "Member approved. Access code: $code");
    } elseif ($action === "reject") {
        $pdo->prepare("UPDATE members SET status = 'rejected' WHERE id = ? AND role <> 'admin'")->execute([$id]);
        logAction($_SESSION["admin_id"], "Rejected member $id");
        setFlash("warning", "Member rejected.");
    } elseif ($action === "regenerate") {
        $code = generateAccessCode();
        $pdo->prepare("UPDATE members SET access_code = ? WHERE id = ?")->execute([$code, $id]);
        addMemberNotification($id, "Your access code has been reset by an administrator. New code: $code", "info");
        logAction($_SESSION["admin_id"], "Regenerated code for member $id");
        setFlash("success", "New access code: $code");
    } else {
        $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
        $stmt->execute([$id]);
        $member = $stmt->fetch();
        $pdo->prepare("DELETE FROM admin WHERE member_id = ?")->execute([$id]);
        $pdo->prepare("UPDATE leadership SET member_id = NULL WHERE member_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM members WHERE id = ? AND role <> 'admin'")->execute([$id]);
        logAction($_SESSION["admin_id"], "Deleted member $id: " . ($member ? $member["name"] : "unknown"));
        setFlash("danger", "Member permanently deleted.");
    }
    redirect("manage_members.php");
}
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_member"])) {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_members.php");
    }

    $id = filter_var($_POST["member_id"] ?? null, FILTER_VALIDATE_INT);
    $name = trim($_POST["name"] ?? "");
    $email = filter_var(trim($_POST["email"] ?? ""), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST["phone"] ?? "");
    $status = $_POST["status"] ?? "";
    $role = $_POST["role"] ?? "member";
    $allowedRoles = ["member", "president", "secretary", "schedule_coordinator", "election_manager", "admin"];
    if (!$id || $name === "" || strlen($name) > 100 || !$email || strlen($phone) > 20 ||
        !in_array($status, ["pending", "approved", "rejected"], true) ||
        !in_array($role, $allowedRoles, true) || ($role === "admin" && $status !== "approved")) {
        setFlash("danger", "Check the member details. Full administrator access can only be granted to an approved member.");
        redirect("manage_members.php");
    }

    try {
        $pdo->beginTransaction();
        $memberStmt = $pdo->prepare("SELECT password FROM members WHERE id = ? FOR UPDATE");
        $memberStmt->execute([$id]);
        $member = $memberStmt->fetch();
        if (!$member) {
            $pdo->rollBack();
            setFlash("danger", "Member account not found.");
            redirect("manage_members.php");
        }

        if ($role === "admin") {
            $linkedAccount = $pdo->prepare("SELECT id FROM admin WHERE member_id = ? LIMIT 1 FOR UPDATE");
            $linkedAccount->execute([$id]);
            $adminAccountId = $linkedAccount->fetchColumn();
            $duplicate = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND (member_id IS NULL OR member_id <> ?) LIMIT 1");
            $duplicate->execute([$email, $id]);
            if ($duplicate->fetch()) {
                $pdo->rollBack();
                setFlash("danger", "That email is already used by a separate administrator. Use a unique member email to grant full admin access.");
                redirect("manage_members.php");
            }
            if ($adminAccountId) {
                $grant = $pdo->prepare("UPDATE admin SET email = ?, password = ? WHERE member_id = ?");
                $grant->execute([$email, $member["password"], $id]);
            } else {
                $grant = $pdo->prepare("INSERT INTO admin (email, password, member_id) VALUES (?, ?, ?)");
                $grant->execute([$email, $member["password"], $id]);
            }
        } else {
            $pdo->prepare("DELETE FROM admin WHERE member_id = ?")->execute([$id]);
        }

        $update = $pdo->prepare("UPDATE members SET name = ?, email = ?, phone = ?, status = ?, role = ? WHERE id = ?");
        $update->execute([$name, $email, $phone, $status, $role, $id]);
        $pdo->commit();
        logAction($_SESSION["admin_id"], "Updated member $id role to $role");
        setFlash("success", "Member details and access role updated.");
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Unable to update member role: " . $e->getMessage());
        setFlash("danger", "The member update failed. Check the email and try again.");
    }
    redirect("manage_members.php");
}

$members = $pdo->query("SELECT * FROM members ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Manage Members - Full Control</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><style>.modal-xl{max-width:800px;}</style></head>
<body><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php"><i class="fas fa-users-cog"></i> Member Management</a><a href="dashboard.php" class="btn btn-light">Back to Dashboard</a></div></nav>
<div class="container mt-3"><div class="card"><div class="card-header bg-primary text-white"><h5><i class="fas fa-user-edit"></i> Full Member Control (Add, Edit, Delete, Approve, QR)</h5></div><div class="card-body"><?php $f=getFlash();if($f):?><div class="alert alert-<?php echo $f["type"];?>"><?php echo $f["message"];?></div><?php endif;?>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Assigned Role</th><th>Access Code</th><th>QR</th><th>Actions</th></tr></thead><tbody>
<?php foreach($members as $m): ?>
<tr>
    <td><?php echo $m["id"]; ?></td>
    <td><?php echo htmlspecialchars($m["name"]); ?></td>
    <td><?php echo htmlspecialchars($m["email"]); ?></td>
    <td><?php echo htmlspecialchars($m["phone"]); ?></td>
    <td><span class="badge bg-<?php echo $m["status"]=='approved'?'success':($m["status"]=='pending'?'warning':'danger'); ?>"><?php echo $m["status"]; ?></span></td>
    <td><span class="badge bg-dark"><?php echo htmlspecialchars($m["role"] ?? "member"); ?></span></td>
    <td><?php if($m["access_code"]): ?><code><?php echo $m["access_code"]; ?></code> <?php else: ?>—<?php endif; ?></td>
    <td><?php if($m["status"]=='approved' && $m["access_code"]): ?><button class="btn btn-sm btn-info" onclick="showQR('<?php echo $m["email"]; ?>','<?php echo $m["access_code"]; ?>')"><i class="fas fa-qrcode"></i> QR</button><?php else: ?>—<?php endif; ?></td>
    <td>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $m["id"]; ?>"><i class="fas fa-edit"></i> Edit</button>
        <form method="POST" class="d-inline" onsubmit="return confirm('Reset access code?');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>"><input type="hidden" name="member_id" value="<?php echo (int)$m["id"]; ?>"><input type="hidden" name="action" value="regenerate"><button class="btn btn-sm btn-warning"><i class="fas fa-sync-alt"></i> New Code</button></form>
        <?php if($m["status"]=='pending'): ?>
            <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>"><input type="hidden" name="member_id" value="<?php echo (int)$m["id"]; ?>"><input type="hidden" name="action" value="approve"><button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Approve</button></form>
            <form method="POST" class="d-inline"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>"><input type="hidden" name="member_id" value="<?php echo (int)$m["id"]; ?>"><input type="hidden" name="action" value="reject"><button class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reject</button></form>
        <?php endif; ?>
        <?php if (($m["role"] ?? "member") !== "admin"): ?><form method="POST" class="d-inline" onsubmit="return confirm('PERMANENTLY DELETE this member and their linked data? This cannot be undone!');"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>"><input type="hidden" name="member_id" value="<?php echo (int)$m["id"]; ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-danger"><i class="fas fa-trash-alt"></i> Delete</button></form><?php endif; ?>
    </td>
</tr>

<!-- Edit Modal for each member -->
<div class="modal fade" id="editModal<?php echo $m["id"]; ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5>Edit Member: <?php echo htmlspecialchars($m["name"]); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><form method="POST"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>"><input type="hidden" name="member_id" value="<?php echo $m["id"]; ?>">
<div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($m["name"]); ?>" required></div>
<div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($m["email"]); ?>" required></div>
<div class="mb-3"><label>Phone Number</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($m["phone"]); ?>"></div>
<div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="pending" <?php echo $m["status"]=='pending'?'selected':''; ?>>Pending</option><option value="approved" <?php echo $m["status"]=='approved'?'selected':''; ?>>Approved</option><option value="rejected" <?php echo $m["status"]=='rejected'?'selected':''; ?>>Rejected</option></select></div>
<div class="mb-3"><label>Assigned role</label><select name="role" class="form-select"><option value="member" <?php echo ($m["role"] ?? "member") === "member" ? "selected" : ""; ?>>Member</option><option value="president" <?php echo ($m["role"] ?? "") === "president" ? "selected" : ""; ?>>President (leadership dashboard)</option><option value="secretary" <?php echo ($m["role"] ?? "") === "secretary" ? "selected" : ""; ?>>Secretary</option><option value="schedule_coordinator" <?php echo ($m["role"] ?? "") === "schedule_coordinator" ? "selected" : ""; ?>>Schedule coordinator</option><option value="election_manager" <?php echo ($m["role"] ?? "") === "election_manager" ? "selected" : ""; ?>>Election manager</option><option value="admin" <?php echo ($m["role"] ?? "") === "admin" ? "selected" : ""; ?>>Full administrator (explicit grant)</option></select><div class="form-text">The President receives a separate leadership dashboard. Full admin access is a separate, explicit grant.</div></div>
<button type="submit" name="edit_member" class="btn btn-primary">Save Changes</button></form></div></div></div></div>
<?php endforeach; ?>
</tbody></table></div></div></div></div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Member Login QR Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><div id="qrcode"></div><p class="mt-3">Scan with phone camera to login instantly</p></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>function showQR(email,code){const url=window.location.origin+'/NehilothVotingSystem/qr_login.php?email='+encodeURIComponent(email)+'&code='+code;document.getElementById('qrcode').innerHTML='';new QRCode(document.getElementById('qrcode'),{text:url,width:200,height:200});new bootstrap.Modal(document.getElementById('qrModal')).show();}</script>
</body></html>