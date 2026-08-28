# Rattlesnake Mountain

Laravel 12 + Inertia + Vue 3 + Tailwind, MySQL. Development on Windows (Herd), production on Laravel Forge.

## Browser verification (Playwright MCP)

Pest feature tests never exercise Inertia or the compiled front end. When a change is visual or depends on client-side behaviour, verify it in a real browser with the Playwright MCP server registered in `.mcp.json`. This is manual verification tooling, not a committed test suite.

- **Base URL:** `https://rattlesnake-mountain.test`. Always navigate to absolute URLs on that host. Herd redirects plain HTTP to HTTPS and serves a self-signed certificate, so `.mcp.json` passes `--ignore-https-errors`.
- **Headless by default.** `.mcp.json` passes `--headless`. Do not switch to headed mode unless the user asks.
- **Screenshots:** `storage/screenshots`. The MCP server is configured with `--output-dir storage/screenshots`; save screenshots there and nowhere else. The directory ignores its own contents, so `git status` stays clean.
- **Credentials:** read `PLAYWRIGHT_TEST_EMAIL` and `PLAYWRIGHT_TEST_PASSWORD` from `.env`. Never ask the user to paste credentials into the prompt, and never write a credential into a file, a commit, or a screenshot caption.

### Login sequence

1. Navigate to `https://rattlesnake-mountain.test/login`.
2. Read the two `PLAYWRIGHT_TEST_*` values from `.env`.
3. Fill the email and password fields, submit the form.
4. Confirm you land on `/dashboard`, then navigate to the page under test.

The browser profile is isolated and nothing is persisted between sessions, so log in again each session.

### Data safety

The browser drives the live dev site against the working `rattlesnake_mountain` MySQL database. There is no isolation and no rollback: anything clicked is a real write. Read-only verification is the default. Before exercising a flow that mutates data (admin actions, breeding, lifecycle, trading, deletions), say what will be written and get the user's go-ahead.

Nothing here runs in CI.
