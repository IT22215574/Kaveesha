# Kaveesha — PHP + Tailwind demo

A minimal PHP project with a Tailwind-styled login page and classic embedded PHP handling the form submit in the same file. Authentication uses a MySQL users table.

Placement
- Put this folder in your XAMPP `htdocs` directory (already created here): `/Applications/XAMPP/xamppfiles/htdocs/Kaveesha`.

Setup
1. Start MySQL in XAMPP.
2. Import the database schema:
	- Open phpMyAdmin (http://localhost/phpmyadmin/) and run `setup.sql` from this folder, or use the MySQL CLI.
3. Adjust DB credentials in `config.php` if needed (defaults: host 127.0.0.1, db `kaveesha_db`, user `root`, empty password).
4. Start Apache in XAMPP.
5. Open http://localhost/Kaveesha/ in your browser.

Credentials (demo)
- Username: `admin`
- Password: `password`

Files
- `index.php` — redirects to login or dashboard
- `login.php` — Tailwind-styled mobile-only login form and POST processing (same file)
- `dashboard.php` — protected page (requires login)
- `logout.php` — clears session
- `config.php` — session start and DB connection (PDO) + credential check
- `setup.sql` — MySQL schema and seed admin user
- `assets/css/styles.css` — small custom CSS

Notes
- Seed user created by `setup.sql`: username `admin`, mobile `0712345678`.
- Login is by mobile number only (no password) per project requirement.
