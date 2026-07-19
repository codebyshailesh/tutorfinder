<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_web_auth();
$activePage = 'profile';

$tutorProfile = null;
if ($user['role'] === 'tutor') {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT subjects, bio, hourly_rate, location FROM tutor_profiles WHERE user_id = :uid');
    $stmt->execute(['uid' => $user['id']]);
    $tutorProfile = $stmt->fetch() ?: ['subjects' => '', 'bio' => '', 'hourly_rate' => '', 'location' => ''];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Your profile — TutorFinder</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="app-main" style="max-width:560px;">
    <h1 class="page-title">Your profile</h1>
    <p class="page-sub">Signed in as <?= htmlspecialchars($user['email']) ?> (<?= htmlspecialchars($user['role']) ?>)</p>

    <div id="errorMsg" class="error-msg"></div>
    <div id="successMsg" class="success-msg"></div>

    <div class="card">
        <form id="profileForm">
            <label for="name">Full name</label>
            <input type="text" id="name" value="<?= htmlspecialchars($user['name']) ?>" required>

            <?php if ($user['role'] === 'tutor'): ?>
                <label for="subjects">Subjects you teach (comma-separated)</label>
                <input type="text" id="subjects" value="<?= htmlspecialchars($tutorProfile['subjects'] ?? '') ?>" placeholder="Math, Physics, English">

                <label for="hourly_rate">Hourly rate</label>
                <input type="number" id="hourly_rate" min="0" step="0.01" value="<?= htmlspecialchars((string) ($tutorProfile['hourly_rate'] ?? '')) ?>">

                <label for="location">Neighbourhood / location</label>
                <input type="text" id="location" value="<?= htmlspecialchars($tutorProfile['location'] ?? '') ?>" placeholder="e.g. Baneshwor, Kathmandu">

                <label for="bio">Bio</label>
                <textarea id="bio" placeholder="Tell students about your experience"><?= htmlspecialchars($tutorProfile['bio'] ?? '') ?></textarea>
            <?php endif; ?>

            <button type="submit">Save changes</button>
        </form>
    </div>
</main>

<script>
document.getElementById('profileForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('errorMsg');
    const successEl = document.getElementById('successMsg');
    errorEl.style.display = 'none';
    successEl.style.display = 'none';

    const payload = { name: document.getElementById('name').value };

    const subjectsEl = document.getElementById('subjects');
    if (subjectsEl) {
        payload.subjects = subjectsEl.value;
        payload.hourly_rate = document.getElementById('hourly_rate').value;
        payload.location = document.getElementById('location').value;
        payload.bio = document.getElementById('bio').value;
    }

    try {
        const res = await fetch('/api/profile/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Update failed');

        successEl.textContent = 'Profile updated.';
        successEl.style.display = 'block';
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.style.display = 'block';
    }
});
</script>

</body>
</html>
