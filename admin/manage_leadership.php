<?php
require_once "../config/functions.php";
if (!canManageArea("leadership")) redirect(isMemberLoggedIn() ? "../member/dashboard.php" : "login.php");

$portalRoles = ["member", "president", "secretary", "schedule_coordinator", "election_manager"];

function syncLeadershipMember($pdo, $leaderId, $memberId, $portalRole) {
    $current = $pdo->prepare("SELECT member_id FROM leadership WHERE id = ? FOR UPDATE");
    $current->execute([$leaderId]);
    $oldMemberId = (int)($current->fetchColumn() ?: 0);

    if ($memberId > 0) {
        $member = $pdo->prepare("SELECT id, status, role FROM members WHERE id = ? FOR UPDATE");
        $member->execute([$memberId]);
        $memberRow = $member->fetch();
        if (!$memberRow || $memberRow["status"] !== "approved" || $memberRow["role"] === "admin") {
            throw new RuntimeException("Choose an approved non-admin member account.");
        }
        $duplicate = $pdo->prepare("SELECT id FROM leadership WHERE member_id = ? AND id <> ? LIMIT 1");
        $duplicate->execute([$memberId, $leaderId]);
        if ($duplicate->fetch()) {
            throw new RuntimeException("That member account is already linked to another leader.");
        }
        $pdo->prepare("UPDATE members SET role = ? WHERE id = ?")->execute([$portalRole, $memberId]);
    }

    if ($oldMemberId > 0 && $oldMemberId !== $memberId) {
        $pdo->prepare("UPDATE members SET role = 'member' WHERE id = ? AND role IN ('president','secretary','schedule_coordinator','election_manager')")->execute([$oldMemberId]);
    }
    $pdo->prepare("UPDATE leadership SET member_id = ? WHERE id = ?")->execute([$memberId ?: null, $leaderId]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_leadership.php");
    }
    $action = $_POST["action"] ?? "";
    if ($action === "save_leader") {
        $leaderId = filter_var($_POST["leader_id"] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $name = trim($_POST["name"] ?? "");
        $role = trim($_POST["role"] ?? "");
        $bio = trim($_POST["bio"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $photo = trim($_POST["photo_url"] ?? "");
        $order = filter_var($_POST["display_order"] ?? 0, FILTER_VALIDATE_INT);
        $memberId = filter_var($_POST["member_id"] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $portalRole = $_POST["portal_role"] ?? "member";
        $validEmail = $email === "" || filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($name === "" || strlen($name) > 100 || $role === "" || strlen($role) > 100 ||
            strlen($bio) > 5000 || strlen($phone) > 20 || strlen($photo) > 255 ||
            ($photo !== "" && !isSafeMediaUrl($photo)) || !$validEmail ||
            !in_array($portalRole, $portalRoles, true)) {
            setFlash("danger", "Check the leader details, contact fields, and assigned portal role.");
            redirect("manage_leadership.php");
        }

        try {
            $pdo->beginTransaction();
            if ($leaderId > 0) {
                $stmt = $pdo->prepare("UPDATE leadership SET name = ?, role = ?, bio = ?, email = ?, phone = ?, photo_url = ?, display_order = ? WHERE id = ?");
                $stmt->execute([$name, $role, $bio, $email ?: null, $phone ?: null, $photo ?: null, $order ?: 0, $leaderId]);
                if ($stmt->rowCount() === 0) {
                    $exists = $pdo->prepare("SELECT id FROM leadership WHERE id = ?");
                    $exists->execute([$leaderId]);
                    if (!$exists->fetch()) {
                        throw new RuntimeException("Leadership entry was not found.");
                    }
                }
                syncLeadershipMember($pdo, $leaderId, $memberId, $portalRole);
            } else {
                $stmt = $pdo->prepare("INSERT INTO leadership (name, role, bio, email, phone, photo_url, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $role, $bio, $email ?: null, $phone ?: null, $photo ?: null, $order ?: 0]);
                $leaderId = (int)$pdo->lastInsertId();
                syncLeadershipMember($pdo, $leaderId, $memberId, $portalRole);
            }
            $pdo->commit();
            logAction($_SESSION["admin_id"] ?? 0, "Saved leadership entry #$leaderId");
            setFlash("success", "Leadership directory and account role updated.");
        } catch (RuntimeException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash("danger", $e->getMessage());
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Unable to save leadership entry: " . $e->getMessage());
            setFlash("danger", "The leadership entry could not be saved.");
        }
        redirect("manage_leadership.php");
    }

    if ($action === "delete_leader") {
        $leaderId = filter_var($_POST["leader_id"] ?? null, FILTER_VALIDATE_INT);
        if (!$leaderId) {
            setFlash("danger", "Invalid leadership entry.");
            redirect("manage_leadership.php");
        }
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT member_id FROM leadership WHERE id = ? FOR UPDATE");
            $stmt->execute([$leaderId]);
            $memberId = (int)($stmt->fetchColumn() ?: 0);
            if ($memberId > 0) {
                $pdo->prepare("UPDATE members SET role = 'member' WHERE id = ? AND role IN ('president','secretary','schedule_coordinator','election_manager')")->execute([$memberId]);
            }
            $pdo->prepare("DELETE FROM leadership WHERE id = ?")->execute([$leaderId]);
            $pdo->commit();
            logAction($_SESSION["admin_id"] ?? 0, "Deleted leadership entry #$leaderId");
            setFlash("success", "Leadership entry removed and its linked portal role revoked.");
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Unable to remove leadership entry: " . $e->getMessage());
            setFlash("danger", "The leadership entry could not be removed.");
        }
        redirect("manage_leadership.php");
    }
}

$leaders = $pdo->query("SELECT l.*, m.name AS account_name, m.role AS portal_role FROM leadership l LEFT JOIN members m ON m.id = l.member_id ORDER BY l.display_order ASC, l.id ASC")->fetchAll();
$members = $pdo->query("SELECT id, name, email, role FROM members WHERE status = 'approved' AND role <> 'admin' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Leadership & Roles - Nehiloth Worship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f5f6f8;min-height:100vh}.leader-form{background:#fff;border-radius:1rem}</style>
</head>
<body>
<?php include "../navbar.php"; ?>
<main class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><h1 class="h2 fw-bold mb-1"><i class="fas fa-users-cog text-warning me-2"></i>Leadership & Portal Roles</h1><p class="text-muted mb-0">Manage the public directory and link leaders to scoped team dashboards.</p></div>
        <a href="dashboard.php" class="btn btn-outline-dark">Dashboard</a>
    </div>
    <?php $f = getFlash(); if ($f): ?><div class="alert alert-<?php echo htmlspecialchars($f["type"]); ?>"><?php echo htmlspecialchars($f["message"]); ?></div><?php endif; ?>

    <form method="POST" class="leader-form shadow-sm p-4 mb-4">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
        <input type="hidden" name="action" value="save_leader">
        <h2 class="h5 fw-bold mb-3">Add a leader</h2>
        <div class="row g-3">
            <div class="col-md-4"><label class="form-label">Name</label><input name="name" class="form-control" maxlength="100" required></div>
            <div class="col-md-4"><label class="form-label">Directory title</label><input name="role" class="form-control" maxlength="100" placeholder="Worship Director" required></div>
            <div class="col-md-4"><label class="form-label">Approved member account</label><select name="member_id" class="form-select"><option value="0">No linked account</option><?php foreach ($members as $member): ?><option value="<?php echo (int)$member["id"]; ?>"><?php echo htmlspecialchars($member["name"] . " — " . $member["email"]); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Portal role</label><select name="portal_role" class="form-select"><?php foreach ($portalRoles as $portalRole): ?><option value="<?php echo htmlspecialchars($portalRole); ?>"><?php echo htmlspecialchars(ucfirst(str_replace("_", " ", $portalRole))); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" maxlength="20"></div>
            <div class="col-md-8"><label class="form-label">Photo URL</label><input type="url" name="photo_url" class="form-control" placeholder="https://"></div>
            <div class="col-md-4"><label class="form-label">Display order</label><input type="number" name="display_order" class="form-control" value="0"></div>
            <div class="col-12"><label class="form-label">Bio</label><textarea name="bio" class="form-control" rows="2" maxlength="5000"></textarea></div>
        </div>
        <button class="btn btn-primary mt-3" type="submit">Add leader</button>
    </form>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Leader</th><th>Directory role</th><th>Linked account</th><th>Contact</th><th>Manage</th></tr></thead>
                <tbody>
                <?php foreach ($leaders as $leader): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($leader["name"]); ?></strong><div class="small text-muted"><?php echo htmlspecialchars($leader["bio"] ?? ""); ?></div></td>
                        <td><?php echo htmlspecialchars($leader["role"]); ?></td>
                        <td><?php echo htmlspecialchars($leader["account_name"] ? $leader["account_name"] . " (" . str_replace("_", " ", $leader["portal_role"]) . ")" : "Not linked"); ?></td>
                        <td class="small"><?php echo htmlspecialchars($leader["email"] ?? ""); ?><br><?php echo htmlspecialchars($leader["phone"] ?? ""); ?></td>
                        <td>
                            <details>
                                <summary class="btn btn-sm btn-outline-primary">Edit</summary>
                                <form method="POST" class="leader-form shadow-sm p-3 mt-2" style="min-width:min(82vw,460px)">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                    <input type="hidden" name="action" value="save_leader">
                                    <input type="hidden" name="leader_id" value="<?php echo (int)$leader["id"]; ?>">
                                    <label class="form-label">Name</label><input name="name" class="form-control mb-2" maxlength="100" value="<?php echo htmlspecialchars($leader["name"]); ?>" required>
                                    <label class="form-label">Directory title</label><input name="role" class="form-control mb-2" maxlength="100" value="<?php echo htmlspecialchars($leader["role"]); ?>" required>
                                    <label class="form-label">Approved member account</label><select name="member_id" class="form-select mb-2"><option value="0">No linked account</option><?php foreach ($members as $member): ?><option value="<?php echo (int)$member["id"]; ?>" <?php echo (int)$leader["member_id"] === (int)$member["id"] ? "selected" : ""; ?>><?php echo htmlspecialchars($member["name"] . " — " . $member["email"]); ?></option><?php endforeach; ?></select>
                                    <label class="form-label">Portal role</label><select name="portal_role" class="form-select mb-2"><?php foreach ($portalRoles as $portalRole): ?><option value="<?php echo htmlspecialchars($portalRole); ?>" <?php echo $leader["portal_role"] === $portalRole ? "selected" : ""; ?>><?php echo htmlspecialchars(ucfirst(str_replace("_", " ", $portalRole))); ?></option><?php endforeach; ?></select>
                                    <label class="form-label">Email</label><input type="email" name="email" class="form-control mb-2" value="<?php echo htmlspecialchars($leader["email"] ?? ""); ?>">
                                    <label class="form-label">Phone</label><input name="phone" class="form-control mb-2" maxlength="20" value="<?php echo htmlspecialchars($leader["phone"] ?? ""); ?>">
                                    <label class="form-label">Photo URL</label><input type="url" name="photo_url" class="form-control mb-2" value="<?php echo htmlspecialchars($leader["photo_url"] ?? ""); ?>">
                                    <label class="form-label">Display order</label><input type="number" name="display_order" class="form-control mb-2" value="<?php echo (int)$leader["display_order"]; ?>">
                                    <label class="form-label">Bio</label><textarea name="bio" class="form-control mb-3" rows="2" maxlength="5000"><?php echo htmlspecialchars($leader["bio"] ?? ""); ?></textarea>
                                    <button class="btn btn-primary btn-sm">Save changes</button>
                                </form>
                            </details>
                            <form method="POST" class="mt-2" onsubmit="return confirm('Remove this leader and revoke their linked leadership role?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                <input type="hidden" name="action" value="delete_leader">
                                <input type="hidden" name="leader_id" value="<?php echo (int)$leader["id"]; ?>">
                                <button class="btn btn-sm btn-outline-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$leaders): ?><tr><td colspan="5" class="text-center text-muted py-4">No leadership entries have been added.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php include "../mobile_nav.php"; ?>
<?php include "../footer.php"; ?>
</body>
</html>
