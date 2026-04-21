---
name: Laravel major upgrade
about: Add support for a new Laravel major version
title: "Support Laravel <VERSION>"
labels: ["upgrade", "ready-for-copilot"]
---

**Target Laravel version:** <VERSION> (e.g. `14.0`)

**Minimum PHP required by this version:** <PHP> (check https://laravel.com/docs/<VERSION>/releases)

---

### Tasks for the implementer (Copilot agent or human)

1. Widen `composer.json` constraints to include `^<VERSION>.0`:
   - `illuminate/console`
   - `illuminate/support`
   - `illuminate/validation`
2. Bump PHP floor in `composer.json` **only if** an older Laravel major in the constraint has been dropped because of this upgrade (rare — don't bump gratuitously).
3. Update `.github/workflows/tests.yml`:
   - Add `'<VERSION>.*'` to the `laravel` matrix axis.
   - Add a row to the matrix `include:` block mapping to the right Testbench version (Testbench major == Laravel major − 2).
   - Add `exclude:` entries for PHP versions the new Laravel doesn't support.
   - Remove `exclude:` entries for older Laravel versions that are now past their support window, if the user has chosen to drop them.
4. Run the full Pest suite locally against the new Laravel version to confirm nothing regresses.
5. Open a PR against `main`. CI must be green on every matrix cell before merge.

### Definition of done

- `composer.json` accepts Laravel <VERSION>.
- CI matrix includes valid `{PHP × Laravel <VERSION>}` cells and all are green.
- A new tag has been pushed (minor bump from the previous release).
