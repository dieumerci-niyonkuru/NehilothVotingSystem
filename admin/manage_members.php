<?php require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

// Handle actions
if (isset($_GET["approve"])) {
    $id = (int)$_GET["approve"];
    $code = generateAccessCode();
    $pdo->prepare("UPDATE members SET status='approved', access_code=? WHERE id=?")->execute([$code, $id]);
    addMemberNotification($id, "Your account has been approved! Your access code is: $code", "success");
    logAction($_SESSION["admin_id"], "Approved member $id");
    setFlash("success", "Member approved. Access code: $code");
    redirect("manage_members.php");
}
if (isset($_GET["reject"])) {
    $id = (int)$_GET["reject"];
    $pdo->prepare("UPDATE members SET status='rejected' WHERE id=?")->execute([$id]);
    logAction($_SESSION["admin_id"], "Rejected member $id");
    setFlash("warning", "Member rejected");
    redirect("manage_members.php");
}
if (isset($_GET["regenerate"])) {
    $id = (int)$_GET["regenerate"];
    $code = generateAccessCode();
    $pdo->prepare("UPDATE members SET access_code=? WHERE id=?")->execute([$code, $id]);
    addMemberNotification($id, "Your access code has been reset by admin. New code: $code", "info");
    logAction($_SESSION["admin_id"], "Regenerated code for member $id");
    setFlash("success", "New access code: $code");
    redirect("manage_members.php");
}
if (isset($_GET["delete"])) {
    $id = (int)$_GET["delete"];
    // Get member name for log
    $stmt = $pdo->prepare("SELECT name FROM members WHERE id=?");
    $stmt->execute([$id]);
    $member = $stmt->fetch();
    $pdo->prepare("DELETE FROM members WHERE id=?")->execute([$id]);
    logAction($_SESSION["admin_id"], "Deleted member $id: " . ($member ? $member["name"] : "unknown"));
    setFlash("danger", "Member permanently deleted");
    redirect("manage_members.php");
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["edit_member"])) {
    $id = (int)$_POST["member_id"];
    $name = sanitize($_POST["name"]);
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $phone = sanitize($_POST["phone"]);
    $status = sanitize($_POST["status"]);
    $stmt = $pdo->prepare("UPDATE members SET name=?, email=?, phone=?, status=? WHERE id=?");
    $stmt->execute([$name, $email, $phone, $status, $id]);
    logAction($_SESSION["admin_id"], "Edited member $id");
    setFlash("success", "Member updated successfully");
    redirect("manage_members.php");
}

$members = $pdo->query("SELECT * FROM members ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html><head><title>Manage Members - Full Control</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script><style>.modal-xl{max-width:800px;}</style></head>
<body><nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="dashboard.php"><i class="fas fa-users-cog"></i> Member Management</a><a href="dashboard.php" class="btn btn-light">Back to Dashboard</a></div></nav>
<div class="container mt-3"><div class="card"><div class="card-header bg-primary text-white"><h5><i class="fas fa-user-edit"></i> Full Member Control (Add, Edit, Delete, Approve, QR)</h5></div><div class="card-body"><?php $f=getFlash();if($f):?><div class="alert alert-<?php echo $f["type"];?>"><?php echo $f["message"];?></div><?php endif;?>
<div class="table-responsive"><table class="table table-bordered table-striped"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Access Code</th><th>QR</th><th>Actions</th></tr></thead><tbody>
<?php foreach($members as $m): ?>
<tr>
    <td><?php echo $m["id"]; ?></td>
    <td><?php echo htmlspecialchars($m["name"]); ?></td>
    <td><?php echo htmlspecialchars($m["email"]); ?></td>
    <td><?php echo htmlspecialchars($m["phone"]); ?></td>
    <td><span class="badge bg-<?php echo $m["status"]=='approved'?'success':($m["status"]=='pending'?'warning':'danger'); ?>"><?php echo $m["status"]; ?></span></td>
    <td><?php if($m["access_code"]): ?><code><?php echo $m["access_code"]; ?></code> <?php else: ?>—<?php endif; ?></td>
    <td><?php if($m["status"]=='approved' && $m["access_code"]): ?><button class="btn btn-sm btn-info" onclick="showQR('<?php echo $m["email"]; ?>','<?php echo $m["access_code"]; ?>')"><i class="fas fa-qrcode"></i> QR</button><?php else: ?>—<?php endif; ?></td>
    <td>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $m["id"]; ?>"><i class="fas fa-edit"></i> Edit</button>
        <a href="?regenerate=<?php echo $m["id"]; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Reset access code?')"><i class="fas fa-sync-alt"></i> New Code</a>
        <?php if($m["status"]=='pending'): ?>
            <a href="?approve=<?php echo $m["id"]; ?>" class="btn btn-sm btn-success" onclick="return confirm('Approve this member?')"><i class="fas fa-check"></i> Approve</a>
            <a href="?reject=<?php echo $m["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Reject this member?')"><i class="fas fa-times"></i> Reject</a>
        <?php endif; ?>
        <a href="?delete=<?php echo $m["id"]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('PERMANENTLY DELETE this member and all their votes? This cannot be undone!')"><i class="fas fa-trash-alt"></i> Delete</a>
    </td>
</tr>

<!-- Edit Modal for each member -->
<div class="modal fade" id="editModal<?php echo $m["id"]; ?>" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header bg-primary text-white"><h5>Edit Member: <?php echo htmlspecialchars($m["name"]); ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><form method="POST"><input type="hidden" name="member_id" value="<?php echo $m["id"]; ?>">
<div class="mb-3"><label>Full Name</label><input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($m["name"]); ?>" required></div>
<div class="mb-3"><label>Email Address</label><input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($m["email"]); ?>" required></div>
<div class="mb-3"><label>Phone Number</label><input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($m["phone"]); ?>"></div>
<div class="mb-3"><label>Status</label><select name="status" class="form-control"><option value="pending" <?php echo $m["status"]=='pending'?'selected':''; ?>>Pending</option><option value="approved" <?php echo $m["status"]=='approved'?'selected':''; ?>>Approved</option><option value="rejected" <?php echo $m["status"]=='rejected'?'selected':''; ?>>Rejected</option></select></div>
<button type="submit" name="edit_member" class="btn btn-primary">Save Changes</button></form></div></div></div></div>
<?php endforeach; ?>
</tbody></table></div></div></div></div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5>Member Login QR Code</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><div id="qrcode"></div><p class="mt-3">Scan with phone camera to login instantly</p></div></div></div></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>function showQR(email,code){const url=window.location.origin+'/NehilothVotingSystem/qr_login.php?email='+encodeURIComponent(email)+'&code='+code;document.getElementById('qrcode').innerHTML='';new QRCode(document.getElementById('qrcode'),{text:url,width:200,height:200});new bootstrap.Modal(document.getElementById('qrModal')).show();}</script>
</body></html>