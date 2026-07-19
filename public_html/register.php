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
    <title>Sign up — TutorFinder</title>
    <style>
        :root {
            --primary: #2f6fed;
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
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 30px 0;
        }

        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 36px;
            width: 100%;
            max-width: 420px;
        }

        h1 {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        p.sub {
            color: var(--muted);
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 0.9rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 0.95rem;
            font-family: inherit;
        }

        textarea {
            resize: vertical;
            min-height: 70px;
        }

        .role-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
        }

        .role-toggle label {
            flex: 1;
            text-align: center;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 10px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 0;
        }

        .role-toggle input {
            display: none;
        }

        .role-toggle input:checked+span {
            color: var(--primary);
            font-weight: 700;
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

        .error {
            color: #c0392b;
            font-size: 0.9rem;
            margin-bottom: 12px;
            display: none;
        }

        .footer-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .footer-link a {
            color: var(--primary);
            text-decoration: none;
        }

        #tutorFields {
            display: none;
        }
    </style>
</head>

<body>

    <div class="card">
        <h1>Create your account</h1>
        <p class="sub">Join TutorFinder as a student or a tutor.</p>

        <div class="error" id="errorMsg"></div>

        <form id="registerForm">
            <label>I am a...</label>
            <div class="role-toggle">
                <label>
                    <input type="radio" name="role" value="student" checked>
                    <span>Student</span>
                </label>
                <label>
                    <input type="radio" name="role" value="tutor">
                    <span>Tutor</span>
                </label>
            </div>

            <label for="name">Full name</label>
            <input type="text" id="name" name="name" required autocomplete="name">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">

            <div id="tutorFields">
                <label for="subjects">Subjects you teach (comma-separated)</label>
                <input type="text" id="subjects" name="subjects" placeholder="Math, Physics, English">

                <label for="hourly_rate">Hourly rate</label>
                <input type="number" id="hourly_rate" name="hourly_rate" min="0" step="0.01">

                <label for="location">Neighbourhood / location</label>
                <input type="text" id="location" name="location" placeholder="e.g. Baneshwor, Kathmandu">

                <label for="bio">Short bio</label>
                <textarea id="bio" name="bio" placeholder="Tell students a bit about your experience"></textarea>
            </div>

            <button type="submit">Sign up</button>
        </form>

        <div class="footer-link">
            Already have an account? <a href="/login.php">Log in</a>
        </div>
    </div>

    <script>
        const roleRadios = document.querySelectorAll('input[name="role"]');
        const tutorFields = document.getElementById('tutorFields');

        roleRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                tutorFields.style.display = radio.value === 'tutor' && radio.checked ? 'block' : 'none';
            });
        });

        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errorEl = document.getElementById('errorMsg');
            errorEl.style.display = 'none';

            const role = document.querySelector('input[name="role"]:checked').value;

            const payload = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                role: role,
                subjects: document.getElementById('subjects').value,
                hourly_rate: document.getElementById('hourly_rate').value,
                location: document.getElementById('location').value,
                bio: document.getElementById('bio').value,
            };

            try {
                const res = await fetch('/api/auth/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();

                if (!res.ok) {
                    errorEl.textContent = data.error || 'Registration failed.';
                    errorEl.style.display = 'block';
                    return;
                }

                window.location.href = '/dashboard.php';
            } catch (err) {
                errorEl.textContent = 'Something went wrong. Please try again.';
                errorEl.style.display = 'block';
            }
        });
    </script>

</body>

</html>