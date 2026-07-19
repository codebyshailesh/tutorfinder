<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_web_auth();
$activePage = 'browse';
$tutorId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutor profile — TutorFinder</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="app-main">
    <div id="errorMsg" class="error-msg"></div>
    <div id="profileContainer"></div>
</main>

<script>
const tutorId = <?= json_encode($tutorId) ?>;
const currentUserRole = <?= json_encode($user['role']) ?>;
const currentUserId = <?= json_encode($user['id']) ?>;

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

async function loadTutor() {
    const container = document.getElementById('profileContainer');
    const errorEl = document.getElementById('errorMsg');

    if (!tutorId) {
        errorEl.textContent = 'No tutor specified.';
        errorEl.style.display = 'block';
        return;
    }

    try {
        const res = await fetch('/api/tutors/get.php?id=' + tutorId);
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Could not load tutor');

        const t = data.tutor;
        const subjects = (t.subjects || '').split(',').map(s => s.trim()).filter(Boolean)
            .map(s => `<span>${escapeHtml(s)}</span>`).join('');
        const rate = t.hourly_rate ? `Rs. ${Number(t.hourly_rate).toFixed(0)}/hr` : 'Rate not specified';

        let bookingSection = '';
        if (currentUserRole === 'student') {
            bookingSection = `
                <div class="card">
                    <h2 style="font-size:1.1rem; margin-top:0;">Request a session</h2>
                    <div id="bookingError" class="error-msg"></div>
                    <div id="bookingSuccess" class="success-msg"></div>
                    <form id="bookingForm">
                        <div class="form-row">
                            <div>
                                <label for="bookSubject">Subject</label>
                                <input type="text" id="bookSubject" required placeholder="e.g. Algebra">
                            </div>
                            <div>
                                <label for="bookDuration">Duration (minutes)</label>
                                <input type="number" id="bookDuration" value="60" min="15" max="480" step="15">
                            </div>
                        </div>
                        <label for="bookTime">Preferred date & time</label>
                        <input type="datetime-local" id="bookTime" required>
                        <label for="bookNote">Note to tutor (optional)</label>
                        <textarea id="bookNote" placeholder="What would you like help with?"></textarea>
                        <button type="submit">Send booking request</button>
                    </form>
                </div>`;
        } else if (currentUserId === t.id) {
            bookingSection = `<div class="card"><a href="/profile.php" class="btn">Edit your profile</a></div>`;
        } else {
            bookingSection = `<div class="card"><a href="/messages.php?with=${t.id}" class="btn">Message this tutor</a></div>`;
        }

        container.innerHTML = `
            <div class="card">
                <h1 class="page-title" style="margin-bottom:2px;">${escapeHtml(t.name)}</h1>
                <div class="meta" style="margin-bottom:10px;">${escapeHtml(t.location || 'Location not specified')} &middot; ${rate}</div>
                <div class="subjects">${subjects}</div>
                <p>${escapeHtml(t.bio || 'This tutor has not added a bio yet.')}</p>
                ${currentUserId !== t.id ? `<a href="/messages.php?with=${t.id}" class="btn secondary">Message</a>` : ''}
            </div>
            ${bookingSection}
        `;

        document.getElementById('bookingForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errEl = document.getElementById('bookingError');
            const okEl = document.getElementById('bookingSuccess');
            errEl.style.display = 'none';
            okEl.style.display = 'none';

            const payload = {
                tutor_id: tutorId,
                subject: document.getElementById('bookSubject').value,
                requested_time: document.getElementById('bookTime').value,
                duration_minutes: Number(document.getElementById('bookDuration').value),
                note: document.getElementById('bookNote').value,
            };

            try {
                const res = await fetch('/api/bookings/create.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.error || 'Booking request failed');

                okEl.textContent = 'Booking request sent! You can track it from your dashboard.';
                okEl.style.display = 'block';
                document.getElementById('bookingForm').reset();
            } catch (err) {
                errEl.textContent = err.message;
                errEl.style.display = 'block';
            }
        });
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.style.display = 'block';
    }
}

loadTutor();
</script>

</body>
</html>
