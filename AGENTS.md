# AGENTS.md

Skribblio: community tool to prepare Skribbl.io games. Users create word **groups**, add **sentences** (words, max 30 chars) to them, **vote** on them, and **export** a group as a comma-separated list to paste into Skribbl.io.

Not for production: a personal playground for learning Laravel and AI pair programming. The user writes in French and is learning: **answer in French**, explain the "why" briefly and point to the modern Laravel way when relevant. Code, comments, commits and identifiers stay in English.

## Stack

- Laravel 13, PHP 8.5 (FrankenPHP), MySQL 8.4, PHPUnit 12.
- Breeze (Blade) + Tailwind CSS 3 + Alpine.js, built with Vite. No Livewire, Inertia, Vue or Sass.
- Dev-only: Debugbar, Mailpit (http://localhost:8025).
- Editor: VSCodium (open-source VS Code build, uses the Open VSX extension registry, not the Microsoft Marketplace).
- UI language is French only (`config/app.php` locale `fr`). Translations live in `resources/lang/fr/{group,menu,sentence}.php` (note: `resources/lang`, not `lang/`). Use `__('file.key')`; never hardcode UI strings.

## Commands (everything runs in Docker)

PHP, Composer and Node are **not installed on the host**. Prefix every command with `docker compose exec app`:

```sh
docker compose exec app vendor/bin/phpunit                    # all tests (SQLite in memory)
docker compose exec app vendor/bin/phpunit --filter=SentenceTest
docker compose exec app php artisan <cmd>                     # e.g. route:list, make:*, migrate
docker compose exec app composer <cmd>
docker compose exec app npm run build                         # needed after Blade/Tailwind class changes if Vite dev is not running
```

The host has little RAM: avoid heavy tooling, long-running watchers or extra services. Do not run `docker compose down -v` (deletes the database).

## Architecture

Request flow: `routes/web.php` → Controller → Form Request (validation) → **Manager** (business logic, queries) → Eloquent model → Blade view.

- `app/Managers/{Group,Sentence,User}Manager.php`: all business logic and query building. Each has a private `applyFilters(Builder, ?array $filters, ?int $userId)` that also enforces **visibility**. Put new query logic here, not in controllers. Controllers currently instantiate managers with `new` (legacy); constructor injection is preferred for new code.
- `app/Http/Controllers/{Group,Sentence}/`: action naming is `add` (shows form) / `doAdd` (handles POST), routes `group.add` / `group.add.do`. Keep this convention.
- `app/Http/Requests/*Request.php`: validation (`authorize()` currently returns `true`, no authorization).
- `app/Observers/VoteObserver.php`: keeps `sentences.note` (denormalized vote total) in sync on vote save.
- `app/Presenters/GroupPresenter.php`: display helpers returning `HtmlString` (always `e()` user data).
- `app/Jobs/ImportSentenceJob.php`: one job per imported sentence (queue is `sync` locally).
- `app/View/Components/`: Breeze layouts + `SentenceVoteButtons`.
- Flash messages: `->with('alert-success', __('...'))`, rendered by `layouts/alerts.blade.php`.

## Domain rules

- Admin = user with `id === 1` (`User::isAdmin()`). Admins see and edit everything.
- `Group` visibility constants: `VISIBILITY_PUBLIC = 1` (everyone can read and add), `VISIBILITY_PUBLIC_UNALTERABLE = 2` (everyone reads, only owner/admin adds; shown with a lock icon), `VISIBILITY_PRIVATE = 3` (owner/admin only). Always use the constants.
- Schema: `groups(name unique ≤30, user_id, visibility)`, `sentences(sentence ≤30, note, group_id, user_id)` unique on `(sentence, group_id)`, `votes(sentence_id, user_id, note)` unique on `(sentence_id, user_id)`.
- A sentence is exported only when `note >= 0`. `sentence.export` is public (guests see public groups only); all other group/sentence routes require `auth`.

## Laravel conventions to follow

- Use `php artisan make:*` to create classes (`--no-interaction`); check sibling files before creating new ones and reuse existing components/managers.
- Validation in Form Requests (array rules), never inline in controllers.
- Eloquent over `DB::`; eager-load relations (`with()`) to avoid N+1; declare return types on relations.
- Use named routes (`route('sentence.index')`) and `config()` (never `env()` outside config files).
- Any schema change = new migration (never edit existing ones). Update factories/seeders accordingly.
- PHP 8 style for new code: constructor property promotion, typed properties and return types, `casts()` method. Do not mass-rewrite old code unless asked.
- Tailwind 3 utility classes, consistent with existing Breeze views and `x-*` components in `resources/views/components/`.

## Testing

- Do not write or add tests unless the user explicitly asks for them. Existing tests can still be run to check a change. Feature tests live in `tests/Feature/` (PHPUnit classes, `RefreshDatabase`, factories, `actingAs`). There is no `tests/Unit/` suite in `phpunit.xml` yet; add it there if a unit test is needed.
- Run the smallest relevant subset with `--filter` first, then the full suite before finishing.
- Tests use SQLite: avoid MySQL-only SQL.

## Known issues (don't rediscover them)

- `README.md` still mentions Sass; the project uses Tailwind.

## Commits & PRs

Short sentence-case imperative messages (`Add votes`, `Fix groups visibilities`), one focused change per commit. Commit only when asked. Never commit `.env` or secrets.
