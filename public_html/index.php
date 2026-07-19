<?php
require_once __DIR__ . '/includes/auth_middleware.php';
$user = get_authenticated_user();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutorFinder — Find a tutor nearby</title>
    <style>
        :root {
            --primary: #2f6fed;
            --primary-dark: #1f4fc2;
            --bg: #f7f9fc;
            --text: #1b1f27;
            --muted: #5b6472;
            --border: #e2e6ee;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: #fff;
            border-bottom: 1px solid var(--border);
        }

        .logo {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary);
        }

        nav a {
            margin-left: 16px;
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
        }

        nav a.button {
            background: var(--primary);
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
        }

        .hero {
            max-width: 720px;
            margin: 80px auto 40px;
            text-align: center;
            padding: 0 20px;
        }

        .hero h1 {
            font-size: 2.4rem;
            margin-bottom: 12px;
        }

        .hero p {
            color: var(--muted);
            font-size: 1.1rem;
            margin-bottom: 28px;
        }

        .cta {
            display: flex;
            justify-content: center;
            gap: 14px;
        }

        .cta a {
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .cta .primary {
            background: var(--primary);
            color: #fff;
        }

        .cta .secondary {
            background: #fff;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .features {
            max-width: 900px;
            margin: 60px auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 24px;
            padding: 0 20px;
        }

        .feature {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 24px;
        }

        .feature h3 {
            margin-top: 0;
        }

        .feature p {
            color: var(--muted);
            font-size: 0.95rem;
        }

        footer {
            text-align: center;
            color: var(--muted);
            padding: 30px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>

    <header>
        <div class="logo">TutorFinder</div>
        <nav>
            <?php if ($user): ?>
                <span>Hi, <?= htmlspecialchars($user['name']) ?></span>
                <a href="/dashboard.php" class="button">Dashboard</a>
                <a href="#" id="logoutLink">Log out</a>
            <?php else: ?>
                <a href="/login.php">Log in</a>
                <a href="/register.php" class="button">Sign up</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <h1>Find the right tutor, right in your neighbourhood.</h1>
        <p>TutorFinder connects students with nearby tutors for one-off sessions or ongoing lessons — no
            need to search citywide when help is often just a few streets away.</p>
        <div class="cta">
            <?php if ($user): ?>
                <a href="/browse.php" class="primary">Find a tutor</a>
                <a href="/dashboard.php" class="secondary">Go to dashboard</a>
            <?php else: ?>
                <a href="/register.php" class="primary">Get started</a>
                <a href="/login.php" class="secondary">I already have an account</a>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <div class="feature">
            <h3>For students</h3>
            <p>Search tutors by subject and location, compare rates, and message tutors directly to book a session.</p>
        </div>
        <div class="feature">
            <h3>For tutors</h3>
            <p>Create a profile listing your subjects, rate, and bio so nearby students can find and book you.</p>
        </div>
        <div class="feature">
            <h3>Simple & secure</h3>
            <p>Accounts are protected with hashed passwords and secure, expiring session tokens.</p>
        </div>
    </section>

    <footer>
        &copy; <?= date('Y') ?> TutorFinder. This is a demo/template application.
    </footer>

    <script>
        document.getElementById('logoutLink')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('/api/auth/logout.php', {
                method: 'POST'
            });
            window.location.reload();
        });
    </script>

</body>

</html>