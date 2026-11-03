<?php
require_once "config/functions.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['applicant_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role_applied']);
    $exp = sanitize($_POST['experience']);
    $avail = sanitize($_POST['availability']);
    $notes = sanitize($_POST['notes']);
    $member_id = isMemberLoggedIn() ? $_SESSION['member_id'] : null;

    if (!empty($name) && !empty($email) && !empty($phone) && !empty($role)) {
        if (submitWorshipApplication($member_id, $name, $email, $phone, $role, $exp, $avail, $notes)) {
            setFlash("success", "Application submitted successfully! Our Worship Team Leaders will contact you for auditions & rehearsals.");
        } else {
            setFlash("danger", "Failed to submit application. Please try again.");
        }
    } else {
        setFlash("warning", "Please fill in all required fields.");
    }
    redirect("join_team.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Nehiloth Worship Team - Application</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background: #f4f6f9; min-height: 100vh; }
        .hero-banner { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 40px 0; }
        .form-card { border-radius: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    </style>
</head>
<body>
<?php include "navbar.php"; ?>

<div class="hero-banner text-center mb-4 shadow-sm">
    <div class="container">
        <h1 class="fw-bold"><i class="fas fa-hands-helping me-2"></i>Join Nehiloth Worship Team</h1>
        <p class="lead mb-0">Use your God-given gifts in vocals, instruments, sound engineering, media, or hospitality!</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php $f = getFlash(); if ($f): ?>
                <div class="alert alert-<?php echo $f['type']; ?> alert-dismissible fade show shadow-sm" role="alert">
                    <?php echo $f['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card form-card bg-white p-4 p-md-5">
                <div class="text-center mb-4">
                    <i class="fas fa-microphone-alt fa-3x text-success mb-2"></i>
                    <h3 class="fw-bold text-dark">Worship Team Application Form</h3>
                    <p class="text-muted">Fill out this form to join our choir, band, technical or media ministry.</p>
                </div>

                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="applicant_name" class="form-control" required placeholder="John Doe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" required placeholder="john@example.com">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Phone Number / WhatsApp <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" required placeholder="0788000000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Ministry Role / Talent <span class="text-danger">*</span></label>
                            <select name="role_applied" class="form-select" required>
                                <option value="" disabled selected>Select Role</option>
                                <option value="Vocalist - Soprano">Vocalist - Soprano</option>
                                <option value="Vocalist - Alto">Vocalist - Alto</option>
                                <option value="Vocalist - Tenor">Vocalist - Tenor</option>
                                <option value="Vocalist - Bass">Vocalist - Bass</option>
                                <option value="Keyboardist / Pianist">Keyboardist / Pianist</option>
                                <option value="Acoustic / Electric Guitarist">Acoustic / Electric Guitarist</option>
                                <option value="Bassist">Bassist</option>
                                <option value="Drummer / Percussionist">Drummer / Percussionist</option>
                                <option value="Sound Engineer">Sound & Audio Engineer</option>
                                <option value="Media & Projection">Media / Visuals / Projection</option>
                                <option value="Ushering & Hospitality">Ushering & Hospitality</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Experience Level <span class="text-danger">*</span></label>
                            <select name="experience" class="form-select" required>
                                <option value="Beginner / Enthusiast">Beginner / Enthusiast</option>
                                <option value="Intermediate (1-3 years)">Intermediate (1-3 years)</option>
                                <option value="Experienced (3+ years)">Experienced (3+ years)</option>
                                <option value="Ministry Leader / Professional">Ministry Leader / Professional</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Availability <span class="text-danger">*</span></label>
                            <select name="availability" class="form-select" required>
                                <option value="Sundays & Midweek Rehearsals">Sundays & Midweek Rehearsals</option>
                                <option value="Sundays Only">Sundays Only</option>
                                <option value="Midweek Rehearsals Only">Midweek Rehearsals Only</option>
                                <option value="Special Events & Concerts">Special Events & Concerts</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Why do you want to join Nehiloth Worship Team?</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Share a brief testimony or motivation..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg w-100 rounded-pill fw-bold shadow-sm">
                        <i class="fas fa-paper-plane me-2"></i> Submit Application
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include "footer.php"; ?>
</body>
</html>
