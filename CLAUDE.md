# Rattlesnake Mountain

Laravel 12 + Inertia + Vue 3 + Tailwind, MySQL. Development on Windows (Herd), production on Laravel Forge.

## Browser verification (Playwright MCP)

- **Base URL:** `https://rattlesnake-mountain.test`. Always navigate to absolute URLs on that host. Herd redirects plain HTTP to HTTPS and serves a self-signed certificate, 
- **Playwright Credentials:** read `PLAYWRIGHT_TEST_EMAIL` and `PLAYWRIGHT_TEST_PASSWORD` from `.env`. Never ask the user to paste credentials into the prompt, and never write a credential into a file, a commit, or a screenshot caption.

The browser profile is isolated and nothing is persisted between sessions, so log in again each session.

### Data safety

The browser drives the live dev site against the working `rattlesnake_mountain` MySQL database. There is no isolation and no rollback: anything clicked is a real write. Read-only verification is the default. Before exercising a flow that mutates data (admin actions, breeding, lifecycle, trading, deletions), say what will be written and get the user's go-ahead.

Nothing here runs in CI.
