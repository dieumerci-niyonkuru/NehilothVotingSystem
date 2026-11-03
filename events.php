<?php
require_once "config/functions.php";
$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC, event_time ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worship Events & Concerts - Nehiloth Worship Team</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.hero-banner{background:linear-gradient(135deg,#00b09b 0%,#96c93d 100%);color:white;padding:35px 0;}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-calendar-check me-2"></i>Upcoming Events & Worship Concerts</h1>
        <p class="lead mb-0">Join Nehiloth Worship Team at Rwanda Polytechnic, Tumba College for worship events</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 justify-content-center">
        <?php if (empty($events)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4 class="text-secondary">No upcoming events scheduled at the moment.</h4>
            </div>
        <?php else: ?>
            <?php foreach ($events as $ev): ?>
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <div class="row align-items-center">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <img src="<?php echo htmlspecialchars($ev['image_url']); ?>" class="img-fluid rounded-4" style="height:160px;object-fit:cover;width:100%;">
                            </div>
                            <div class="col-md-8">
                                <span class="badge bg-success rounded-pill px-3 py-2 mb-2"><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($ev['category']); ?></span>
                                <h4 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($ev['event_title']); ?></h4>
                                <p class="text-muted small mb-2"><i class="fas fa-calendar-day text-primary me-2"></i>Date: <strong><?php echo date('F d, Y', strtotime($ev['event_date'])) . ' at ' . date('H:i', strtotime($ev['event_time'])); ?></strong></p>
                                <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i>Location: <strong><?php echo htmlspecialchars($ev['location']); ?></strong></p>
                                <p class="text-secondary mb-0"><?php echo htmlspecialchars($ev['description']); ?></p>
                            </div>
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
