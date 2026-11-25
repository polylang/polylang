# Architecture

## PHP (plugin)

| Area | Path |
|------|------|
| Bootstrap | `polylang.php` → `new Polylang()` |
| Core | `src/class-polylang.php`, `src/base.php`, `src/model.php` |
| Features | `src/modules/*/load.php`, `src/integrations/*/load.php` |
| Namespaced | `src/Options/`, `src/Capabilities/`, `src/Switcher/`, `src/modules/REST/` |
