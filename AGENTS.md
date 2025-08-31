# Repository Guidelines

## Project Structure & Module Organization
- `public/`: Browser-facing pages (e.g., `public/dashboard.php`, `public/mes_commandes.php`).
- `php/`: Server actions, exports, and form handlers (e.g., `php/export_*`, `php/login.php`).
- `config/`: App configuration (e.g., `config/db.php`).
- `uploads/`: User-uploaded assets (images, PDFs). Ensure write permissions in dev.
- `vendor/`: Third-party libraries (e.g., `dompdf`) loaded via autoloaders.

## Build, Test, and Development Commands
- Run with PHP’s dev server: `php -S localhost:8000 -t public`
  - Serves pages under `public/` (mirrors MAMP’s docroot behavior).
- MAMP: Place the repo in `htdocs` and visit `/drivncook/public/dashboard.php`.
- PDF exports use Dompdf; no build step required.

## Coding Style & Naming Conventions
- PHP: Follow PSR-12 style; 2 spaces indentation; UTF-8; short array syntax; strict types where feasible.
- Files: snake_case for PHP scripts (`export_commandes_pdf.php`), kebab-case for assets if added.
- CSS within pages uses CSS variables; prefer theme tokens over hardcoded colors.
- Security: escape output with `htmlspecialchars` (`e()` helper exists in views).

## Testing Guidelines
- No formal test suite yet. Add focused tests if introducing critical logic.
- Manual checks: login/logout, filters in `mes_commandes.php`, PDF exports, uploads visibility.
- If adding PHPUnit: place tests under `tests/`, name like `Feature/ExportCommandesTest.php`; run `vendor/bin/phpunit`.

## Commit & Pull Request Guidelines
- Commits: small, scoped, imperative mood (e.g., "Fix dark mode table background").
- PRs: include purpose, changes, screenshots for UI, and steps to verify (URLs, credentials if mock).
- Link related issues; note migrations/config changes explicitly.

## Security & Configuration Tips
- Database config: edit `config/db.php` for local credentials. Prefer env vars in production and never commit secrets.
- File permissions: `uploads/` must be writable by the web server in dev and prod.
- Input handling: validate request data in `php/*` handlers; use prepared statements (already in use) and output escaping in views.

