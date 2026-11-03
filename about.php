<?php require_once "config/functions.php"; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Nehiloth Worship Team</title>
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="theme-color" content="#172b55">
    <link rel="stylesheet" href="/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>body{background:#f4f6f9;min-height:100vh;padding-bottom:70px;}.hero-banner{background:linear-gradient(135deg,#1e3c72 0%,#2a5298 100%);color:white;padding:45px 0;}</style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-5 shadow-sm">
    <div class="container">
        <img src="/logo/logo.jpg" alt="Nehiloth Worship Team logo" width="100" height="100" class="rounded-circle bg-white p-1 mb-3">
        <h1 class="fw-bold">About Nehiloth Worship Team</h1>
        <p class="lead mb-0">A worship community at Rwanda Polytechnic, Tumba College</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Organization Overview -->
            <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm mb-4">
                <h2 class="fw-bold text-dark mb-3"><i class="fas fa-bullseye text-primary me-2"></i>Our Purpose & Identity</h2>
                <p class="lead text-secondary">
                    "Nehiloth Worship Team — one family, one purpose, one voice in worship."
                </p>
                <p class="text-secondary">
                    Nehiloth Worship Team is a community of singers, musicians, worship leaders, and media volunteers serving through music and fellowship.
                    We bring people together through genuine praise, spiritual growth, musical excellence, and servant leadership. Our digital home keeps the team connected with song recordings, rehearsal plans, team news, and leadership.
                </p>
            </div>

            <!-- Vision & Mission Grid -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <i class="fas fa-eye fa-3x text-warning mb-3"></i>
                        <h4 class="fw-bold text-dark">Our Vision</h4>
                        <p class="text-secondary mb-0">
                            To build a vibrant, spiritually empowered worship community that inspires heartfelt worship, musical excellence, and servant leadership.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <i class="fas fa-hands-helping fa-3x text-success mb-3"></i>
                        <h4 class="fw-bold text-dark">Our Mission</h4>
                        <p class="text-secondary mb-0">
                            Equipping members through vocal and instrumental training, preparing songs together, serving the community through worship, and supporting accountable leadership.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Contact & Campus Details -->
            <div class="bg-white rounded-4 p-4 p-md-5 shadow-sm text-center">
                <h4 class="fw-bold text-dark mb-3"><i class="fas fa-university me-2 text-danger"></i>Rwanda Polytechnic - Tumba College Campus</h4>
                <p class="text-muted mb-2"><i class="fas fa-map-marker-alt text-danger me-2"></i>RP Tumba College, Rulindo District, Northern Province, Rwanda</p>
                <p class="text-muted mb-0"><i class="fas fa-envelope text-primary me-2"></i>Contact Leadership: <a href="mailto:info@nelilothworship.org">info@nelilothworship.org</a></p>
            </div>
        </div>
    </div>
</div>

<?php include "mobile_nav.php"; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
