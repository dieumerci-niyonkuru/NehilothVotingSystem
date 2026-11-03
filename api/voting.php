<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Idempotency-Key");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../config/functions.php";

$json_input = json_decode(file_get_contents("php://input"), true);
if (is_array($json_input)) {
    $_POST = array_merge($_POST, $json_input);
}
$action = $_GET["action"] ?? $_POST["action"] ?? "get_elections";

switch ($action) {
    case "get_elections":
        $elections = $pdo->query("
            SELECT id, title, description, start_time, end_time, status, results_published, published_at
            FROM elections
            WHERE status NOT IN ('draft', 'cancelled')
            ORDER BY start_time DESC
        ")->fetchAll();
        foreach ($elections as &$election) {
            $election["status"] = getElectionStatus((int)$election["id"]);
        }
        unset($election);
        echo json_encode(["status" => "success", "elections" => $elections]);
        break;

    case "get_status":
        $election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
        if (!$election_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "A valid election_id is required."]);
            break;
        }
        $stmt = $pdo->prepare("SELECT id, title, start_time, end_time, results_published FROM elections WHERE id = ? AND status <> 'draft'");
        $stmt->execute([(int)$election_id]);
        $election = $stmt->fetch();
        if (!$election) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Election not found."]);
            break;
        }
        $election["status"] = getElectionStatus((int)$election_id);
        echo json_encode(["status" => "success", "election" => $election]);
        break;

    case "get_candidates":
        $election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
        if (!$election_id) {
            $election_id = (int)$pdo->query("
                SELECT id FROM elections
                WHERE status IN ('upcoming', 'open', 'paused')
                ORDER BY start_time DESC LIMIT 1
            ")->fetchColumn();
        }
        $election_stmt = $pdo->prepare("SELECT id, status FROM elections WHERE id = ? AND status <> 'draft'");
        $election_stmt->execute([(int)$election_id]);
        $election = $election_stmt->fetch();
        if (!$election) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Published election not found."]);
            break;
        }
        $status = getElectionStatus((int)$election_id);
        if ($status === "cancelled") {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Election not found."]);
            break;
        }

        $positions = $pdo->prepare("SELECT id, title, description FROM positions WHERE election_id = ? ORDER BY id");
        $positions->execute([(int)$election_id]);
        $data = [];
        foreach ($positions->fetchAll() as $position) {
            $candidates = $pdo->prepare("
                SELECT id, name, photo_url, vision_statement, manifesto, bio
                FROM candidates WHERE position_id = ? ORDER BY id
            ");
            $candidates->execute([(int)$position["id"]]);
            $data[] = [
                "position_id" => (int)$position["id"],
                "position_title" => $position["title"],
                "description" => $position["description"],
                "candidates" => $candidates->fetchAll()
            ];
        }
        echo json_encode([
            "status" => "success",
            "election_id" => (int)$election_id,
            "election_status" => $status,
            "data" => $data
        ]);
        break;

    case "cast_vote":
        if (!isMemberLoggedIn()) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Member authentication is required."]);
            break;
        }
        $member_id = (int)$_SESSION["member_id"];
        $election_id = filter_var($_POST["election_id"] ?? null, FILTER_VALIDATE_INT);
        $selections = $_POST["selections"] ?? null;
        if (!is_array($selections) && isset($_POST["position_id"], $_POST["candidate_id"])) {
            $selections = [(int)$_POST["position_id"] => (int)$_POST["candidate_id"]];
        }
        $idempotency_key = $_SERVER["HTTP_IDEMPOTENCY_KEY"] ?? $_POST["idempotency_key"] ?? "";
        if (!$election_id || !is_array($selections)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Election and complete ballot selections are required."]);
            break;
        }

        $result = submitElectionBallot($member_id, (int)$election_id, $selections, $idempotency_key);
        if (empty($result["success"])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => $result["message"]]);
            break;
        }
        if (empty($result["duplicate"])) {
            addMemberNotification($member_id, "Your ballot was recorded for Election #$election_id.", "success");
        }
        echo json_encode([
            "status" => "success",
            "message" => $result["message"],
            "duplicate" => !empty($result["duplicate"]),
            "receipt" => $result["receipt"] ?? null
        ]);
        break;

    case "get_receipt":
        if (!isMemberLoggedIn()) {
            http_response_code(401);
            echo json_encode(["status" => "error", "message" => "Member authentication is required."]);
            break;
        }
        $election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
        if (!$election_id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "A valid election_id is required."]);
            break;
        }
        $receipt_stmt = $pdo->prepare("
            SELECT receipt_code, receipt_hash, submitted_at
            FROM election_vote_receipts
            WHERE election_id = ? AND member_id = ?
        ");
        $receipt_stmt->execute([(int)$election_id, (int)$_SESSION["member_id"]]);
        $receipt = $receipt_stmt->fetch();
        if (!$receipt) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "No submitted ballot was found for this election."]);
            break;
        }
        if (!is_string($receipt["receipt_code"]) || !hash_equals($receipt["receipt_hash"], hash("sha256", $receipt["receipt_code"]))) {
            error_log("Election receipt integrity check failed for member " . (int)$_SESSION["member_id"] . " and election " . (int)$election_id);
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "The ballot receipt could not be verified. Contact an election administrator."]);
            break;
        }
        echo json_encode([
            "status" => "success",
            "submitted" => true,
            "receipt" => $receipt["receipt_code"],
            "submitted_at" => $receipt["submitted_at"]
        ]);
        break;

    case "get_results":
        $election_id = filter_var($_GET["election_id"] ?? null, FILTER_VALIDATE_INT);
        if (!$election_id) {
            $published_elections = $pdo->query("
                SELECT id FROM elections
                WHERE results_published = 1 AND status NOT IN ('draft', 'cancelled')
                ORDER BY end_time DESC
            ")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($published_elections as $published_election_id) {
                if (getElectionStatus((int)$published_election_id) === "closed") {
                    $election_id = (int)$published_election_id;
                    break;
                }
            }
        }
        $election_stmt = $pdo->prepare("SELECT id, status, results_published FROM elections WHERE id = ?");
        $election_stmt->execute([(int)$election_id]);
        $election = $election_stmt->fetch();
        if ($election) {
            $election["status"] = getElectionStatus((int)$election_id);
        }
        if (!$election || $election["status"] !== "closed" || !(bool)$election["results_published"]) {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Official results are not published for this election."]);
            break;
        }

        $participation = $pdo->prepare("SELECT COUNT(*) FROM election_vote_receipts WHERE election_id = ?");
        $participation->execute([(int)$election_id]);
        $total_voters = (int)$participation->fetchColumn();
        $eligible_stmt = $pdo->prepare("SELECT COUNT(*) FROM election_eligible_voters WHERE election_id = ?");
        $eligible_stmt->execute([(int)$election_id]);
        $eligible_voters = (int)$eligible_stmt->fetchColumn();
        $turnout = $eligible_voters > 0 ? round(($total_voters / $eligible_voters) * 100, 1) : 0;

        $positions = $pdo->prepare("SELECT id, title FROM positions WHERE election_id = ? ORDER BY id");
        $positions->execute([(int)$election_id]);
        $results = [];
        foreach ($positions->fetchAll() as $position) {
            $total_stmt = $pdo->prepare("SELECT COUNT(*) FROM ballots WHERE election_id = ? AND position_id = ?");
            $total_stmt->execute([(int)$election_id, (int)$position["id"]]);
            $position_total = (int)$total_stmt->fetchColumn();

            $candidates = $pdo->prepare("
                SELECT c.id, c.name, c.photo_url, COUNT(b.id) AS votes_count
                FROM candidates c
                LEFT JOIN ballots b ON b.candidate_id = c.id
                    AND b.election_id = ? AND b.position_id = c.position_id
                WHERE c.position_id = ?
                GROUP BY c.id, c.name, c.photo_url
                ORDER BY votes_count DESC, c.name ASC
            ");
            $candidates->execute([(int)$election_id, (int)$position["id"]]);
            $candidate_results = [];
            foreach ($candidates->fetchAll() as $candidate) {
                $votes = (int)$candidate["votes_count"];
                $candidate_results[] = [
                    "candidate_id" => (int)$candidate["id"],
                    "name" => $candidate["name"],
                    "photo_url" => $candidate["photo_url"],
                    "votes" => $votes,
                    "percentage" => $position_total > 0 ? round(($votes / $position_total) * 100, 1) : 0
                ];
            }
            $results[] = [
                "position_title" => $position["title"],
                "total_votes_position" => $position_total,
                "candidates" => $candidate_results
            ];
        }
        echo json_encode([
            "status" => "success",
            "eligible_voters" => $eligible_voters,
            "total_voters" => $total_voters,
            "turnout_percentage" => $turnout,
            "results" => $results
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid voting action."]);
        break;
}
?>
