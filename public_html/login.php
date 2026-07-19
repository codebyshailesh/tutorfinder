<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = get_authenticated_user();
if ($user) {
    header('Location: /');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — TutorFinder</title>
<style>
    :root { --primary: #2f6fed; --bg: #f7f9fc; --text: #1b1f27; --muted: #5b6472; --border: #e2e6ee; }
    * { box-sizing: border-box; }
    body {
        margin: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg);
        color: var(--text);
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
    }
    .card {
        background: #fff;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 36px;
        width: 100%;
        max-width: 380px;
    }
    h1 { font-size: 1.4rem; margin-bottom: 4px; }
    p.sub { color: var(--muted); margin-top: 0; margin-bottom: 24px; font-size: 0.9rem; }
    label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
    input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid var(--border);
        border-radius: 6px;
        margin-bottom: 16px;
        font-size: 0.95rem;
    }
    button {
        width: 100%;
        padding: 12px;
        background: var(--primary);
        color: #fff;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
    }
    .error { color: #c0392b; font-size: 0.9rem; margin-bottom: 12px; display: none; }
    .footer-link { text-align: center; margin-top: 18px; font-size: 0.9rem; color: var(--muted); }
    .footer-link a { color: var(--primary); text-decoration: none; }
</style>
</head>
<body>

<div class="card">
    <h1>Welcome back</h1>
    <p class="sub">Log in to your TutorFinder account.</p>

    <div class="error" id="errorMsg"></div>

    <form id="loginForm">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required autocomplete="email">

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <button type="submit">Log in</button>
    </form>

    <div class="footer-link">
        Don't have an account? <a href="/register.php">Sign up</a>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const errorEl = document.getElementById('errorMsg');
    errorEl.style.display = 'none';

    const payload = {
        email: document.getElementById('email').value,
        password: document.getElementById('password').value,
    };

    try {
        const res = await fetch('/api/auth/login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (!res.ok) {
            errorEl.textContent = data.error || 'Login failed.';
            errorEl.style.display = 'block';
            return;
        }

        const params = new URLSearchParams(window.location.search);
        const redirectTo = params.get('redirect');
        window.location.href = redirectTo ? decodeURIComponent(redirectTo) : '/dashboard.php';
    } catch (err) {
        errorEl.textContent = 'Something went wrong. Please try again.';
        errorEl.style.display = 'block';
    }
});
</script>

</body>
</html>
