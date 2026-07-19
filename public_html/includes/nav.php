<?php
/**
 * Shared nav partial. Expects $user (from require_web_auth()) and optional
 * $activePage string ('dashboard' | 'browse' | 'messages' | 'profile') to
 * highlight the current page.
 */
$activePage = $activePage ?? '';
?>
<header class="app-header">
    <a href="/dashboard.php" class="logo">TutorFinder</a>
    <nav class="app-nav">
        <a href="/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="/browse.php" class="<?= $activePage === 'browse' ? 'active' : '' ?>">Find a tutor</a>
        <a href="/messages.php" class="<?= $activePage === 'messages' ? 'active' : '' ?>">Messages</a>
        <a href="/profile.php" class="<?= $activePage === 'profile' ? 'active' : '' ?>">Profile</a>
        <a href="#" id="navLogout">Log out</a>
    </nav>
</header>
<script>
document.getElementById('navLogout')?.addEventListener('click', async (e) => {
    e.preventDefault();
    await fetch('/api/auth/logout.php', { method: 'POST' });
    window.location.href = '/';
});
</script>
