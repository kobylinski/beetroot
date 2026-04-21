# Laravel 12 & 13 compatibility for beetroot

**Date:** 2026-04-21
**Owner:** Marek Kobylinski
**Status:** Approved for planning

## Goal

Make `kobylinski/beetroot` install and run correctly on Laravel 10, 11, 12, and 13,
with a GitHub Actions CI matrix that proves it, and a Dependabot + Copilot workflow
that keeps the compatibility range moving forward automatically after release.

Current state: the package supports `^10.0|^11.0`, has no tests, no CI, and commits
`composer.lock` (incorrect for a library).

## Scope

**In scope**
- Widen `composer.json` to `^10.0|^11.0|^12.0|^13.0`, bump PHP floor to `8.1`.
- Add a Pest test suite covering the four public units.
- Add a GitHub Actions matrix running `{PHP 8.1..8.4} x {L10..L13}` with the valid
  cells only.
- Apply the minimum source changes required to make the matrix green.
- Remove the committed `composer.lock`; add it to `.gitignore`.
- Add `.github/dependabot.yml` for `composer` and `github-actions` ecosystems.
- Add one Copilot-friendly issue template for future Laravel major upgrades.
- Document the branch-protection and Copilot-agent settings the maintainer needs
  to click (not code — this is out of band of a commit).

**Out of scope**
- New features or API additions.
- Refactors that are not required to make the matrix pass.
- Dropping support for Laravel 10 or 11.
- Changing the package's public API.

## Approach

**All-in-one.** A single branch/PR widens composer, adds the tests, adds the CI
matrix, and applies any source fixes CI surfaces. Reviewer sees one coherent
change; if something is wrong it reverts as one unit.

## Target compatibility matrix

|         | L10 | L11 | L12 | L13 |
|---------|-----|-----|-----|-----|
| PHP 8.1 | yes |     |     |     |
| PHP 8.2 | yes | yes | yes |     |
| PHP 8.3 | yes | yes | yes | yes |
| PHP 8.4 |     | yes | yes | yes |

Total: 11 CI jobs (16 cells minus 5 excluded combinations). Derived from each
Laravel major's minimum PHP (L10:8.1, L11:8.2, L12:8.2, L13:8.3) and each PHP
release's support window.

## Composer changes

```json
{
  "require": {
    "php": ">=8.1",
    "illuminate/console": "^10.0|^11.0|^12.0|^13.0",
    "illuminate/support": "^10.0|^11.0|^12.0|^13.0",
    "illuminate/validation": "^10.0|^11.0|^12.0|^13.0"
  },
  "require-dev": {
    "pestphp/pest": "^2.34|^3.0",
    "orchestra/testbench": "^8.0|^9.0|^10.0|^11.0"
  },
  "autoload-dev": {
    "psr-4": { "Kobylinski\\Beetroot\\Tests\\": "tests/" }
  }
}
```

Rationale:
- PHP floor `>=8.1` — Laravel 10's minimum. Cannot go lower without dropping L10.
- Pest `^2.34|^3.0` — Pest 3 requires PHP 8.2+, Pest 2 still supports 8.1.
  Composer resolves per PHP version automatically.
- `orchestra/testbench` is the de facto way to boot Laravel services inside a
  package's tests. Version tracks Laravel major: TB 8 / L10, TB 9 / L11,
  TB 10 / L12, TB 11 / L13.

`composer.lock` is removed from git and added to `.gitignore`. A library's
lockfile belongs to the consuming application, not the library.

## Test suite

Pest, under `tests/`. Small — ~15 focused tests total. The parser earns the
densest coverage because it's the most novel code and the most exposed to
framework drift.

Files:

- `tests/Pest.php` — Testbench bootstrap; binds a validator factory.
- `tests/WithValidateTest.php`
  - Validation fires when rules are defined.
  - Custom `messages()` overrides default messages.
  - Missing `rules()` is a no-op.
- `tests/WithNamedParametersTest.php`
  - `#[Value]` / `#[Flag]` / `#[Sequence]` parameters map to class properties.
  - Defaults apply when the rule string omits a parameter.
  - `Value(dictionary: [...])` rejects out-of-dictionary values.
  - `#[Rule("name")]` registers under the declared name (reflection check).
- `tests/WithSubcommandsTest.php`
  - Parsed `InputDefinition` contains every nested argument.
  - Running the command with a subcommand argument reaches the right branch.
- `tests/Subcommands/ParserTest.php`
  - Flat signature.
  - One-level nesting.
  - Two-level nesting.
  - Alternation `a|b`.
  - Default-star marker `*find`.
  - Optional arrays `{abilities?*}`.

Principle: test the public behavior, not the internals. If a test mirrors the
implementation line-for-line, it's not a test.

## CI workflow

Single file: `.github/workflows/tests.yml`.

Triggers: `push` and `pull_request` on `main`.

Job shape:

```yaml
strategy:
  fail-fast: false
  matrix:
    php: ['8.1', '8.2', '8.3', '8.4']
    laravel: ['10.*', '11.*', '12.*', '13.*']
    exclude:
      - { php: '8.1', laravel: '11.*' }
      - { php: '8.1', laravel: '12.*' }
      - { php: '8.1', laravel: '13.*' }
      - { php: '8.2', laravel: '13.*' }
      - { php: '8.4', laravel: '10.*' }

steps:
  - actions/checkout@v4
  - shivammathur/setup-php@v2 with { php-version: ${{ matrix.php }} }
  - composer require "laravel/framework:${{ matrix.laravel }}" --no-update
  - composer update --prefer-dist --no-progress
  - vendor/bin/pest
```

After the first all-green run, enable branch protection on `main`: require the
matrix job to pass before merge. This is the safety net that makes Dependabot
auto-PRs and Copilot agent PRs safe to land.

## Source-code changes

The Laravel touch points used by this package:

1. **`Illuminate\Console\Parser`** (subclassed in `src/Subcommands/Parser.php`) —
   the one risky point. If parent method signatures, visibility, or internal
   behaviour shifted across 10/11/12/13, the subclass breaks. Fix lands in one
   of three forms, picked based on what CI reports:
   - Adjust method signatures to match the newest parent.
   - Re-copy small pieces of parent logic into the subclass.
   - Refactor to compose rather than inherit.
2. **`Illuminate\Support\Facades\Validator`** — stable across 10–13. No change
   expected.
3. **`Illuminate\Support\Str`** — stable across 10–13. No change expected.
4. **`Illuminate\Validation\Validator`** — typehinted only. No change expected.

Principle for source edits: touch the minimum required to make the matrix
green. Every tempting cleanup is deferred to a future change.

## Dependabot & Copilot auto-upgrade

Applied **after** the matrix is green and a release is tagged.

**`.github/dependabot.yml`**

```yaml
version: 2
updates:
  - package-ecosystem: composer
    directory: /
    schedule: { interval: weekly }
  - package-ecosystem: github-actions
    directory: /
    schedule: { interval: weekly }
```

**Branch protection on `main`** (repo settings, not code):
- Require the matrix job to pass.
- Require PRs to be up-to-date with `main` before merge.
- Allow Dependabot and the Copilot agent to auto-merge green PRs.

**Copilot coding agent** (repo settings, not code):
- Enable the agent on the repo.
- Confirm the account's Copilot subscription covers the agent feature.

**Issue template for future Laravel upgrades** —
`.github/ISSUE_TEMPLATE/laravel-upgrade.md`. Worded as a Copilot prompt so the
future flow is: open issue, fill the template with the new Laravel version,
assign to `@copilot`, review the resulting PR. The template lays out:
- Which composer constraint to widen.
- Which matrix cells to add.
- Which minimum PHP the new Laravel requires (to trim obsolete cells).
- A reminder to update the README compatibility table if we add one.

## Release

After the matrix is green on `main`:
- Tag a new minor version (e.g. `v1.1.0` if current is `v1.0.x`; confirm before
  tagging).
- Packagist picks it up automatically via its GitHub webhook (assumed to exist
  from the original publication; to verify at tag time).

## Risks

- **Parser subclass** is the single most likely source of breakage. Mitigation:
  the ParserTest suite is the densest; any break surfaces immediately.
- **Testbench version juggling** — picking the wrong TB majors for a given L
  major wastes a CI slot. Mitigation: the composer constraint above is already
  aligned with Laravel majors.
- **Pest 2 vs 3 split** — if a test uses syntax only available in one, the other
  matrix half fails. Mitigation: stick to the Pest 2 subset, which is a proper
  subset of Pest 3.

## Out-of-band steps

These steps need a human in the GitHub UI and cannot be committed:
1. Enable branch protection on `main` after first green CI run.
2. Enable Copilot coding agent on the repo.
3. (Optional) Enable Dependabot auto-merge for green minor/patch PRs.

These will be listed at the end of the implementation plan with exact
click-paths.

## Deliverables

- Updated `composer.json` (widened constraints, PHP floor, dev deps,
  `autoload-dev`).
- `composer.lock` removed from git; `.gitignore` updated.
- `tests/` directory with Pest suite (five files).
- `phpunit.xml` (Pest's default config).
- `.github/workflows/tests.yml` with the matrix.
- `.github/dependabot.yml`.
- `.github/ISSUE_TEMPLATE/laravel-upgrade.md`.
- Any minimal source changes CI forces in `src/`.
- A tagged release once `main` is green.
