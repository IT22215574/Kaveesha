# Kaveesha — PHP + Tailwind demo

A minimal PHP project with a Tailwind-styled login page and classic embedded PHP handling the form submit in the same file. Authentication uses a MySQL users table.

Placement
- Put this folder in your XAMPP `htdocs` directory (already created here): `/Applications/XAMPP/xamppfiles/htdocs/Kaveesha`.

Setup
1. Start MySQL in XAMPP.
2. Import the database schema:
	- Open phpMyAdmin (http://localhost/phpmyadmin/) and run `setup.sql` from this folder, or use the MySQL CLI.
3. Adjust DB credentials in `config.php` if needed (defaults: host 127.0.0.1, db `Electronice`, user `root`, empty password).
4. Start Apache in XAMPP.
5. Open http://localhost/Kaveesha/ in your browser.

Credentials (demo)
- Username: `yoma electronics`
- Password: `password`

Files
- `index.php` — redirects to login or dashboard
- `login.php` — Tailwind-styled mobile-only login form and POST processing (same file)
- `dashboard.php` — protected page (requires login)
- `admin.php` — admin-only panel to create users and view recent users
- `add_listing.php` — admin-only page to add a listing for a selected user
- `logout.php` — clears session
- `config.php` — session start and DB connection (PDO) + credential check
- `setup.sql` — MySQL schema and seed admin user
- `assets/css/styles.css` — small custom CSS

Notes
- Seed users created by `setup.sql`:
	- Admin: name `yoma electronics`, mobile `0775604833` (admin privileges)
	- Regular user: name `Demo User`, mobile `0712345678`
- Login is by mobile number only (no password) per project requirement.
- Admin can access `http://localhost/Kaveesha/admin.php` to create users by name and mobile number.

New: Listings (optional)
- The admin Users table now allows clicking a user (ID or Name) to open `add_listing.php?user_id=...`.
- To store listings, run the updated `setup.sql` which now includes a `listings` table.
- If you haven't created the `listings` table yet, the Add Listing page will show a helpful message.
