<!-- Nehiloth Worship Footer with Social Media -->
<footer class="bg-dark text-white pt-4 pb-3 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="/logo/logo.jpg" alt="" width="44" height="44" class="rounded-circle bg-white p-1">
                    <h5 class="mb-0">Nehiloth Worship Team</h5>
                </div>
                <p class="small">Worship, music, fellowship, and service at Rwanda Polytechnic, Tumba College.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white text-decoration-none">Home</a></li>
                    <li><a href="register.php" class="text-white text-decoration-none">Register</a></li>
                    <li><a href="login.php" class="text-white text-decoration-none">Member Login</a></li>
                    <li><a href="admin/login.php" class="text-white text-decoration-none">Admin</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-3">
                <h5>Follow Us</h5>
                <a href="https://www.instagram.com/nehilothworshipteam/" class="text-white me-3 fs-4" target="_blank" rel="noopener noreferrer" aria-label="Nehiloth on Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.youtube.com/@NEHILOTH_WORSHIPTEAM" class="text-white me-3 fs-4" target="_blank" rel="noopener noreferrer" aria-label="Nehiloth on YouTube"><i class="fab fa-youtube"></i></a>
                <a href="https://www.tiktok.com/@nehilothworshipteam" class="text-white fs-4" target="_blank" rel="noopener noreferrer" aria-label="Nehiloth on TikTok"><i class="fab fa-tiktok"></i></a>
                <p class="mt-3 small">&copy; 2026 Nehiloth Worship. All rights reserved.</p>
            </div>
        </div>
    </div>
</footer>
<script>
if ("serviceWorker" in navigator) {
    window.addEventListener("load", () => navigator.serviceWorker.register("/sw.js").catch(error => console.error("PWA service worker registration failed:", error)));
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>