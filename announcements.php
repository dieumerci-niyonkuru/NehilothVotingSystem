<?php
require_once "config/functions.php";
$announcements = $pdo->query("SELECT * FROM announcements ORDER BY is_pinned DESC, created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Announcements - Nehiloth Worship Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.hero-banner{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);color:white;padding:35px 0;}.news-card{border-radius:18px;border:none;transition:transform 0.2s;}.news-card:hover{transform:translateY(-3px);}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-bullhorn text-warning me-2"></i>News & Announcements</h1>
        <p class="lead mb-0">Official updates from the Nehiloth Worship Team leadership</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        <?php if (empty($announcements)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-newspaper fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No announcements published yet.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $a): ?>
                <div class="col-md-8">
                    <div class="card news-card shadow-sm p-4 bg-white border-start border-4 <?php echo $a['is_pinned'] ? 'border-warning' : 'border-primary'; ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <?php if ($a['is_pinned']): ?>
                                    <span class="badge bg-warning text-dark me-2"><i class="fas fa-thumbtack me-1"></i>PINNED</span>
                                <?php endif; ?>
                                <?php if ($a['is_important']): ?>
                                    <span class="badge bg-danger me-2"><i class="fas fa-exclamation-circle me-1"></i>IMPORTANT</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><i class="fas fa-clock me-1"></i><?php echo date('M d, Y - H:i', strtotime($a['created_at'])); ?></small>
                        </div>

                        <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($a['title']); ?></h4>
                        <p class="text-secondary mb-3"><?php echo nl2br(htmlspecialchars($a['content'])); ?></p>

                        <?php if (!empty($a['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($a['image_url']); ?>" class="img-fluid rounded-3 mb-3" style="max-height:300px;object-fit:cover;width:100%;">
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <small class="text-primary fw-bold"><i class="fas fa-user-shield me-1"></i><?php echo htmlspecialchars($a['author_name']); ?></small>
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
