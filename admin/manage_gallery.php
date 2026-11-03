<?php
require_once "../config/functions.php";
if (!isAdminLoggedIn()) redirect("login.php");

$mediaFolders = [
    "photos" => ["path" => __DIR__ . "/../photo", "url" => "photo"],
    "videos" => ["path" => __DIR__ . "/../video", "url" => "video"]
];
$allowedMimeTypes = [
    "jpg" => ["image/jpeg", "photos"],
    "jpeg" => ["image/jpeg", "photos"],
    "png" => ["image/png", "photos"],
    "gif" => ["image/gif", "photos"],
    "webp" => ["image/webp", "photos"],
    "mp4" => ["video/mp4", "videos"],
    "webm" => ["video/webm", "videos"],
    "ogv" => ["video/ogg", "videos"]
];

function galleryMediaDetails($path, $allowedMimeTypes, $originalFileName = null) {
    $extension = strtolower(pathinfo($originalFileName ?? $path, PATHINFO_EXTENSION));
    if (!isset($allowedMimeTypes[$extension]) || !is_file($path)) {
        return null;
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($path);
    if (!in_array($mimeType, $allowedMimeTypes[$extension], true)) {
        return null;
    }

    return ["category" => $allowedMimeTypes[$extension][1], "extension" => $extension];
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!verifyCsrfToken($_POST["csrf_token"] ?? null)) {
        setFlash("danger", "Your session expired. Refresh the page and try again.");
        redirect("manage_gallery.php");
    }

    $action = $_POST["action"] ?? "";
    if ($action === "upload_file") {
        $upload = $_FILES["media_file"] ?? null;
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        if (!$upload || $upload["error"] !== UPLOAD_ERR_OK) {
            $uploadErrors = [
                UPLOAD_ERR_INI_SIZE => "The file exceeds the server's upload limit.",
                UPLOAD_ERR_FORM_SIZE => "The file exceeds the allowed upload size.",
                UPLOAD_ERR_PARTIAL => "The file upload was incomplete.",
                UPLOAD_ERR_NO_FILE => "Choose a photo or video to upload."
            ];
            setFlash("danger", $uploadErrors[$upload["error"] ?? UPLOAD_ERR_NO_FILE] ?? "The media upload failed.");
            redirect("manage_gallery.php");
        }
        if ($title === "" || strlen($title) > 255 || strlen($description) > 5000 || $upload["size"] > 256 * 1024 * 1024) {
            setFlash("danger", "Enter a title and description within the limits, and choose a file no larger than 256 MB.");
            redirect("manage_gallery.php");
        }

        $details = galleryMediaDetails($upload["tmp_name"], $allowedMimeTypes, $upload["name"]);
        if (!$details || !is_uploaded_file($upload["tmp_name"])) {
            setFlash("danger", "Use a valid JPEG, PNG, GIF, or WebP photo, or an MP4, WebM, or OGV video.");
            redirect("manage_gallery.php");
        }

        $targetDirectory = $mediaFolders[$details["category"]]["path"];
        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0755, true) && !is_dir($targetDirectory)) {
            setFlash("danger", "The media folder could not be created. Check server permissions.");
            redirect("manage_gallery.php");
        }

        $fileName = bin2hex(random_bytes(16)) . "." . $details["extension"];
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file($upload["tmp_name"], $targetPath)) {
            setFlash("danger", "The uploaded file could not be saved. Check server folder permissions.");
            redirect("manage_gallery.php");
        }

        $mediaUrl = $mediaFolders[$details["category"]]["url"] . "/" . rawurlencode($fileName);
        $stmt = $pdo->prepare("INSERT INTO gallery (title, category, media_url, description, is_published) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$title, $details["category"], $mediaUrl, $description]);
        setFlash("success", "Media uploaded and published to the public gallery.");
        redirect("manage_gallery.php");
    }

    if ($action === "add_url") {
        $title = trim($_POST["title"] ?? "");
        $category = $_POST["category"] ?? "";
        $mediaUrl = trim($_POST["media_url"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $parsedUrl = filter_var($mediaUrl, FILTER_VALIDATE_URL);
        $scheme = $parsedUrl ? strtolower((string)parse_url($mediaUrl, PHP_URL_SCHEME)) : "";
        if ($title === "" || strlen($title) > 255 || strlen($description) > 5000 || strlen($mediaUrl) > 255 || !in_array($category, ["photos", "videos"], true) || !$parsedUrl || !in_array($scheme, ["http", "https"], true)) {
            setFlash("danger", "Enter a title, valid photo or video category, and an HTTP or HTTPS media URL.");
            redirect("manage_gallery.php");
        }

        $stmt = $pdo->prepare("INSERT INTO gallery (title, category, media_url, description, is_published) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$title, $category, $mediaUrl, $description]);
        setFlash("success", "Media link added and published to the public gallery.");
        redirect("manage_gallery.php");
    }

    $galleryId = filter_var($_POST["gallery_id"] ?? null, FILTER_VALIDATE_INT);
    if (!$galleryId) {
        setFlash("danger", "Invalid gallery item.");
        redirect("manage_gallery.php");
    }

    if ($action === "update_item") {
        $title = trim($_POST["title"] ?? "");
        $description = trim($_POST["description"] ?? "");
        if ($title === "" || strlen($title) > 255 || strlen($description) > 5000) {
            setFlash("danger", "Enter a title and description within the allowed limits.");
            redirect("manage_gallery.php");
        }
        $stmt = $pdo->prepare("UPDATE gallery SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $description, $galleryId]);
        setFlash("success", "Gallery item details updated.");
        redirect("manage_gallery.php");
    }

    if ($action === "toggle_visibility") {
        $stmt = $pdo->prepare("UPDATE gallery SET is_published = NOT is_published WHERE id = ?");
        $stmt->execute([$galleryId]);
        setFlash("success", "Gallery visibility updated. The media file was kept in its folder.");
        redirect("manage_gallery.php");
    }

    setFlash("danger", "Unknown gallery action.");
    redirect("manage_gallery.php");
}

// Register existing files once so the supplied photo/ and video/ folders appear in the gallery.
foreach ($mediaFolders as $folder) {
    if (!is_dir($folder["path"])) {
        continue;
    }
    $files = scandir($folder["path"]);
    if ($files === false) {
        error_log("Unable to read gallery media directory: " . $folder["path"]);
        continue;
    }
    foreach ($files as $fileName) {
        if ($fileName === "." || $fileName === "..") {
            continue;
        }
        $filePath = $folder["path"] . DIRECTORY_SEPARATOR . $fileName;
        $details = galleryMediaDetails($filePath, $allowedMimeTypes);
        if (!$details) {
            continue;
        }
        $mediaUrl = $folder["url"] . "/" . rawurlencode($fileName);
        $existing = $pdo->prepare("SELECT id FROM gallery WHERE media_url = ? LIMIT 1");
        $existing->execute([$mediaUrl]);
        if (!$existing->fetchColumn()) {
            $title = pathinfo($fileName, PATHINFO_FILENAME);
            $insert = $pdo->prepare("INSERT INTO gallery (title, category, media_url, description, is_published) VALUES (?, ?, ?, ?, 1)");
            $insert->execute([$title, $details["category"], $mediaUrl, ""]);
        }
    }
}

$gallery = $pdo->query("SELECT * FROM gallery ORDER BY uploaded_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Gallery - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;}.media-preview{width:120px;height:76px;object-fit:cover;background:#111;border-radius:8px;}</style>
</head>
<body>
<?php include "../navbar.php"; ?>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><i class="fas fa-images text-danger me-2"></i>Worship Gallery Manager</h2>
            <p class="text-muted small mb-0">Manage photos and videos from the local media folders or add a media link.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-dark rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i> Dashboard</a>
    </div>

    <?php $f = getFlash(); if ($f): ?>
        <div class="alert alert-<?php echo htmlspecialchars($f["type"]); ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($f["message"]); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-upload text-danger me-2"></i>Upload local media</h5>
                <p class="small text-muted">Photos are saved in <code>photo/</code>; videos are saved in <code>video/</code>. Supported formats: JPEG, PNG, GIF, WebP, MP4, WebM, and OGV (up to 256 MB).</p>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <input type="hidden" name="action" value="upload_file">
                    <div class="mb-3">
                        <label for="upload-title" class="form-label fw-semibold">Title</label>
                        <input id="upload-title" type="text" name="title" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="media-file" class="form-label fw-semibold">Photo or video file</label>
                        <input id="media-file" type="file" name="media_file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/ogg" required>
                    </div>
                    <div class="mb-3">
                        <label for="upload-description" class="form-label fw-semibold">Description</label>
                        <textarea id="upload-description" name="description" class="form-control" rows="2" maxlength="5000"></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Upload and publish</button>
                </form>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                <h5 class="fw-bold"><i class="fas fa-link text-primary me-2"></i>Add media link</h5>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                    <input type="hidden" name="action" value="add_url">
                    <div class="mb-3">
                        <label for="link-title" class="form-label fw-semibold">Title</label>
                        <input id="link-title" type="text" name="title" class="form-control" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="link-category" class="form-label fw-semibold">Media type</label>
                        <select id="link-category" name="category" class="form-select" required>
                            <option value="photos">Photo</option>
                            <option value="videos">Video</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="media-url" class="form-label fw-semibold">HTTP(S) media URL</label>
                        <input id="media-url" type="url" name="media_url" class="form-control" placeholder="https://" required>
                    </div>
                    <div class="mb-3">
                        <label for="link-description" class="form-label fw-semibold">Description</label>
                        <textarea id="link-description" name="description" class="form-control" rows="2" maxlength="5000"></textarea>
                    </div>
                    <button type="submit" class="btn btn-outline-primary rounded-pill px-4 fw-bold">Add and publish link</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 bg-white p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr><th>Preview</th><th>Title</th><th>Type</th><th>Visibility</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($gallery as $g): ?>
                        <tr>
                            <td>
                                <?php if ($g["category"] === "videos"): ?>
                                    <video class="media-preview" controls preload="metadata" aria-label="<?php echo htmlspecialchars($g["title"]); ?>">
                                        <source src="<?php echo htmlspecialchars($g["media_url"]); ?>">
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo htmlspecialchars($g["media_url"]); ?>" class="media-preview" alt="<?php echo htmlspecialchars($g["title"]); ?>" loading="lazy">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($g["title"]); ?></strong>
                                <div class="small text-muted"><?php echo htmlspecialchars($g["description"] ?? ""); ?></div>
                            </td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars(strtoupper($g["category"])); ?></span></td>
                            <td>
                                <span class="badge <?php echo !empty($g["is_published"]) ? "bg-success" : "bg-secondary"; ?>">
                                    <?php echo !empty($g["is_published"]) ? "Published" : "Hidden"; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#edit-<?php echo (int)$g["id"]; ?>">
                                        <i class="fas fa-pen"></i> Edit
                                    </button>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                        <input type="hidden" name="action" value="toggle_visibility">
                                        <input type="hidden" name="gallery_id" value="<?php echo (int)$g["id"]; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill">
                                            <?php echo !empty($g["is_published"]) ? "Hide" : "Publish"; ?>
                                        </button>
                                    </form>
                                </div>
                                <div class="modal fade" id="edit-<?php echo (int)$g["id"]; ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content rounded-4 border-0">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit gallery item</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrfToken()); ?>">
                                                    <input type="hidden" name="action" value="update_item">
                                                    <input type="hidden" name="gallery_id" value="<?php echo (int)$g["id"]; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Title</label>
                                                        <input type="text" name="title" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($g["title"]); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold">Description</label>
                                                        <textarea name="description" class="form-control" rows="3" maxlength="5000"><?php echo htmlspecialchars($g["description"] ?? ""); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary rounded-pill">Save changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$gallery): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No media is registered yet. Existing files in photo/ and video/ are added automatically.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "../footer.php"; ?>
</body>
</html>
