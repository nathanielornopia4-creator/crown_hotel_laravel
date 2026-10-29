# Crown Hotel Management System — Laravel Version

This is the Laravel migration of the original Crown Hotel Management System. The original HTML/CSS/JavaScript interface and room image assets are retained, while the PHP JSON API is replaced with a Laravel controller, Laravel routing, Laravel sessions/CSRF protection, database query builder, and a Laravel migration.

## Requirements
- PHP 8.2+
- Composer
- XAMPP (MySQL/MariaDB)
- Laravel 12 compatible PHP extensions

## Install / run
1. Extract this folder to a working location such as `C:\xampp\htdocs\crown_hotel_laravel`.
2. Open a terminal in the folder.
3. Install Laravel dependencies:
   ```bash
   composer install
   ```
4. Copy `.env.example` to `.env` (Windows CMD: `copy .env.example .env`).
5. Generate the app key:
   ```bash
   php artisan key:generate
   ```
6. Create an empty MySQL database named `crown_hotel` in phpMyAdmin.
7. Check `.env` database settings. Default XAMPP settings are root with a blank password.
8. Run migrations and seed starter rooms:
   ```bash
   php artisan migrate --seed
   ```
9. Start the Laravel server:
   ```bash
   php artisan serve
   ```
10. Open `http://127.0.0.1:8000`.

## Admin login
- Username/email: `admin@crownhotel.com`
- Password: `crownhotel2026`

The built-in administrator is provisioned when these credentials are used on the login page. Change this demo password before using the app beyond local coursework.

## Notes
- The app uses the original table names/column names so existing UI data fields continue to work.
- `database/sql/crown_hotel.sql` is included for reference/manual import. For a fresh Laravel database, prefer `php artisan migrate --seed`; do not import the SQL file and then run migrations against the same database.
- Room uploads are stored under `public/uploads`.
- The payment QR is a placeholder only. The app does not process or automatically verify real payments.
- This is a local development/school-project setup, not a production deployment.
