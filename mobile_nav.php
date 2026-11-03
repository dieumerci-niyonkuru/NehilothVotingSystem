<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Persistent Mobile Bottom Navigation Bar -->
<div class="d-lg-none fixed-bottom bg-dark text-white border-top border-secondary py-2 shadow-lg" style="z-index: 1050;">
    <div class="container d-flex justify-content-around text-center">
        <a href="/index.php" class="text-decoration-none <?php echo $current_page == 'index.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-home fa-lg d-block mb-1"></i>Home
        </a>
        <a href="/announcements.php" class="text-decoration-none <?php echo $current_page == 'announcements.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-bullhorn fa-lg d-block mb-1"></i>News
        </a>
        <a href="/schedule.php" class="text-decoration-none <?php echo $current_page == 'schedule.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-calendar-alt fa-lg d-block mb-1"></i>Schedule
        </a>
        <a href="/leadership.php" class="text-decoration-none <?php echo $current_page == 'leadership.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-users-cog fa-lg d-block mb-1"></i>Leadership
        </a>
        <a href="/voting.php" class="text-decoration-none <?php echo $current_page == 'voting.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-vote-yea fa-lg d-block mb-1"></i>Voting
        </a>
        <a href="/profile.php" class="text-decoration-none <?php echo $current_page == 'profile.php' ? 'text-warning fw-bold' : 'text-light'; ?> small">
            <i class="fas fa-user-circle fa-lg d-block mb-1"></i>Profile
        </a>
    </div>
</div>
