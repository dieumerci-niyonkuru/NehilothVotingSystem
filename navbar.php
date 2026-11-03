<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . "/config/functions.php";
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-warning" href="/index.php">
            <i class="fas fa-church text-light me-2"></i>NELILOTH WORSHIP
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='index.php'?'active text-warning':''; ?>" href="/index.php"><i class="fas fa-home me-1"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='announcements.php'?'active text-warning':''; ?>" href="/announcements.php"><i class="fas fa-bullhorn me-1"></i> News</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='schedule.php'?'active text-warning':''; ?>" href="/schedule.php"><i class="fas fa-calendar-alt me-1"></i> Schedule</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='leadership.php'?'active text-warning':''; ?>" href="/leadership.php"><i class="fas fa-users-cog me-1"></i> Leadership</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='voting.php'?'active text-warning':''; ?>" href="/voting.php"><i class="fas fa-vote-yea text-info me-1"></i> Voting</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='songs.php'?'active text-warning':''; ?>" href="/songs.php"><i class="fas fa-music me-1"></i> Songs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='gallery.php'?'active text-warning':''; ?>" href="/gallery.php"><i class="fas fa-images me-1"></i> Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold <?php echo $current_page=='about.php'?'active text-warning':''; ?>" href="/about.php"><i class="fas fa-info-circle me-1"></i> About CEP</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <?php if (isMemberLoggedIn()): ?>
                    <a href="/profile.php" class="btn btn-outline-info btn-sm rounded-pill px-3">
                        <i class="fas fa-user-circle me-1"></i> Profile
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
                    <a href="/login.php" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                        <i class="fas fa-sign-in-alt me-1"></i> Member Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
