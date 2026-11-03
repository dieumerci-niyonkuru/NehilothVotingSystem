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

// ========== NOTIFICATIONS ==========
function addNotification($message, $type = "info") {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, ?)");
        $stmt->execute([$message, $type]);
    } catch (PDOException $e) {}
}

function getUnreadNotifications() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 20");
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function markNotificationsRead() {
    global $pdo;
    try { $pdo->exec("UPDATE notifications SET is_read = 1"); } catch (PDOException $e) {}
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
    } catch (PDOException $e) {}
}

function getMemberNotifications($member_id, $limit = 10) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM member_notifications WHERE member_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$member_id, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function markMemberNotificationRead($notification_id, $member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member_notifications SET is_read = 1 WHERE id = ? AND member_id = ?");
        $stmt->execute([$notification_id, $member_id]);
    } catch (PDOException $e) {}
}

function markAllMemberNotificationsRead($member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE member_notifications SET member_id = ?");
        $stmt->execute([$member_id]);
    } catch (PDOException $e) {}
}

function notifyAllMembers($message, $type = "info") {
    global $pdo;
    try {
        $members = $pdo->query("SELECT id FROM members WHERE status = 'approved'")->fetchAll();
        foreach ($members as $m) {
            addMemberNotification($m["id"], $message, $type);
        }
    } catch (PDOException $e) {}
}

// ========== SONG REQUESTS FUNCTIONS ==========
function getSongRequests($status = null, $category = null, $limit = 50) {
    global $pdo;
    try {
        $sql = "SELECT s.*, m.name as member_name_db FROM song_requests s LEFT JOIN members m ON s.member_id = m.id WHERE 1=1";
        $params = [];
        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        if ($category) {
            $sql .= " AND s.genre_category = ?";
            $params[] = $category;
        }
        $sql .= " ORDER BY s.votes_count DESC, s.requested_at DESC LIMIT " . (int)$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function getMemberSongRequests($member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM song_requests WHERE member_id = ? ORDER BY requested_at DESC");
        $stmt->execute([$member_id]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function addSongRequest($member_id, $requester_name, $title, $artist, $category, $key_note, $lyrics_link, $notes) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO song_requests (member_id, requester_name, song_title, artist, genre_category, key_note, lyrics_link, notes, status, votes_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 1)");
        $stmt->execute([$member_id, $requester_name, $title, $artist, $category, $key_note, $lyrics_link, $notes]);
        $song_id = $pdo->lastInsertId();
        if ($member_id) {
            $pdo->prepare("INSERT INTO song_votes (song_id, member_id) VALUES (?, ?)")->execute([$song_id, $member_id]);
        }
        addNotification("New song request submitted: '$title' by $requester_name", "info");
        return true;
    } catch (PDOException $e) { return false; }
}

function voteForSong($song_id, $member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO song_votes (song_id, member_id) VALUES (?, ?)");
        $stmt->execute([$song_id, $member_id]);
        $pdo->prepare("UPDATE song_requests SET votes_count = votes_count + 1 WHERE id = ?")->execute([$song_id]);
        return true;
    } catch (PDOException $e) { return false; }
}

function hasVotedForSong($song_id, $member_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id FROM song_votes WHERE song_id = ? AND member_id = ?");
        $stmt->execute([$song_id, $member_id]);
        return $stmt->fetch() ? true : false;
    } catch (PDOException $e) { return false; }
}

function updateSongStatus($song_id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE song_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $song_id]);
        return true;
    } catch (PDOException $e) { return false; }
}

function deleteSongRequest($song_id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM song_requests WHERE id = ?");
        $stmt->execute([$song_id]);
        return true;
    } catch (PDOException $e) { return false; }
}

// ========== LEADERSHIP DIRECTORY FUNCTIONS ==========
function getLeadershipTeam() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM leadership ORDER BY display_order ASC, id ASC");
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function addLeadershipMember($name, $role, $bio, $email, $phone, $photo_url, $display_order) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO leadership (name, role, bio, email, phone, photo_url, display_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $role, $bio, $email, $phone, $photo_url, (int)$display_order]);
        return true;
    } catch (PDOException $e) { return false; }
}

function deleteLeadershipMember($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM leadership WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch (PDOException $e) { return false; }
}

// ========== WORSHIP TEAM APPLICATIONS FUNCTIONS ==========
function submitWorshipApplication($member_id, $name, $email, $phone, $role, $experience, $availability, $notes) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO worship_applications (member_id, applicant_name, email, phone, role_applied, experience, availability, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$member_id, $name, $email, $phone, $role, $experience, $availability, $notes]);
        addNotification("New worship team application from $name ($role)", "success");
        return true;
    } catch (PDOException $e) { return false; }
}

function getWorshipApplications($status = null) {
    global $pdo;
    try {
        if ($status) {
            $stmt = $pdo->prepare("SELECT * FROM worship_applications WHERE status = ? ORDER BY applied_at DESC");
            $stmt->execute([$status]);
        } else {
            $stmt = $pdo->query("SELECT * FROM worship_applications ORDER BY applied_at DESC");
        }
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function updateApplicationStatus($id, $status) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE worship_applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        return true;
    } catch (PDOException $e) { return false; }
}

// ========== AGGREGATED DASHBOARD STATS ==========
function getDashboardStats() {
    global $pdo;
    $stats = [
        "total_members" => 0,
        "pending_members" => 0,
        "approved_members" => 0,
        "total_song_requests" => 0,
        "pending_songs" => 0,
        "total_votes_cast" => 0,
        "pending_applications" => 0,
        "leadership_count" => 0,
        "election_active" => isElectionActive()
    ];
    try {
        $stats["total_members"] = (int)$pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
        $stats["pending_members"] = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'pending'")->fetchColumn();
        $stats["approved_members"] = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'approved'")->fetchColumn();
        $stats["total_song_requests"] = (int)$pdo->query("SELECT COUNT(*) FROM song_requests")->fetchColumn();
        $stats["pending_songs"] = (int)$pdo->query("SELECT COUNT(*) FROM song_requests WHERE status = 'pending'")->fetchColumn();
        $stats["total_votes_cast"] = (int)$pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
        $stats["pending_applications"] = (int)$pdo->query("SELECT COUNT(*) FROM worship_applications WHERE status = 'pending'")->fetchColumn();
        $stats["leadership_count"] = (int)$pdo->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
    } catch (PDOException $e) {}
    return $stats;
}
?>