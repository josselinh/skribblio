<laravel-boost-guidelines>
=== .ai/project rules ===

# Skribblio Tools — Project Guidelines

Skribblio Tools lets users build custom word lists (alone or collaboratively) and export them for Skribbl.io games. The whole app is private: every page requires a registered, logged-in user with a verified email address (the email is used for notifications).

## Architecture

- The front-end is a **Vue 3 + TypeScript single-page application** living in `resources/js`, built by Vite. It is not Inertia and not Blade: do not add Blade pages or Inertia.
- Laravel is a **JSON API** consumed by the SPA. Laravel serves a single view, `resources/views/app.blade.php`, for every front-end path (see `routes/web.php`).
- `routes/web.php` declares named SPA routes (`dashboard`, `login`, `password.reset`, `verification.notice`) because Laravel and Fortify generate redirects and email links to those names. Any other front-end path is handled by `Route::fallback`, which returns a JSON 404 for `api/*` or JSON requests.
- **Authentication**: Sanctum in SPA mode (session cookie + CSRF, `statefulApi()` in `bootstrap/app.php`) with **Fortify headless** (`'views' => false`). Fortify features enabled: registration, password reset, email verification, profile update, password update. Two-factor auth and passkeys are intentionally disabled.
- Fortify endpoints live at the root (`/login`, `/register`, `/logout`, `/forgot-password`, `/reset-password`, `/email/verification-notification`); the app's API lives under `/api`.
- After authentication, users land on `/dashboard` (Fortify `home` is `/dashboard`).

## Domain model

- **Word** (`words`): `name`, `word_group_id`, `created_by`. Belongs to exactly one group; the name is unique within its group, at most 32 characters (`Word::NAME_MAX_LENGTH`) and contains no comma, because Skribbl.io uses commas as separators. A word has no visibility of its own: it is visible to whoever can see its group. **Votes** (`word_votes`, `Word::voters()`, pivot `value` +1/−1, composite primary key): while the group is open to contributions, whoever can see it gives a word a thumb up or down (`WordVote` enum), except its author; once closed, votes are kept and shown but nobody can vote. Votes are only an indicator for contributors: they do not affect the export and are not copied by duplication.
- **WordGroup** (`word_groups`): `name`, `visibility`, `created_by`. Has many words; belongs to many group collections through the `groups_collections` pivot (keys `group_collection_id`, `word_group_id`). The model is named `GroupCollection`, not `Collection`, to avoid confusion with Laravel's collection classes.
- **GroupCollection** (`group_collections`): `name`, `visibility`, `created_by`. Belongs to many word groups through `groups_collections`. **A collection must always contain at least one group** (enforce it when creating a collection or detaching a group; the database cannot). Exception: deleting a group removes it from every collection, even when it was the last one, so a collection can end up empty (its page says so).
- **Favorites**: users favorite groups (`favorite_word_groups`) and collections (`favorite_group_collections`), with composite primary keys.
- `visibility` is the `App\Enums\Visibility` enum (`Public`: every logged-in user can see; `Private`: only the creator). Groups and collections share the `App\Models\Concerns\HasVisibility` trait (`creator()`, `isCreatedBy()`, `isVisibleTo()`, `visibleTo($user)` scope).
- Authorization lives in `app/Policies`:
    - Only the creator can update or delete a group or a collection.
    - **Anyone who can see a group can add words to it** (so anyone for a public group).
    - A word can be edited or deleted by its author or by the creator of its group.
    - The creator of a collection can attach any group they can see, including other users' public groups.
    - Favoriting requires being able to see the item.
- A public collection may contain private groups. Other users must not see those groups: filter with `$groupCollection->wordGroups()->visibleTo($user)` and tell them that some groups are hidden.
- Deletions cascade: deleting a user deletes everything they created, except the words they added to other people's groups, which stay without author (`words.created_by` null, shown as "Compte supprimé"); deleting a group deletes its words and detaches it from collections; deleting a collection keeps its groups.
- Factories have `public()`, `private()` and `createdBy($user)` states. `GroupCollectionFactory` attaches a group automatically when none was attached, to respect the "at least one group" rule.
- **Sharing private items** (`Shareable` trait, used with `HasVisibility`):
    - The creator gets a secret share link (`share_token`, generated on demand, regenerable to invalidate old links, never serialized). Opening `/share/groups/{token}` or `/share/collections/{token}` lets a user request access; the creator is emailed (`AccessRequested`), accepts or refuses (`AccessRequestDecided` email), and can revoke access later.
    - Requests live in `word_group_access_requests` / `group_collection_access_requests` (one row per item and requester, `AccessRequestStatus`: pending, accepted, refused; asking again after a refusal reopens it). Accepted requesters become members (`word_group_members` / `group_collection_members`).
    - Visibility = public, or creator, or member. A member of a private group can see it and add words. **A member of a collection also sees the private groups it contains, but only those created by the collection's creator** (consent rule: sharing a collection never exposes other people's groups). `WordGroup` overrides `isSharedWith()` / `orWhereSharedWith()` for this.
    - On a collection, a viewer can request access to all the groups hidden from them in one action; those requests carry `via_group_collection_id` and must never reveal the groups' names to the requester (not listed in "shared with me", refusal email without the name).
    - Policies: `share` (creator, private item only) `manageAccess` (creator) and `leave` (direct members only: seeing a group through a collection membership does not make one its member).

## Back-end conventions

- **Every primary key is a UUID**: models use `HasUuids`, migrations use `$table->uuid('id')->primary()` and `foreignUuid()` / `uuidMorphs()`. Never use `id()` or `foreignId()`.
- New business endpoints go inside the `auth:sanctum` + `verified` group of `routes/api.php`, in controllers under `app/Http/Controllers/Api`, validated by Form Requests (authorizing through the policy). Current endpoints:
    - `GET /api/word-groups`: the current user's own groups (12 per page, most recently updated first). `POST /api/word-groups` creates one; `GET`/`PATCH`/`DELETE /api/word-groups/{id}` show/update/delete one (`DELETE /api/group-collections/{id}` likewise; creator only, 404 when the user cannot see the item). Both pages show a "Supprimer" button (`DeleteButton`, `can.delete`, confirmed in `ConfirmDialog`) after "Modifier", "Dupliquer" and "Partager", through the `actions` slot of `EditableNameVisibility`, then go back to the dashboard.
    - Favorites: `PUT`/`DELETE /api/word-groups/{id}/favorite` and `/api/group-collections/{id}/favorite` (idempotent; adding requires seeing the item, removing is always allowed). `GET /api/favorites/word-groups` and `GET /api/favorites/group-collections` list them (12 per page, most recently favorited first), including items that became private, flagged `is_accessible: false`.
    - Group and collection resources expose `is_favorite` when computed: use the `withIsFavorite($user)` scope on lists and `loadIsFavorite($user)` on a single model (`Favoritable` trait); never compute it per item.
    - Sharing: `GET`/`POST /api/{word-groups|group-collections}/{id}/share-link` (get / regenerate), `GET .../access` (pending requests and members), `DELETE .../members/{userId}`, `DELETE .../membership` (the current user, a member, leaves the item: `leave` policy, `can.leave`, `Shareable::leave()`; like a revocation, they can ask again, and their favorites on what they can no longer see are removed, including a collection's private groups it showed them); `GET`/`POST /api/share/{word-groups|group-collections}/{token}[/access-request]` (invitation status: accessible, pending, refused, none); `GET /api/access-requests/received`, `POST /api/access-requests/{kind}/{id}/accept|refuse`; `GET /api/shared/word-groups|group-collections` (granted items and pending link requests, flagged with `access_status`); `POST /api/group-collections/{id}/hidden-word-groups/access-requests`.
    - `GET /api/search/word-groups` and `GET /api/search/group-collections` (`q`, `author=all|me|others`, `per_page` ≤ 24, `page`): paginated search, by name only, over what the user can see; names starting with `q` first, then most recently updated; results include `creator` and `words_count` / `word_groups_count`. Name search and author filtering are the `nameContains`, `orderByNameRelevance` and `createdByAuthor` scopes of the `HasSearchableName` trait.
    - `GET /api/word-groups/search?q=`: up to 10 groups visible to the user whose name contains `q` (own groups first), with their `creator`; used by the group picker.
    - `GET /api/group-collections`: the current user's own collections (12 per page). `POST /api/group-collections` creates one with `word_group_ids` (at least one, each visible to the user), in a transaction.
    - `GET`/`PATCH /api/group-collections/{id}`: show (with the `word_groups` visible to the user and `hidden_word_groups_count`) / update a collection. `POST /api/group-collections/{id}/word-groups` (`word_group_id`) adds a group, `DELETE /api/group-collections/{id}/word-groups/{wordGroupId}` removes one but refuses the last one (422). Both touch the collection's `updated_at`.
    - `GET`/`POST /api/word-groups/{id}/words`: list (20 per page, `sort=name|author|created|updated|score` and `direction=asc|desc`, `App\Enums\WordSort`: most recently added first by default, the direction defaulting to `WordSort::defaultDirection()`, authors deleted last, score = upvotes − downvotes then most upvotes; each word has `upvotes_count`, `downvotes_count`, `my_vote` from the `withVotes($user)` scope, and the response has `has_votes`) / add the words of a group. `POST` takes `names` (up to 500, split by the SPA from a comma- or newline-separated text): it is lenient, adds the valid words, skips those already in the group (as the database compares them, regardless of case and accents) and returns `{ data, skipped, invalid: [{ name, message }] }` (201 when a word was added, 200 otherwise); `AddWordForm` leaves the invalid ones in its textarea. `PATCH`/`DELETE /api/words/{id}` (shallow routes) rename/delete a word. `PUT /api/words/{id}/vote` (`vote`: `up`|`down`) / `DELETE /api/words/{id}/vote` record or remove the current user's vote (idempotent, `vote` policy, return the word with its counts).
    - `POST /api/word-groups/{id}/duplicate` (`duplicate` policy: anyone who can see the group, members of a private group included): copies the group (`WordGroup::duplicateFor()`) into a new group of the current user named "… (copie)", private and closed to contributions, with every word written by them (bulk insert in a transaction); favorites, members, share link and collections are not copied, and the copy keeps no link to the original. The group page shows a "Dupliquer" button (`can.duplicate`) and opens the copy.
    - `POST /api/group-collections/{id}/duplicate` (`duplicate` policy: anyone who can see the collection): copies the collection ("… (copie)", private, closed to contributions) with a copy of each group the user can see, made by `WordGroup::duplicateFor()`, except the user's own groups, which are kept as they are; hidden groups are not copied (the response's `hidden_word_groups_count` says how many, never which); 422 when no group is visible. Everything happens in one transaction (`GroupCollection::duplicateFor()`). Both pages use `DuplicateButton` ("Dupliquer", right of "Modifier", confirmed in a `ModalDialog` that explains what is copied); the copy name comes from the `HasCopyName` trait.
    - `GET /api/word-groups/{id}/export` and `GET /api/group-collections/{id}/export`: every word (unpaginated, sorted by name, duplicates kept) of a group, or of the groups of a collection visible to the user; allowed to anyone who can see the item.
    - `GET /api/exports/composition?word_group_ids[]=…&group_collection_ids[]=…` (at least one item, 50 of each at most, all visible to the user or 422): words of the selected groups plus the groups of the selected collections (deduplicated groups, hidden ones left out and counted in `hidden_word_groups_count`), with the resolved `word_group_ids` so that the SPA can save the composition as a collection through `POST /api/group-collections`. Nothing is stored.
- `DELETE /api/user` (`CurrentUserController`, `password` checked with `current_password`) deletes the current user's account: it logs out first (logging out saves a remember token, which would recreate a deleted user), then deletes their sessions, API tokens, password reset tokens and the user, in a transaction. The profile page has a "Supprimer mon compte" section (password asked in a `ModalDialog`), then goes to the login page with a status message. Queued notifications are marked `#[DeleteWhenMissingModels]`.
- Resources expose a `can` object (e.g. `can.update`, `can.add_word`) computed from the policies, so the SPA shows or hides actions without duplicating authorization rules.
- A private group the user cannot see answers **404, not 403** (`Response::denyAsNotFound()` in the policy; Form Requests return `Gate::inspect(...)` to keep that status).
- Adding, renaming or deleting a word touches its group's `updated_at` (`#[Touches(['wordGroup'])]` on `Word`). Only `GET /api/user` (the SPA needs it to know where to redirect) and `DELETE /api/user` are reachable by unverified users.
- API responses always go through Eloquent API Resources (`app/Http/Resources`), with snake_case keys and ISO 8601 dates.
- The application locale is French (`APP_LOCALE=fr`). Translations come from `laravel-lang/common` (dev dependency) and are published in `lang/`; refresh them with `vendor/bin/sail artisan lang:update`. Add new user-facing strings in French. Messages produced by the back-end (validation messages and field names, policy denials, emails, generated names such as " (copie)") are never written in the code: they live in per-domain PHP translation files of `lang/fr` (`words.php`, `word_groups.php`, `group_collections.php`, `access_requests.php`, `duplication.php`, `account.php`, `fields.php` for validation attribute names) and are read with explicit keys and placeholders (`__('words.add.max', ['max' => self::MAX_NAMES])`), never with the French text as key. Do not add them to the files generated by laravel-lang (`validation.php`, `fr.json`), which `lang:update` may overwrite.

## Front-end conventions

- Directory layout in `resources/js`: `pages/` (route components, `pages/auth/` for authentication, one sub-folder per domain such as `pages/word-groups/`), `components/` (reusable UI), `api/` (one module per API resource, e.g. `api/wordGroups.ts`, exposing typed functions), `stores/` (Pinia, setup syntax, for shared state such as the logged-in user), `composables/`, `router/`, `lib/` (HTTP client, formatting, labels), `types/`. Use the `@/` alias for imports.
- Authenticated pages are wrapped in `components/AppLayout.vue` (header + main container + `AppFooter`: open source licence, GitHub links, non-affiliation notice; also shown under `AuthCard` on auth pages).
- Editing happens in place on the detail page (e.g. `EditableNameVisibility` turns the page title of a group or collection into an inline form, `WordRow` edits a word inline and confirms its deletion in `ConfirmDialog`; its "Votes" column, shown while the group is open or when it has votes, uses `WordVoteButtons`, optimistic like `FavoriteButton`) rather than on separate edit pages. Modals use `ModalDialog` (native `<dialog>`: bottom sheet on phones, centered card from `sm`); `ConfirmDialog` wraps it for destructive confirmations. Data lists are tables, sorted by clicking column titles (`SortableHeader`; the words table keeps `sort`/`direction` in the URL, defaults left out, and goes back to page 1); collections of items to open are cards.
- Paginated lists use Laravel's paginator (`PaginatedResponse<T>` in `types/api.ts`), keep the page in the URL query (`?page=N`, or a dedicated parameter such as `groups_page` when a page shows several lists) and render `components/PaginationNav.vue` (`page-param` prop). Load them with the `usePaginatedList(fetchPage, pageParam)` composable.
- The header (`AppLayout`) has a search field leading to `/search` (`pages/search/SearchPage.vue`), whose filters (`q`, `type`, `author`, `page`) live in the URL. Results are rendered with `SearchResultsSection` (typed `item` slot). Cards show "par X · N mots" through `CardMeta` when the resource includes `creator`/counts; use `creatorLabel()` from `@/lib/creator` to display "vous" for the current user.
- Group and collection cards are built on `ItemCard`: the whole card links to the item, the favorite star (`FavoriteButton`, optimistic, reverted on failure) sits outside the link, and an item with `is_accessible: false` is greyed out and not clickable while its star stays usable. Card lists use `CardListSection` (dashboard and search).
- The dashboard shows, in order: a "Créer un export" call to action, received access requests (only when there are some), favorite groups, favorite collections, own groups, own collections, groups shared with me, collections shared with me (page parameters `favorite_groups_page`, `favorite_collections_page`, `groups_page`, `collections_page`, `shared_groups_page`, `shared_collections_page`). Pending requests in the shared lists are greyed out like inaccessible favorites.
- User pages: clicking another user's name (card "par X", word author, access requester) opens `/users/{id}` (`pages/users/UserPage.vue`), listing that user's groups and collections visible to the current user (`GET /api/users/{id}`, `/api/users/{id}/word-groups`, `/api/users/{id}/group-collections`; never the email). The current user's own page redirects to the dashboard. Display names with `UserLink` (plain text for the current user). Cards use a stretched link on the title (`ItemCard`), so other links inside a card must sit above it (`relative z-10`).
- Profile: the header's `UserMenu` (initials, name, ▾) opens a menu with "Mon profil" (`/profile`) and "Se déconnecter". The profile page (`pages/ProfilePage.vue`) uses Fortify's `PUT /user/profile-information` (name, email; a new email un-verifies the account and sends a verification email) and `PUT /user/password` (requires `current_password`) through the auth store.
- **Skribbl.io export** (the core feature): `ExportPanel` ("Exporter pour Skribbl.io" on group and collection pages) loads the export endpoint on each opening and shows the comma-separated list with a copy button, word and character counters, warnings (fewer than 10 words, more than 20,000 characters) and options: remove duplicates (case-insensitive, on by default), shuffle, and a random selection that fits in the character limit. Skribbl.io's limits and the list helpers live in `@/lib/skribbl`; formatting happens in the SPA, not in the API. The list itself is `WordListExport` (props `words`, `note`), reused by `ExportPanel` and by the "Créer un export" page (`/exports/new`, `pages/exports/CreateExportPage.vue`, linked from the top of the dashboard): the user picks groups and collections, sees the combined export update, and can save the selection as a new collection (collections are flattened into their groups; the new collection does not follow later changes). The selection is not kept in the URL.
- Sharing UI: `ShareButton` ("Partager", between "Dupliquer" and "Supprimer": a `ModalDialog` with the link, copy, regenerate), `AccessManagement` (requests and members on a private item's page), `AccessRequestItem`, `LeaveButton` ("Quitter", after "Supprimer", on `can.leave`: leaving a private item goes back to the dashboard, a public one reloads the page), and `pages/share/SharePage.vue`. A logged-out user opening a share link is sent back to it after login or email verification (`@/lib/pendingRedirect`).
- Pick groups with `WordGroupSearch` (search dropdown over visible groups, emits `select`); `WordGroupPicker` wraps it with removable tags for forms. Both build on the generic `ItemSearch` (`search` function prop), also used to search collections.
- Show user-facing dates with `formatDateTime()` from `@/lib/format` and visibility labels with `visibilityLabel()` / `visibilityDescription()` from `@/lib/visibility`.
- **Translations** (`vue-i18n`, Composition API mode, set up in `@/lib/i18n`): no user-facing text is written in components or modules. Messages live in `resources/js/lang/fr/<domain>.json` (one file per domain, registered in `lang/fr/index.ts`) under explicit keys, never the French text as key. Use `$t('words.add.submit')` in templates, `const { t } = useI18n()` in scripts and `i18n.global.t` outside components; put HTML or components inside a message with `<i18n-t keypath="common.byUser" scope="global">` and slots. Plurals use `|` (`"{n} mot | {n} mots"`, called as `t(key, count)`), with the French rule (0 and 1 singular; a three-part message starts with the zero form). Pick the group or collection variant of a message with `itemKey(kind)`. Use `intlLocale` for `Intl` formatters and `localeCompare`. The characters `@`, `|`, `{` and `}` are special in messages.
- To show a one-off status message on the next page, pass it in the router `state` (`router.push({ name, state: { status } })`); the target page reads it once with `consumeHistoryStatus()` from `@/lib/historyStatus` (on each route change when the page component is reused).
- Always call the server through the shared axios instance `http` from `@/lib/http` (it sends credentials and the XSRF token). Call `ensureCsrfCookie()` before guest-side state-changing auth requests.
- Every API payload has a TypeScript interface in `resources/js/types` that mirrors its API Resource (`models.ts` for resources, `api.ts` for response envelopes such as `ResourceResponse<T>`).
- Use the `useForm` composable for forms: it exposes `data`, `errors` (first Laravel validation message per field), `isProcessing`, `submit()` and `reset()`.
- Protect routes with `meta` in `router/index.ts`: `requiresVerified` (default for app pages), `requiresAuth` (logged in, verified or not), `guestOnly`.
- The UI is written in French. Styling uses Tailwind CSS v4 utility classes: neutral `zinc` tones, and the `brand` accent colour (a muted slate blue, defined in `@theme` in `resources/css/app.css`) for primary buttons, links, focus rings, selections and the current navigation item; never hard-code its values. Badges carry meaning: public in emerald, open contributions in sky, members only in violet. The UI follows the system theme: every colour class has its `dark:` counterpart next to it (`bg-white dark:bg-zinc-900`, `text-zinc-500 dark:text-zinc-400`, `text-brand-700 dark:text-brand-300`, `bg-brand-50 dark:bg-brand-950`; see existing components).
- Use `<script setup lang="ts">` with typed `defineProps<…>()` and `defineModel()`.

## Verification

- PHP: `vendor/bin/sail artisan test --compact` (PHPUnit feature tests) and `vendor/bin/sail bin pint --dirty --format agent`.
- Front-end: `vendor/bin/sail npm run type-check` (vue-tsc); `vendor/bin/sail npm run build` type-checks and builds.

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `vendor/bin/sail composer show --direct` to list direct dependencies with versions, or `vendor/bin/sail composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `vendor/bin/sail npm run build` or ask the user to run `vendor/bin/sail npm run dev` or `vendor/bin/sail composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `vendor/bin/sail artisan route:list`). Use `vendor/bin/sail artisan list` to discover available commands and `vendor/bin/sail artisan [command] --help` to check parameters.
- Inspect routes with `vendor/bin/sail artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `vendor/bin/sail artisan config:show app.name`, `vendor/bin/sail artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `vendor/bin/sail artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `vendor/bin/sail artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== sail rules ===

# Laravel Sail

- This project runs inside Laravel Sail's Docker containers. You MUST execute all commands through Sail.
- Start services using `vendor/bin/sail up -d` and stop them with `vendor/bin/sail stop`.
- Open the application in the browser by running `vendor/bin/sail open`.
- Always prefix PHP, Artisan, Composer, and Node commands with `vendor/bin/sail`. Examples:
    - Run Artisan Commands: `vendor/bin/sail artisan migrate`
    - Install Composer packages: `vendor/bin/sail composer install`
    - Execute Node commands: `vendor/bin/sail npm run dev`
    - Execute PHP scripts: `vendor/bin/sail php [script]`
- View all available Sail commands by running `vendor/bin/sail` without arguments.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `vendor/bin/sail artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `vendor/bin/sail artisan list` and check their parameters with `vendor/bin/sail artisan [command] --help`.
- If you're creating a generic PHP class, use `vendor/bin/sail artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `vendor/bin/sail artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `vendor/bin/sail artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/sail bin pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/sail bin pint --test --format agent`, simply run `vendor/bin/sail bin pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `vendor/bin/sail artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `vendor/bin/sail artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/sail bin phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
