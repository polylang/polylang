# E2E conventions

Stack: `@wordpress/e2e-test-utils-playwright` + `@wpsyntex/e2e-test-utils`. Config: `tests/e2e/playwright.config.mjs`. Run: `npm run test:e2e` · scoped path · Docker browsers: `npm run test:e2e:docker-browser`.

| Path | Purpose |
|------|---------|
| `tests/e2e/specs/<area>/` | `*.spec.js` |
| `tests/e2e/plugins/` | Test plugins (`custom-strings/`, `translation-updates/`) |
| `tests/e2e/specs/**/*.spec.js-snapshots/` | Image baselines |

Copy `maxDiffPixelRatio` from `specs/blocks/navigation-language-switcher.spec.js`.

Setup that is easy to miss:

| Area | Notes |
|------|--------|
| Widget editor | Theme with widget areas (e.g. `twentytwentyone`); restore it in `afterAll` |
| Navigation block | `test.describe.serial` |
| Strings | `setSetting( requestUtils, 'force_lang', … )` and a serial admin → frontend flow |
| Test plugins | Load from `tests/e2e/plugins/` |
