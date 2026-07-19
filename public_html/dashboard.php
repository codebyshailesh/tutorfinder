<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_web_auth();
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — TutorFinder</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="app-main">
    <h1 class="page-title">Welcome back, <?= htmlspecialchars($user['name']) ?></h1>
    <p class="page-sub">
        <?= $user['role'] === 'tutor'
            ? 'Here are your booking requests and upcoming sessions.'
            : 'Here are your bookings. Looking for more help? Find a tutor below.' ?>
    </p>

    <?php if ($user['role'] === 'student'): ?>
        <div class="card">
            <strong>New to TutorFinder?</strong>
            <p style="color: var(--muted); margin: 6px 0 12px;">Search tutors by subject or neighbourhood and send a booking request.</p>
            <a href="/browse.php" class="btn">Find a tutor</a>
        </div>
    <?php endif; ?>

    <div id="errorMsg" class="error-msg"></div>

    <h2 style="font-size:1.2rem;">
        <?= $user['role'] === 'tutor' ? 'Booking requests' : 'Your bookings' ?>
    </h2>
    <div id="bookingsList"></div>
</main>

<script>
const role = <?= json_encode($user['role']) ?>;

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

function formatDate(iso) {
    const d = new Date(iso.replace(' ', 'T'));
    return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

async function loadBookings() {
    const list = document.getElementById('bookingsList');
    try {
        const res = await fetch('/api/bookings/list.php');
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Failed to load bookings');

        if (data.bookings.length === 0) {
            list.innerHTML = '<div class="card empty-state">No bookings yet.</div>';
            return;
        }

        list.innerHTML = data.bookings.map(b => {
            const otherLabel = role === 'tutor' ? 'Student' : 'Tutor';
            let actions = `<a href="/messages.php?with=${b.other_user_id}" class="btn secondary">Message</a>`;

            if (role === 'tutor' && b.status === 'pending') {
                actions += ` <button onclick="updateBooking(${b.id}, 'accept')">Accept</button>
                             <button class="danger" onclick="updateBooking(${b.id}, 'decline')">Decline</button>`;
            }
            if (role === 'tutor' && b.status === 'accepted') {
                actions += ` <button onclick="updateBooking(${b.id}, 'complete')">Mark complete</button>`;
            }
            if (b.status === 'pending' || b.status === 'accepted') {
                actions += ` <button class="danger" onclick="updateBooking(${b.id}, 'cancel')">Cancel</button>`;
            }

            return `<div class="card">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                    <div>
                        <strong>${escapeHtml(b.subject)}</strong>
                        <span class="badge ${b.status}">${escapeHtml(b.status)}</span>
                        <div style="color:var(--muted); font-size:0.9rem; margin-top:4px;">
                            ${otherLabel}: ${escapeHtml(b.other_user_name)} &middot;
                            ${formatDate(b.requested_time)} &middot; ${b.duration_minutes} min
                        </div>
                        ${b.note ? `<div style="margin-top:6px; font-size:0.9rem;">${escapeHtml(b.note)}</div>` : ''}
                    </div>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">${actions}</div>
                </div>
            </div>`;
        }).join('');
    } catch (err) {
        const errorEl = document.getElementById('errorMsg');
        errorEl.textContent = err.message;
        errorEl.style.display = 'block';
    }
}

async function updateBooking(bookingId, action) {
    try {
        const res = await fetch('/api/bookings/update.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ booking_id: bookingId, action }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Action failed');
        loadBookings();
    } catch (err) {
        alert(err.message);
    }
}

loadBookings();
</script>

</body>
</html>
