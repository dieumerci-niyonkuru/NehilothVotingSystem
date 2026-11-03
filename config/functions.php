<?php
require_once __DIR__ . "/database.php";

function isAdminLoggedIn() { return isset($_SESSION["admin_id"]); }
function isMemberLoggedIn() { return isset($_SESSION["member_id"]); }
function redirect($url) { header("Location: $url"); exit(); }
function sanitize($input) { return htmlspecialchars(trim($input), ENT_QUOTES, "UTF-8"); }
function generateAccessCode() { return strtoupper(substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 8)); }
function csrfToken() {
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}
function verifyCsrfToken($token) {
    return is_string($token) && isset($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token);
}
function isElectionActive() {
    global $pdo;
    $elections = $pdo->query("SELECT id FROM elections WHERE status IN ('upcoming','open','paused') ORDER BY id DESC")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($elections as $election_id) {
        if (getElectionStatus((int)$election_id) === "open") {
            return true;
        }
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn() > 0) {
        return false;
    }
    $stmt = $pdo->query("SELECT election_active FROM settings WHERE id = 1");
    $res = $stmt->fetch();
    return $res ? (bool)$res["election_active"] : false;
}
function getElectionStatus($election_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT status, start_time, end_time FROM elections WHERE id = ?");
    $stmt->execute([(int)$election_id]);
    $election = $stmt->fetch();
    if (!$election) {
        return "missing";
    }
    $status = $election["status"];
    if ($status === "upcoming" && strtotime($election["start_time"]) <= time()) {
        $pdo->prepare("UPDATE elections SET status = 'open' WHERE id = ? AND status = 'upcoming'")->execute([(int)$election_id]);
        $status = "open";
    }
    if (in_array($status, ["open", "paused"], true) && strtotime($election["end_time"]) <= time()) {
        $pdo->prepare("UPDATE elections SET status = 'closed' WHERE id = ? AND status IN ('open', 'paused')")->execute([(int)$election_id]);
        return "closed";
    }
    return $status;
}
function transitionElection($election_id, $action) {
    global $pdo;
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT * FROM elections WHERE id = ? FOR UPDATE");
        $stmt->execute([(int)$election_id]);
        $election = $stmt->fetch();
        if (!$election) {
            $pdo->rollBack();
            return ["success" => false, "message" => "Election not found."];
        }

        $status = $election["status"];
        $new_status = null;
        if ($action === "publish" && $status === "draft") {
            if (strtotime($election["start_time"]) >= strtotime($election["end_time"]) || strtotime($election["end_time"]) <= time()) {
                $pdo->rollBack();
                return ["success" => false, "message" => "The opening time must be before closing, and the closing time must still be in the future."];
            }
            $positions = $pdo->prepare("SELECT id FROM positions WHERE election_id = ?");
            $positions->execute([(int)$election_id]);
            $position_ids = $positions->fetchAll(PDO::FETCH_COLUMN);
            if (!$position_ids) {
                $pdo->rollBack();
                return ["success" => false, "message" => "Add at least one position before publishing."];
            }
            foreach ($position_ids as $position_id) {
                $candidate = $pdo->prepare("SELECT id FROM candidates WHERE position_id = ? LIMIT 1");
                $candidate->execute([(int)$position_id]);
                if (!$candidate->fetch()) {
                    $pdo->rollBack();
                    return ["success" => false, "message" => "Every position needs at least one candidate before publishing."];
                }
            }
            $eligible = $pdo->prepare("INSERT IGNORE INTO election_eligible_voters (election_id, member_id) SELECT ?, id FROM members WHERE status = 'approved'");
            $eligible->execute([(int)$election_id]);
            $eligible_count = $pdo->prepare("SELECT COUNT(*) FROM election_eligible_voters WHERE election_id = ?");
            $eligible_count->execute([(int)$election_id]);
            if ((int)$eligible_count->fetchColumn() === 0) {
                $pdo->rollBack();
                return ["success" => false, "message" => "At least one approved member is required before publishing an election."];
            }
            $new_status = strtotime($election["start_time"]) <= time() ? "open" : "upcoming";
            $pdo->prepare("UPDATE elections SET status = ?, published_at = NOW(), results_published = 0 WHERE id = ?")->execute([$new_status, (int)$election_id]);
        } elseif ($action === "pause" && $status === "open") {
            $new_status = "paused";
        } elseif ($action === "resume" && $status === "paused" && strtotime($election["end_time"]) > time()) {
            $new_status = "open";
        } elseif ($action === "close" && in_array($status, ["upcoming", "open", "paused"], true)) {
            $new_status = "closed";
        } elseif ($action === "cancel" && in_array($status, ["draft", "upcoming"], true)) {
            $new_status = "cancelled";
        } elseif ($action === "publish_results" && $status === "closed" && !(bool)$election["results_published"]) {
            $pdo->prepare("UPDATE elections SET results_published = 1 WHERE id = ?")->execute([(int)$election_id]);
            $new_status = "closed";
        } else {
            $pdo->rollBack();
            return ["success" => false, "message" => "That action is not allowed for the current election status."];
        }

        if ($new_status !== $status) {
            $pdo->prepare("UPDATE elections SET status = ? WHERE id = ?")->execute([$new_status, (int)$election_id]);
        }
        $pdo->commit();
        return ["success" => true, "message" => "Election updated successfully."];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Unable to transition election: " . $e->getMessage());
        return ["success" => false, "message" => "The election could not be updated. Check the server logs and try again."];
    }
}
function hasVotedInElection($member_id, $election_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id FROM election_vote_receipts WHERE election_id = ? AND member_id = ? LIMIT 1");
    $stmt->execute([(int)$election_id, (int)$member_id]);
    return (bool)$stmt->fetch();
}
function submitElectionBallot($member_id, $election_id, $selections, $idempotency_key) {
    global $pdo;
    if (!is_array($selections) || !is_string($idempotency_key) || strlen($idempotency_key) < 16 || strlen($idempotency_key) > 128) {
        return ["success" => false, "message" => "The ballot is incomplete or invalid."];
    }

    try {
        $pdo->beginTransaction();
        $election_stmt = $pdo->prepare("SELECT id, status, start_time, end_time FROM elections WHERE id = ? FOR UPDATE");
        $election_stmt->execute([(int)$election_id]);
        $election = $election_stmt->fetch();
        if (!$election) {
            $pdo->rollBack();
            return ["success" => false, "message" => "Election not found."];
        }

        $member_stmt = $pdo->prepare("SELECT id FROM members WHERE id = ? AND status = 'approved'");
        $member_stmt->execute([(int)$member_id]);
        if (!$member_stmt->fetch()) {
            $pdo->rollBack();
            return ["success" => false, "message" => "Only approved members may vote."];
        }

        $idempotency_hash = hash("sha256", $idempotency_key);
        $existing = $pdo->prepare("SELECT id, idempotency_hash, receipt_code, receipt_hash FROM election_vote_receipts WHERE election_id = ? AND member_id = ?");
        $existing->execute([(int)$election_id, (int)$member_id]);
        $receipt = $existing->fetch();
        if ($receipt) {
            $pdo->rollBack();
            $receipt_valid = is_string($receipt["receipt_code"]) && hash_equals($receipt["receipt_hash"], hash("sha256", $receipt["receipt_code"]));
            if (!$receipt_valid) {
                error_log("Election receipt integrity check failed for receipt row " . (int)$receipt["id"]);
            }
            $same_request = hash_equals($receipt["idempotency_hash"], $idempotency_hash);
            return [
                "success" => $same_request && $receipt_valid,
                "duplicate" => true,
                "receipt" => $same_request && $receipt_valid ? $receipt["receipt_code"] : null,
                "message" => !$receipt_valid
                    ? "The existing ballot receipt could not be verified. Contact an election administrator."
                    : ($same_request
                    ? "This ballot was already recorded."
                    : "A ballot has already been submitted for this election.")
            ];
        }

        $now = time();
        if ($election["status"] === "upcoming" && strtotime($election["start_time"]) <= $now) {
            $pdo->prepare("UPDATE elections SET status = 'open' WHERE id = ? AND status = 'upcoming'")->execute([(int)$election_id]);
            $election["status"] = "open";
        }
        if (in_array($election["status"], ["open", "paused"], true) && strtotime($election["end_time"]) <= $now) {
            $pdo->prepare("UPDATE elections SET status = 'closed' WHERE id = ? AND status IN ('open', 'paused')")->execute([(int)$election_id]);
            $pdo->commit();
            return ["success" => false, "message" => "Voting has closed for this election."];
        }
        if ($election["status"] !== "open" || strtotime($election["start_time"]) > $now) {
            $pdo->rollBack();
            return ["success" => false, "message" => "Voting is not currently open for this election."];
        }

        $eligible_stmt = $pdo->prepare("SELECT member_id FROM election_eligible_voters WHERE election_id = ? AND member_id = ?");
        $eligible_stmt->execute([(int)$election_id, (int)$member_id]);
        if (!$eligible_stmt->fetch()) {
            $pdo->rollBack();
            return ["success" => false, "message" => "You are not on this election's frozen eligible-voter list."];
        }

        $positions_stmt = $pdo->prepare("SELECT id FROM positions WHERE election_id = ? ORDER BY id");
        $positions_stmt->execute([(int)$election_id]);
        $position_ids = array_map("intval", $positions_stmt->fetchAll(PDO::FETCH_COLUMN));
        $normalized = [];
        foreach ($selections as $position_id => $candidate_id) {
            $position_id = filter_var($position_id, FILTER_VALIDATE_INT);
            $candidate_id = filter_var($candidate_id, FILTER_VALIDATE_INT);
            if (!$position_id || !$candidate_id || !in_array($position_id, $position_ids, true)) {
                $pdo->rollBack();
                return ["success" => false, "message" => "One or more selections are invalid."];
            }
            $candidate_stmt = $pdo->prepare("SELECT id FROM candidates WHERE id = ? AND position_id = ?");
            $candidate_stmt->execute([(int)$candidate_id, (int)$position_id]);
            if (!$candidate_stmt->fetch()) {
                $pdo->rollBack();
                return ["success" => false, "message" => "A selected candidate does not belong to the selected position."];
            }
            $normalized[(int)$position_id] = (int)$candidate_id;
        }
        if (!$position_ids || count($normalized) !== count($position_ids)) {
            $pdo->rollBack();
            return ["success" => false, "message" => "Select one candidate for every position before submitting your ballot."];
        }

        $ballot_group = bin2hex(random_bytes(32));
        $receipt_code = bin2hex(random_bytes(16));
        $receipt_insert = $pdo->prepare("INSERT INTO election_vote_receipts (election_id, member_id, idempotency_hash, receipt_code, receipt_hash) VALUES (?, ?, ?, ?, ?)");
        $receipt_insert->execute([(int)$election_id, (int)$member_id, $idempotency_hash, $receipt_code, hash("sha256", $receipt_code)]);
        $ballot_insert = $pdo->prepare("INSERT INTO ballots (election_id, position_id, candidate_id, anonymous_hash, ballot_group) VALUES (?, ?, ?, ?, ?)");
        $eligibility_insert = $pdo->prepare("INSERT INTO voting_eligibility (election_id, member_id, position_id, has_voted) VALUES (?, ?, ?, 1)");
        foreach ($normalized as $position_id => $candidate_id) {
            $ballot_insert->execute([(int)$election_id, $position_id, $candidate_id, bin2hex(random_bytes(32)), $ballot_group]);
            $eligibility_insert->execute([(int)$election_id, (int)$member_id, $position_id]);
        }
        $pdo->commit();
        return ["success" => true, "message" => "Your ballot has been recorded.", "receipt" => $receipt_code];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Unable to submit election ballot: " . $e->getMessage());
        return ["success" => false, "message" => "Your ballot could not be confirmed. Retry safely; duplicate submissions are prevented."];
    }
}
function setFlash($type, $msg) { $_SESSION["flash"] = ["type" => $type, "message" => $msg]; }
function getFlash() { if (isset($_SESSION["flash"])) { $f = $_SESSION["flash"]; unset($_SESSION["flash"]); return $f; } return null; }
function logAction($admin_id, $action) { global $pdo; $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, ip_address) VALUES (?, ?, ?)"); $stmt->execute([$admin_id, $action, $_SERVER["REMOTE_ADDR"]]); }

// ========== NOTIFICATIONS ==========
function addNotification($message, $type = "info") {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO notifications (message, type) VALUES (?, ?)");
        $stmt->execute([$message, $type]);
    } catch (PDOException $e) {
        error_log("Unable to create admin notification: " . $e->getMessage());
    }
}

function getUnreadNotifications() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM notifications WHERE is_read = 0 ORDER BY created_at DESC LIMIT 20");
    return $stmt->fetchAll();
}

function markNotificationsRead() {
    global $pdo;
    $pdo->exec("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
}

// ========== MEMBER NOTIFICATIONS ==========
function addMemberNotification($member_id, $message, $type = "info") {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO member_notifications (member_id, message, type) VALUES (?, ?, ?)");
        $stmt->execute([$member_id, $message, $type]);
    } catch (PDOException $e) {
        error_log("Unable to create member notification: " . $e->getMessage());
    }
}

function getMemberNotifications($member_id, $limit = 10) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM member_notifications WHERE member_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?");
    $stmt->bindValue(1, $member_id, PDO::PARAM_INT);
    $stmt->bindValue(2, max(0, (int)$limit), PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function markMemberNotificationRead($notification_id, $member_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE member_notifications SET is_read = 1 WHERE id = ? AND member_id = ?");
    $stmt->execute([$notification_id, $member_id]);
}

function markAllMemberNotificationsRead($member_id) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE member_notifications SET is_read = 1 WHERE member_id = ? AND is_read = 0");
    $stmt->execute([$member_id]);
}

function notifyAllMembers($message, $type = "info") {
    global $pdo;
    $members = $pdo->query("SELECT id FROM members WHERE status = 'approved'")->fetchAll();
    foreach ($members as $m) {
        addMemberNotification($m["id"], $message, $type);
    }
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
        $stats["total_votes_cast"] = (int)$pdo->query("SELECT COUNT(*) FROM election_vote_receipts")->fetchColumn();
        $stats["pending_applications"] = (int)$pdo->query("SELECT COUNT(*) FROM worship_applications WHERE status = 'pending'")->fetchColumn();
        $stats["leadership_count"] = (int)$pdo->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
    } catch (PDOException $e) {}
    return $stats;
}
?>