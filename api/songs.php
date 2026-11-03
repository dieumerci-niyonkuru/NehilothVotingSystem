<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

require_once __DIR__ . "/../config/functions.php";

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    $category = trim($_GET["category"] ?? "");
    $songs = getPublicSongRequests($category !== "" ? $category : null, 200);
    echo json_encode(["status" => "success", "songs" => $songs]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Use GET to list songs or POST to submit a song request."]);
    exit();
}

if (!isMemberLoggedIn()) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Member sign-in is required to submit from the mobile API."]);
    exit();
}

$csrfToken = $_SERVER["HTTP_X_CSRF_TOKEN"] ?? $_POST["csrf_token"] ?? "";
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "A valid CSRF token is required. Sign in again to refresh your session token."]);
    exit();
}

$json = json_decode(file_get_contents("php://input"), true);
$input = is_array($json) ? array_merge($_POST, $json) : $_POST;
$title = trim($input["song_title"] ?? "");
$artist = trim($input["artist"] ?? "");
$category = $input["genre_category"] ?? "Worship";
$key = trim($input["key_note"] ?? "");
$mediaUrl = trim($input["media_url"] ?? "");
$lyricsUrl = trim($input["lyrics_link"] ?? "");
$notes = trim($input["notes"] ?? "");
$audioPath = null;

try {
    if ($title === "" || strlen($title) > 255 || strlen($artist) > 100 || strlen($key) > 20 ||
        strlen($notes) > 5000 || !in_array($category, ["Worship", "Praise", "Hymn", "Special"], true) ||
        ($mediaUrl !== "" && !isSafeMediaUrl($mediaUrl)) || ($lyricsUrl !== "" && !isSafeMediaUrl($lyricsUrl))) {
        throw new RuntimeException("Check the song title, category, and HTTP(S) links.");
    }

    if (!empty($_FILES["audio_file"]["name"])) {
        if ($mediaUrl !== "") {
            throw new RuntimeException("Provide an audio link or an audio file, not both.");
        }
        $audioPath = storeSongAudio($_FILES["audio_file"]);
    }

    $memberName = $_SESSION["member_name"] ?? "";
    if (!addSongRequest((int)$_SESSION["member_id"], $memberName, $title, $artist, $category, $key, $lyricsUrl, $notes, $audioPath ?: ($mediaUrl ?: null))) {
        if ($audioPath) {
            $storedFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $audioPath);
            if (is_file($storedFile) && !unlink($storedFile)) {
                error_log("Unable to remove unused uploaded song file: " . $storedFile);
            }
        }
        throw new RuntimeException("The song request could not be saved.");
    }
    http_response_code(201);
    echo json_encode(["status" => "success", "message" => "Song submitted for review."]);
} catch (RuntimeException $e) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
