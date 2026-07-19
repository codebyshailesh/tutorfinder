<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = require_web_auth();
$activePage = 'browse';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Find a tutor — TutorFinder</title>
<link rel="stylesheet" href="/assets/app.css">
</head>
<body>

<?php include __DIR__ . '/includes/nav.php'; ?>

<main class="app-main">
    <h1 class="page-title">Find a tutor</h1>
    <p class="page-sub">Search by subject or neighbourhood.</p>

    <div class="card">
        <form id="searchForm" class="form-row" style="margin-bottom:0;">
            <div>
                <label for="subject">Subject</label>
                <input type="text" id="subject" placeholder="e.g. Math, Guitar, English">
            </div>
            <div>
                <label for="location">Location</label>
                <input type="text" id="location" placeholder="e.g. Baneshwor">
            </div>
            <div style="flex: 0 0 auto; align-self: flex-end; margin-bottom: 16px;">
                <button type="submit">Search</button>
            </div>
        </form>
    </div>

    <div id="errorMsg" class="error-msg"></div>
    <div id="results" class="grid"></div>
</main>

<script>
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str ?? '';
    return div.innerHTML;
}

async function searchTutors() {
    const subject = document.getElementById('subject').value.trim();
    const location = document.getElementById('location').value.trim();
    const results = document.getElementById('results');
    const errorEl = document.getElementById('errorMsg');
    errorEl.style.display = 'none';

    const params = new URLSearchParams();
    if (subject) params.set('subject', subject);
    if (location) params.set('location', location);

    try {
        const res = await fetch('/api/tutors/list.php?' + params.toString());
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Search failed');

        if (data.tutors.length === 0) {
            results.innerHTML = '<div class="card empty-state">No tutors found. Try a different search.</div>';
            return;
        }

        results.innerHTML = data.tutors.map(t => {
            const subjects = (t.subjects || '').split(',').map(s => s.trim()).filter(Boolean)
                .map(s => `<span>${escapeHtml(s)}</span>`).join('');
            const rate = t.hourly_rate ? `<span class="rate">Rs. ${Number(t.hourly_rate).toFixed(0)}/hr</span>` : '';

            return `<div class="card tutor-card">
                <h3>${escapeHtml(t.name)}</h3>
                <div class="meta">${escapeHtml(t.location || 'Location not specified')}</div>
                <div class="subjects">${subjects}</div>
                <p style="color:var(--muted); font-size:0.9rem;">${escapeHtml((t.bio || '').slice(0, 100))}${(t.bio || '').length > 100 ? '…' : ''}</p>
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    ${rate}
                    <a href="/tutor.php?id=${t.id}" class="btn">View profile</a>
                </div>
            </div>`;
        }).join('');
    } catch (err) {
        errorEl.textContent = err.message;
        errorEl.style.display = 'block';
    }
}

document.getElementById('searchForm').addEventListener('submit', (e) => {
    e.preventDefault();
    searchTutors();
});

searchTutors();
</script>

</body>
</html>
