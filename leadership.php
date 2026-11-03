<?php
require_once "config/functions.php";
$leaders = getLeadershipTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leadership & Committee Directory - Nehiloth Worship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; }
        .hero-banner { background: linear-gradient(135deg, #141e30 0%, #243b55 100%); color: white; padding: 40px 0; }
        .leader-card { border-radius: 20px; border: none; transition: transform 0.3s, box-shadow 0.3s; overflow: hidden; }
        .leader-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.12) !important; }
        .leader-img { height: 220px; object-fit: cover; width: 100%; }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-5 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-users-cog text-warning me-2"></i>Nehiloth Leadership & Committee</h1>
        <p class="lead mb-0">Meet the leadership team guiding the spiritual, musical, and technical ministry of Nehiloth Worship.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        <?php if (empty($leaders)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-user-friends fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No leadership entries found.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($leaders as $l): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card leader-card shadow-sm h-100 bg-white">
                        <img src="<?php echo !empty($l['photo_url']) ? htmlspecialchars($l['photo_url']) : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=300'; ?>" class="card-img-top leader-img" alt="<?php echo htmlspecialchars($l['name']); ?>">
                        <div class="card-body p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="badge bg-primary rounded-pill px-3 py-2 mb-2"><?php echo htmlspecialchars($l['role']); ?></span>
                                <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($l['name']); ?></h4>
                                <p class="text-muted small mb-3"><?php echo htmlspecialchars($l['bio']); ?></p>
                            </div>
                            <div class="border-top pt-3 text-secondary small">
                                <?php if (!empty($l['email'])): ?>
                                    <div class="mb-1"><i class="fas fa-envelope me-2 text-primary"></i><?php echo htmlspecialchars($l['email']); ?></div>
                                <?php endif; ?>
                                <?php if (!empty($l['phone'])): ?>
                                    <div><i class="fas fa-phone me-2 text-success"></i><?php echo htmlspecialchars($l['phone']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="text-center mt-5 p-4 bg-white rounded-4 shadow-sm">
        <h4 class="fw-bold text-dark">Interested in serving alongside leadership?</h4>
        <p class="text-muted mb-3">We welcome singers, musicians, tech, and media volunteers!</p>
        <a href="join_team.php" class="btn btn-success btn-lg rounded-pill px-5 fw-bold"><i class="fas fa-user-plus me-2"></i>Apply to Join Worship Team</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
