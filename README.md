# TutorFinder

TutorFinder is a small web application for connecting students with nearby tutors. People can register as a **student** or a **tutor**, log in, and (in a future version) search for tutors by subject and location to book short lessons — instead of trawling citywide listings for help with a single subject.

This project follows the same structure and auth approach as the SaathiSaaman template it was built from.

## What this project includes

- A landing page for the platform
- User registration and login pages (student or tutor role)
- API endpoints for authentication (register, login, logout)
- A MySQL schema for storing users, tutor profiles, and auth tokens

## Project structure

- `public_html/index.php` — landing page
- `public_html/login.php` — login form
- `public_html/register.php` — registration form (student or tutor)
- `public_html/api/auth/login.php` — login API endpoint
- `public_html/api/auth/register.php` — registration API endpoint
- `public_html/api/auth/logout.php` — logout API endpoint
- `public_html/includes/db.php` — database connection config
- `public_html/includes/auth_middleware.php` — token generation and authentication helpers
- `database/schema.sql` — database schema

## Requirements

- PHP 8+
- MySQL or MariaDB
- A web server such as Apache, Nginx, or a hosting platform like InfinityFree

## Setup

1. Create a MySQL database.
2. Import the SQL file:

   ```bash
   mysql -u YOUR_USER -p YOUR_DATABASE < database/schema.sql
   ```

3. Update the database credentials in `public_html/includes/db.php`.
4. Serve the project from the `public_html` folder, or point your web server document root to `public_html`.
5. Open the app in your browser:
   - Home: `/`
   - Register: `/register.php`
   - Login: `/login.php`

## Authentication notes

The authentication flow uses:

- password hashing with PHP's `password_hash`
- cookie-based auth tokens stored in the database (`auth_tokens` table)
- token expiration after 7 days
- a `logout.php` endpoint that invalidates the token and clears the cookie

## Data model

- **users** — id, name, email, password_hash, role (`student` or `tutor`), created_at
- **tutor_profiles** — subjects, bio, hourly_rate, location, linked to a tutor's user id
- **auth_tokens** — token, expiry, linked to a user id

## Notes

This repository currently contains the authentication foundation and a basic tutor-profile field set at registration. Search/filter of tutors, booking, messaging, and reviews are not yet implemented.

## License

This project is provided as a simple demo/example application/template.
