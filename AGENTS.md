# Repository Guidelines

## Project Purpose & Context

This project is not intended for production deployment. Its primary purpose is personal training and experimentation with Laravel and AI-assisted software development. Explanations should remain pedagogical, clear, and focused on learning modern Laravel best practices and effective pair programming with AI.

## Project Structure & Module Organization

This is a Laravel 13 application. Business logic is in `app/` (models, managers, jobs, requests, and controllers); routes are in `routes/`; Blade templates and frontend sources are in `resources/views/`, `resources/js/`, and `resources/css/`. Database migrations, seeders, and factories live in `database/`. PHPUnit tests are under `tests/Unit/` and `tests/Feature/`. Compiled frontend assets are emitted to `public/build/` by Vite; edit source files under `resources/` instead.

## Build, Test, and Development Commands

- `composer install` and `npm install` install PHP and JavaScript dependencies.
- `cp .env.example .env`, then `php artisan key:generate`, configures a local environment; set database details in `.env` as needed.
- `php artisan migrate --seed` creates and seeds the database.
- `php artisan serve` runs the Laravel development server.
- `vendor/bin/phpunit` runs the unit and feature suites (SQLite in memory is configured in `phpunit.xml`).
- `npm run dev` starts the Vite development server; `npm run build` creates production assets.

## Coding Style & Naming Conventions

Follow the surrounding Laravel/PHP conventions and keep changes consistent with nearby files. Use PSR-4 namespaces under `App\`, `Database\`, and `Tests\`; name PHP classes in `StudlyCase`, methods and variables in `camelCase`, and migrations with timestamped snake_case filenames. Keep route definitions in the appropriate `routes/` file and use Laravel requests for input validation. Frontend assets use JavaScript and Tailwind CSS, compiled through Vite. No separate formatter or linter is configured in the repository.

## Testing Guidelines

Add regression coverage in `tests/Feature/` for HTTP behavior and `tests/Unit/` for isolated logic. Name test files and classes with the `Test` suffix, following the existing `ExampleTest.php` pattern. Run `vendor/bin/phpunit` before submitting changes; the suite uses an in-memory SQLite database and does not require local database data.

## Commit & Pull Request Guidelines

Recent commits use short, sentence-case imperative summaries (for example, “Add votes” and “Improve navbar + Separate add page from index”). Keep each commit focused and describe its user-visible change. Pull requests should explain the motivation and implementation, link related issues when available, list relevant test/build commands and outcomes, and include screenshots for visible UI changes.

## Security & Configuration Tips

Keep secrets and machine-specific values in `.env`; do not commit credentials. Review migrations and seeders for unintended data changes before submitting them.
