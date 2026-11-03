<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/functions.php";

$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'get_elections');

$json_input = json_decode(file_get_contents("php://input"), true);
if (is_array($json_input)) {
    $_POST = array_merge($_POST, $json_input);
    if (isset($json_input['action'])) $action = $json_input['action'];
}

switch ($action) {
    case 'get_elections':
        $elections = $pdo->query("SELECT * FROM elections ORDER BY created_at DESC")->fetchAll();
        echo json_encode(["status" => "success", "elections" => $elections]);
        break;

    case 'get_candidates':
        $election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
        if ($election_id == 0) {
            $e = $pdo->query("SELECT id FROM elections WHERE status = 'open' LIMIT 1")->fetch();
            $election_id = $e ? $e['id'] : 0;
        }

        $positions = $pdo->prepare("SELECT * FROM positions WHERE election_id = ?");
        $positions->execute([$election_id]);
        $pos_list = $positions->fetchAll();

        $data = [];
        foreach ($pos_list as $p) {
            $cands = $pdo->prepare("SELECT id, name, photo_url, vision_statement, manifesto, bio FROM candidates WHERE position_id = ?");
            $cands->execute([$p['id']]);
            $cand_list = $cands->fetchAll();

            $data[] = [
                "position_id" => $p['id'],
                "position_title" => $p['title'],
                "description" => $p['description'],
                "candidates" => $cand_list
            ];
        }

        echo json_encode(["status" => "success", "election_id" => $election_id, "data" => $data]);
        break;

    case 'cast_vote':
        $member_id = isset($_POST['member_id']) ? (int)$_POST['member_id'] : (isMemberLoggedIn() ? $_SESSION['member_id'] : 0);
        $election_id = isset($_POST['election_id']) ? (int)$_POST['election_id'] : 0;
        $position_id = isset($_POST['position_id']) ? (int)$_POST['position_id'] : 0;
        $candidate_id = isset($_POST['candidate_id']) ? (int)$_POST['candidate_id'] : 0;

        if ($member_id == 0 || $election_id == 0 || $position_id == 0 || $candidate_id == 0) {
            echo json_encode(["status" => "error", "message" => "Missing required voting parameters."]);
            exit();
        }

        // Check if election is OPEN
        $stmt = $pdo->prepare("SELECT status, start_time, end_time FROM elections WHERE id = ?");
        $stmt->execute([$election_id]);
        $el = $stmt->fetch();

        if (!$el || $el['status'] !== 'open') {
            echo json_encode(["status" => "error", "message" => "Voting is not open for this election."]);
            exit();
        }

        // Check duplicate vote in eligibility table
        $chk = $pdo->prepare("SELECT id FROM voting_eligibility WHERE election_id = ? AND member_id = ? AND position_id = ?");
        $chk->execute([$election_id, $member_id, $position_id]);
        if ($chk->fetch()) {
            echo json_encode(["status" => "error", "message" => "Your vote has already been recorded for this position."]);
            exit();
        }

        // Secret Ballot Transaction:
        // 1. Record voter eligibility flag
        // 2. Record anonymous ballot with secret hash
        try {
            $pdo->beginTransaction();

            $pdo->prepare("INSERT INTO voting_eligibility (election_id, member_id, position_id, has_voted) VALUES (?, ?, ?, 1)")
                ->execute([$election_id, $member_id, $position_id]);

            $anon_hash = hash('sha256', $member_id . '_' . $election_id . '_' . time() . '_' . mt_rand());

            $pdo->prepare("INSERT INTO ballots (election_id, position_id, candidate_id, anonymous_hash) VALUES (?, ?, ?, ?)")
                ->execute([$election_id, $position_id, $candidate_id, $anon_hash]);

            $pdo->commit();

            addMemberNotification($member_id, "Your vote was securely recorded in Election #$election_id", "success");

            echo json_encode(["status" => "success", "message" => "Your vote has been securely recorded."]);
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(["status" => "error", "message" => "Failed to record vote. Please try again."]);
        }
        break;

    case 'get_results':
        $election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : 0;
        if ($election_id == 0) {
            $e = $pdo->query("SELECT id FROM elections ORDER BY id DESC LIMIT 1")->fetch();
            $election_id = $e ? $e['id'] : 0;
        }

        $total_voters = (int)$pdo->query("SELECT COUNT(DISTINCT member_id) FROM voting_eligibility WHERE election_id = $election_id")->fetchColumn();
        $total_members = (int)$pdo->query("SELECT COUNT(*) FROM members WHERE status = 'approved'")->fetchColumn();
        $turnout = $total_members > 0 ? round(($total_voters / $total_members) * 100, 1) : 0;

        $positions = $pdo->prepare("SELECT * FROM positions WHERE election_id = ?");
        $positions->execute([$election_id]);
        $pos_list = $positions->fetchAll();

        $results = [];
        foreach ($pos_list as $p) {
            $pos_votes = (int)$pdo->prepare("SELECT COUNT(*) FROM ballots WHERE position_id = ?")->execute([$p['id']]) ? (int)$pdo->query("SELECT COUNT(*) FROM ballots WHERE position_id = " . $p['id'])->fetchColumn() : 0;

            $cands = $pdo->prepare("SELECT c.*, COUNT(b.id) as votes_count FROM candidates c LEFT JOIN ballots b ON c.id = b.candidate_id WHERE c.position_id = ? GROUP BY c.id ORDER BY votes_count DESC");
            $cands->execute([$p['id']]);
            $cand_results = $cands->fetchAll();

            $cand_data = [];
            foreach ($cand_results as $cr) {
                $pct = $pos_votes > 0 ? round(($cr['votes_count'] / $pos_votes) * 100, 1) : 0;
                $cand_data[] = [
                    "candidate_id" => $cr['id'],
                    "name" => $cr['name'],
                    "photo_url" => $cr['photo_url'],
                    "votes" => (int)$cr['votes_count'],
                    "percentage" => $pct
                ];
            }

            $results[] = [
                "position_title" => $p['title'],
                "total_votes_position" => $pos_votes,
                "candidates" => $cand_data
            ];
        }

        echo json_encode([
            "status" => "success",
            "turnout_percentage" => $turnout,
            "total_voters" => $total_voters,
            "results" => $results
        ]);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid voting action."]);
        break;
}
?>