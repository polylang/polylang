# E2E conventions

Stack: `@wordpress/e2e-test-utils-playwright` + `@wpsyntex/e2e-test-utils`. Config: `tests/e2e/playwright.config.mjs` (wp-env `http://localhost:8891`). Run: `npm run test:e2e` · scoped path.

| Path | Purpose |
|------|---------|
| `tests/e2e/specs/<area>/` | `*.spec.js` |
| `tests/e2e/plugins/` | Test plugins (`custom-strings/`, `translation-updates/`) |
| `tests/e2e/specs/**/*.spec.js-snapshots/` | Image baselines |

Image snapshots only (no download fixtures). Linux baselines use the Playwright Docker browser: `npm run env:start:docker-browser`, then `npm run test:e2e:docker-browser`. `maxDiffPixelRatio` baseline is `0.25`; adjust it when a snapshot needs a different tolerance.

| Area | Notes |
|------|--------|
| Widget editor | Theme with widget areas (e.g. `twentytwentyone`); restore it in `afterAll` |
| Navigation block | `test.describe.serial` |
| Strings | `setSetting( requestUtils, 'force_lang', … )` and a serial admin → frontend flow |
