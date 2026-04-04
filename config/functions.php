<?php
require_once __DIR__ . "/database.php";

function isAdminLoggedIn() { return isset($_SESSION["admin_id"]); }
function isMemberLoggedIn() { return isset($_SESSION["member_id"]); }
function redirect($url) { header("Location: $url"); exit(); }
function sanitize($input) { return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8"); }
function generateAccessCode() { return strtoupper(substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8)); }
function isElectionActive() { global $pdo; $stmt = $pdo->query("SELECT election_active FROM settings WHERE id = 1"); $res = $stmt->fetch(); return $res ? (bool)$res["election_active"] : false; }
function getVoteCount($candidate_id) { global $pdo; $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM votes WHERE candidate_id = ?"); $stmt->execute([$candidate_id]); return (int)$stmt->fetch()["c"]; }
function hasVoted($member_id, $position_id) { global $pdo; $stmt = $pdo->prepare("SELECT id FROM votes WHERE member_id = ? AND position_id = ?"); $stmt->execute([$member_id, $position_id]); return $stmt->fetch() ? true : false; }
function getWinner($position_id) { global $pdo; $stmt = $pdo->prepare("SELECT c.*, COUNT(v.id) as votes FROM candidates c LEFT JOIN votes v ON c.id = v.candidate_id WHERE c.position_id = ? GROUP BY c.id ORDER BY votes DESC LIMIT 1"); $stmt->execute([$position_id]); return $stmt->fetch(); }
function setFlash($type, $msg) { $_SESSION["flash"] = ["type" => $type, "message" => $msg]; }
function getFlash() { if (isset($_SESSION["flash"])) { $f = $_SESSION["flash"]; unset($_SESSION["flash"]); return $f; } return null; }
function logAction($admin_id, $action) { global $pdo; $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, ?, ?)"); $stmt->execute([$admin_id, $action, $_SERVER["REMOTE_ADDR"]]); }

// ========== NEW FUNCTIONS (with error handling) ==========
function addNotification($message, $type = "info") {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, ?)");
        $stmt->execute([$message, $type]);
    } catch (PDOException $e) {
        // Table might not exist yet â€“ ignore
    }
}

function getUnreadNotifications() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 20");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return []; // table doesn't exist yet
    }
}

function markNotificationsRead() {
    global $pdo;
    try {
        $pdo->exec("UPDATE notifications SET is_read = 1");
    } catch (PDOException $e) {
        // ignore
    }
}

function getVoteDetails($candidate_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT m.name as member_name, m.email, v.created_at 
        FROM votes v 
        JOIN members m ON v.member_id = m.id 
        WHERE v.candidate_id = ?
        ORDER BY v.created_at DESC
    ");
    $stmt->execute([$candidate_id]);
    return $stmt->fetchAll();
}

function generateVoteReport() {
    global $pdo;
    $positions = $pdo->query("SELECT * FROM positions")->fetchAll();
    $report = [];
    foreach ($positions as $pos) {
        $candidates = $pdo->prepare("SELECT * FROM candidates WHERE position_id = ?");
        $candidates->execute([$pos["id"]]);
        $cands = $candidates->fetchAll();
        $positionData = [
            "title" => $pos["title"],
            "candidates" => []
        ];
        foreach ($cands as $c) {
            $votes = getVoteCount($c["id"]);
            $voters = getVoteDetails($c["id"]);
            $positionData["candidates"][] = [
                "name" => $c["name"],
                "bio" => $c["bio"],
                "votes" => $votes,
                "voters" => $voters
            ];
        }
        $positionData["winner"] = getWinner($pos["id"]);
        $report[] = $positionData;
    }
    return $report;
}

// ========== MEMBER NOTIFICATIONS ==========
function addMemberNotification($member_id, $message, $type = "info") {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO member_notifications (member_id, message, type) VALUES (?, ?, ?)");
        $stmt->execute([$member_id, $message, $type]);
    } catch (PDOException $e) {
        // ignore
    }
}

function getMemberNotifications($member_id, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM member_notifications WHERE member_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$member_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function markMemberNotificationRead($notification_id, $member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member_notifications SET is_read = 1 WHERE id = ? AND member_id = ?");
        $stmt->execute([$notification_id, $member_id]);
    } catch (PDOException $e) {
        // ignore
    }
}

function markAllMemberNotificationsRead($member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member_notifications SET is_read = 1 WHERE member_id = ?");
        $stmt->execute([$member_id]);
    } catch (PDOException $e) {
        // ignore
    }
}

function notifyAllMembers($message, $type = "info") {
    global $pdo;
    try {
        $members = $pdo->query("SELECT id FROM members WHERE status = 'approved'")->fetchAll();
        foreach ($members as $m) {
            addMemberNotification($m["id"], $message, $type);
        }
    } catch (PDOException $e) {
        // ignore
    }
}
?>