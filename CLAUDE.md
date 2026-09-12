# Rattlesnake Mountain

Laravel 13 + Inertia + Vue 3 + Tailwind, MySQL. Development on Windows (Herd), production on Laravel Forge.

## Tests

`php artisan test` runs `RefreshDatabase`, which drops every table. `phpunit.xml` points it at `rattlesnake_mountain_testing`, kept separate from the working `rattlesnake_mountain` database. Create it once before the first run:

```sh
mysql -h 127.0.0.1 -u root -e "CREATE DATABASE IF NOT EXISTS rattlesnake_mountain_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
```

Never remove the `DB_DATABASE` override from `phpunit.xml`. Without it a test run wipes the dev database.

## Browser verification (Playwright MCP)

- **Base URL:** `https://rattlesnake-mountain.test`. Always navigate to absolute URLs on that host. Herd redirects plain HTTP to HTTPS and serves a self-signed certificate, 
- **Playwright Credentials:** read `PLAYWRIGHT_TEST_EMAIL` and `PLAYWRIGHT_TEST_PASSWORD` from `.env`. Never ask the user to paste credentials into the prompt, and never write a credential into a file, a commit, or a screenshot caption.

The browser profile is isolated and nothing is persisted between sessions, so log in again each session.

### Data safety

The browser drives the live dev site against the working `rattlesnake_mountain` MySQL database.

Nothing here runs in CI.

<!-- laravel-boost:start -->
## Stack

- Laravel 13.30 on PHP 8.4
- Vue 3.5, Inertia 3.7, Vite 6.4, TypeScript 5.7
- Pest 5, Tailwind 4.1

Laravel Boost is installed: Boost agent skills are in `.claude/skills/`, and the Boost
MCP server (`.mcp.json`) exposes this app's routes, models, config, database schema,
logs, tinker, and version-accurate Laravel docs. Prefer those tools over guessing.
<!-- laravel-boost:end -->
