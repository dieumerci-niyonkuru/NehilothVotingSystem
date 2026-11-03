<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/functions.php";
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="/index.php">
            <i class="fas fa-church text-light me-2"></i>Nehiloth Worship
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="/index.php"><i class="fas fa-home text-info me-1"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="/songs.php"><i class="fas fa-music text-warning me-1"></i> Song Requests</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="/join_team.php"><i class="fas fa-user-plus text-success me-1"></i> Join Worship Team</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold" href="/leadership.php"><i class="fas fa-users-cog text-danger me-1"></i> Leadership</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if (isMemberLoggedIn()): ?>
                    <a href="/member/dashboard.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fas fa-user-circle me-1"></i> Member Portal
                    </a>
                    <a href="/logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                <?php elseif (isAdminLoggedIn()): ?>
                    <a href="/admin/dashboard.php" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">
                        <i class="fas fa-user-shield me-1"></i> Admin Dashboard
                    </a>
                    <a href="/admin/logout.php" class="btn btn-danger btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="/register.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                    <a href="/login.php" class="btn btn-primary btn-sm rounded-pill px-3">
                        <i class="fas fa-sign-in-alt me-1"></i> Member Login
                    </a>
                    <a href="/admin/login.php" class="btn btn-outline-warning btn-sm rounded-pill px-3">
                        <i class="fas fa-shield-alt me-1"></i> Admin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
