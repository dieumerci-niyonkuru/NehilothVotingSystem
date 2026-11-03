<?php
require_once "config/functions.php";

$category = isset($_GET['category']) ? sanitize($_GET['category']) : null;
$sql = "SELECT * FROM gallery WHERE is_published = 1";
$params = [];
if ($category) {
    $sql .= " AND category = ?";
    $params[] = $category;
}
$sql .= " ORDER BY uploaded_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$gallery = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worship Gallery - Nehiloth Worship Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.hero-banner{background:linear-gradient(135deg,#e95440 0%,#2a5298 100%);color:white;padding:35px 0;}.gal-img{height:220px;object-fit:cover;width:100%;border-radius:15px 15px 0 0;}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-images text-warning me-2"></i>Worship Gallery</h1>
        <p class="lead mb-0">Photos and video highlights from Nehiloth Worship Team services and concerts</p>
    </div>
</div>

<div class="container mb-5">
    <div class="d-flex justify-content-center gap-2 mb-4">
        <a href="gallery.php" class="btn btn-outline-dark rounded-pill px-4 <?php echo empty($category)?'active':''; ?>">All Media</a>
        <a href="gallery.php?category=photos" class="btn btn-outline-primary rounded-pill px-4 <?php echo $category=='photos'?'active':''; ?>"><i class="fas fa-camera me-1"></i> Photos</a>
        <a href="gallery.php?category=videos" class="btn btn-outline-danger rounded-pill px-4 <?php echo $category=='videos'?'active':''; ?>"><i class="fas fa-video me-1"></i> Videos</a>
    </div>

    <div class="row g-4">
        <?php if (empty($gallery)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-photo-video fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No gallery media uploaded yet.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($gallery as $g): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white h-100 overflow-hidden">
                        <?php if ($g['category'] === 'videos'): ?>
                            <video class="gal-img bg-dark" controls preload="metadata" aria-label="<?php echo htmlspecialchars($g['title']); ?>">
                                <source src="<?php echo htmlspecialchars($g['media_url']); ?>">
                                Your browser does not support video playback.
                            </video>
                        <?php else: ?>
                            <img src="<?php echo htmlspecialchars($g['media_url']); ?>" class="gal-img" alt="<?php echo htmlspecialchars($g['title']); ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="card-body p-3">
                            <span class="badge bg-secondary mb-2"><?php echo strtoupper($g['category']); ?></span>
                            <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($g['title']); ?></h5>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($g['description']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
