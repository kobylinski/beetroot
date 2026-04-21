# Laravel 12/13 Compatibility Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make `kobylinski/beetroot` install and run on Laravel 10, 11, 12, and 13; prove it with a GitHub Actions matrix; set up Dependabot + Copilot for automated future upgrades.

**Architecture:** Widen composer constraints to `^10.0|^11.0|^12.0|^13.0`, add a Pest test suite that exercises the four public units, run a GitHub Actions matrix of 11 `{PHP × Laravel}` cells to prove compatibility, apply the minimum source-code fixes that CI surfaces, tag a release, then add `.github/dependabot.yml` and a Copilot-ready issue template for future Laravel upgrades.

**Tech Stack:** PHP 8.1–8.4, Laravel 10–13, Pest 2/3, orchestra/testbench, GitHub Actions, Dependabot.

**Linked spec:** [docs/superpowers/specs/2026-04-21-laravel-12-13-compatibility-design.md](../specs/2026-04-21-laravel-12-13-compatibility-design.md)

---

## File Structure

**To modify:**
- `composer.json` — widen require/require-dev, bump PHP floor, add `autoload-dev`.
- `.gitignore` — add `composer.lock`, `vendor/`, `/.phpunit.cache/`.

**To create:**
- `phpunit.xml` — Pest config.
- `tests/Pest.php` — Testbench bootstrap shared by all tests.
- `tests/TestCase.php` — Testbench base class (one property: `orchestra/testbench` integration).
- `tests/Subcommands/ParserTest.php` — pure-function tests on the Parser class.
- `tests/WithValidateTest.php` — exercises validation via a fake command + Testbench.
- `tests/WithNamedParametersTest.php` — rule registration, parameter mapping, dictionary rejection.
- `tests/WithSubcommandsTest.php` — fake command with nested signature.
- `tests/Stubs/AddUserCommand.php`, `tests/Stubs/MyCustomRule.php`, `tests/Stubs/UserCommand.php` — fixtures used by the trait tests.
- `.github/workflows/tests.yml` — matrix CI.
- `.github/dependabot.yml` — composer + github-actions weekly.
- `.github/ISSUE_TEMPLATE/laravel-upgrade.md` — Copilot-ready prompt template.

**To remove from git:**
- `composer.lock` (and ignore it going forward).

**Source files touched only if CI surfaces breakage:**
- `src/Subcommands/Parser.php` (most likely point of drift).
- Others are unlikely; act only in response to a failing matrix cell.

---

## Task 1: Remove committed lockfile and expand `.gitignore`

**Files:**
- Remove from git: `composer.lock`
- Modify: `.gitignore` (create if missing)

- [ ] **Step 1: Check current `.gitignore` state**

```bash
cat /Users/marek/Projects/garden/beetroot/.gitignore 2>/dev/null || echo "(no .gitignore)"
```

Expected: either an existing file or "(no .gitignore)".

- [ ] **Step 2: Write `.gitignore`**

Write the full contents of `/Users/marek/Projects/garden/beetroot/.gitignore`:

```
/vendor/
/composer.lock
/.phpunit.cache/
/.phpunit.result.cache
/.DS_Store
```

If the file already existed with other entries, add these lines without removing existing ones.

- [ ] **Step 3: Untrack the lockfile**

```bash
cd /Users/marek/Projects/garden/beetroot
git rm --cached composer.lock
```

Expected: `rm 'composer.lock'`.

- [ ] **Step 4: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add .gitignore
git commit -m "chore: stop tracking composer.lock for this library"
```

---

## Task 2: Widen composer constraints and add dev dependencies

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Rewrite `composer.json`**

Replace the entire contents of `/Users/marek/Projects/garden/beetroot/composer.json` with:

```json
{
  "name": "kobylinski/beetroot",
  "description": "Helpers to extend Laravel's console commands with validation rules and support for subcommands with nested arguments.",
  "type": "library",
  "license": "MIT",
  "authors": [
    {
      "name": "Marek Kobylinski",
      "email": "marek@kobylinski.co"
    }
  ],
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
  "autoload": {
    "psr-4": {
      "Kobylinski\\Beetroot\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "Kobylinski\\Beetroot\\Tests\\": "tests/"
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true,
  "config": {
    "allow-plugins": {
      "pestphp/pest-plugin": true
    }
  }
}
```

Rationale for the new keys:
- `autoload-dev` namespaces test stubs.
- `allow-plugins` silences Composer's prompt for Pest's plugin.
- `minimum-stability: stable` + `prefer-stable: true` mean Composer never accepts a dev/alpha version by accident.

- [ ] **Step 2: Install dependencies**

```bash
cd /Users/marek/Projects/garden/beetroot
composer install --prefer-dist --no-progress
```

Expected: resolves against the newest Laravel the local PHP allows and prints `Generating autoload files`. If the local PHP is `<8.1`, stop and upgrade PHP before continuing.

- [ ] **Step 3: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add composer.json
git commit -m "deps: widen Laravel support to 10/11/12/13, add Pest + Testbench"
```

Do not commit the freshly created `composer.lock` — the previous task put it in `.gitignore`.

---

## Task 3: Scaffold Pest and write the first Parser test (TDD baseline)

**Files:**
- Create: `phpunit.xml`
- Create: `tests/Pest.php`
- Create: `tests/TestCase.php`
- Create: `tests/Subcommands/ParserTest.php`

- [ ] **Step 1: Write `phpunit.xml`**

Full contents of `/Users/marek/Projects/garden/beetroot/phpunit.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
  <testsuites>
    <testsuite name="Beetroot">
      <directory suffix="Test.php">tests</directory>
    </testsuite>
  </testsuites>
  <source>
    <include>
      <directory suffix=".php">src</directory>
    </include>
  </source>
</phpunit>
```

- [ ] **Step 2: Write `tests/TestCase.php`**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/TestCase.php`:

```php
<?php

namespace Kobylinski\Beetroot\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
}
```

- [ ] **Step 3: Write `tests/Pest.php`**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/Pest.php`:

```php
<?php

use Kobylinski\Beetroot\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);
```

Rationale: `uses()->in()` accepts directories; giving it `__DIR__` binds the Testbench TestCase to every test file. Parser tests don't need Testbench but booting it per test is cheap (~ms), and keeping a single rule is simpler than per-file opt-in.

- [ ] **Step 4: Write the first Parser test (failing at first because the test directory does not exist yet)**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/Subcommands/ParserTest.php`:

```php
<?php

use Kobylinski\Beetroot\Subcommands\Parser;
use Symfony\Component\Console\Input\InputArgument;

test('parses a flat signature with a required argument', function () {
    [$name, $arguments, $options] = Parser::parse('hello {who}');

    expect($name)->toBe('hello');
    expect($arguments)->toHaveCount(1);
    expect($arguments[0])->toBeInstanceOf(InputArgument::class);
    expect($arguments[0]->getName())->toBe('who');
    expect($arguments[0]->isRequired())->toBeTrue();
    expect($options)->toBe([]);
});
```

- [ ] **Step 5: Run the test and verify it passes**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest tests/Subcommands/ParserTest.php
```

Expected: `Tests: 1 passed` (green). If it fails with a class-not-found error, re-run `composer dump-autoload`. If it fails with a parse error inside `Parser.php`, stop — the Parser is already broken on this Laravel version and the engineer should skip ahead to Task 8 before writing more tests.

- [ ] **Step 6: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add phpunit.xml tests/
git commit -m "test: scaffold Pest with Testbench and a first Parser smoke test"
```

---

## Task 4: Expand Parser tests (the densest-coverage unit)

**Files:**
- Modify: `tests/Subcommands/ParserTest.php`

- [ ] **Step 1: Add the full Parser test file contents**

Replace the entire contents of `/Users/marek/Projects/garden/beetroot/tests/Subcommands/ParserTest.php` with:

```php
<?php

use Kobylinski\Beetroot\Subcommands\Parser;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

beforeEach(function () {
    // Some parser paths consult $_SERVER['argv']. Isolate each test.
    $this->originalArgv = $_SERVER['argv'] ?? [];
});

afterEach(function () {
    $_SERVER['argv'] = $this->originalArgv;
});

test('parses a flat signature with a required argument', function () {
    [$name, $arguments, $options] = Parser::parse('hello {who}');

    expect($name)->toBe('hello');
    expect($arguments)->toHaveCount(1);
    expect($arguments[0]->getName())->toBe('who');
    expect($arguments[0]->isRequired())->toBeTrue();
    expect($options)->toBe([]);
});

test('parses an optional argument with a default', function () {
    [, $arguments] = Parser::parse('hello {who=world}');

    expect($arguments[0]->isRequired())->toBeFalse();
    expect($arguments[0]->getDefault())->toBe('world');
});

test('parses an array argument', function () {
    [, $arguments] = Parser::parse('hello {names?*}');

    expect($arguments[0]->isArray())->toBeTrue();
    expect($arguments[0]->isRequired())->toBeFalse();
});

test('parses a long option', function () {
    [, $arguments, $options] = Parser::parse('hello {--force}');

    expect($arguments)->toBe([]);
    expect($options)->toHaveCount(1);
    expect($options[0])->toBeInstanceOf(InputOption::class);
    expect($options[0]->getName())->toBe('force');
});

test('parses a single-level subcommand group', function () {
    $_SERVER['argv'] = ['artisan', 'user', 'add'];

    [$name, $arguments, $options, $subcommands] = Parser::parse(
        'user {UserCommand (add {handle}) (remove {handle})}'
    );

    expect($name)->toBe('user');
    // The subcommand discriminator is stored as an argument.
    $names = array_map(fn($a) => $a->getName(), $arguments);
    expect($names)->toContain('UserCommand');
    expect($subcommands)->toHaveKey('UserCommand');
    // The "add" branch selected via $_SERVER['argv'] contributes its own arg.
    expect($names)->toContain('handle');
});

test('parses a subcommand alternation (suspend|restore)', function () {
    $_SERVER['argv'] = ['artisan', 'user', 'suspend'];

    [, $arguments, , $subcommands] = Parser::parse(
        'user {UserCommand (suspend|restore {handle})}'
    );

    expect($subcommands)->toHaveKey('UserCommand');
    $names = array_map(fn($a) => $a->getName(), $arguments);
    expect($names)->toContain('handle');
});

test('parses a default-star subcommand as optional', function () {
    $_SERVER['argv'] = ['artisan', 'user'];

    [, $arguments] = Parser::parse('user {UserCommand (*find {query?})}');

    $discriminator = collect($arguments)->firstWhere(
        fn($a) => $a->getName() === 'UserCommand'
    );
    expect($discriminator)->not()->toBeNull();
    expect($discriminator->isRequired())->toBeFalse();
    expect($discriminator->getDefault())->toBe('find');
});

test('parses nested two-level subcommands', function () {
    $_SERVER['argv'] = ['artisan', 'user', 'token', 'alice', 'add'];

    [, $arguments, , $subcommands] = Parser::parse('user
        {UserCommand
            (token
                {handle}
                {TokenCommand
                    (add {name})
                    (remove {name})
                })
        }');

    expect($subcommands)->toHaveKey('UserCommand');
    expect($subcommands)->toHaveKey('TokenCommand');
    $names = array_map(fn($a) => $a->getName(), $arguments);
    expect($names)->toContain('handle');
    expect($names)->toContain('name');
});

test('currentArgs skips flags, help and --', function () {
    $_SERVER['argv'] = ['artisan', 'user', '--verbose', 'add', '--', 'alice'];

    $args = Parser::currentArgs();

    expect($args)->not()->toContain('--verbose');
    expect($args)->not()->toContain('--');
    expect($args)->toContain('user');
    expect($args)->toContain('add');
});
```

- [ ] **Step 2: Run the Parser tests**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest tests/Subcommands/ParserTest.php
```

Expected: all 9 tests pass on the local Laravel version. If any fail, first re-read the test and the Parser code together — do not change the Parser's behaviour to match the test; the test is new code and may be wrong. If a failure looks like real drift (e.g. parent class signature changed), skip ahead to Task 8.

- [ ] **Step 3: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add tests/Subcommands/ParserTest.php
git commit -m "test: cover Parser flat, nested, alternation and default-star cases"
```

---

## Task 5: Test WithValidate

**Files:**
- Create: `tests/Stubs/AddUserCommand.php`
- Create: `tests/WithValidateTest.php`

- [ ] **Step 1: Write the fake command**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/Stubs/AddUserCommand.php`:

```php
<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithValidate;

class AddUserCommand extends Command
{
    use WithValidate;

    protected $signature = 'test:add-user {handle}';

    protected $description = 'Fake command for tests';

    protected function rules(): array
    {
        return [
            'handle' => ['required', 'string', 'min:3'],
        ];
    }

    protected function messages(): array
    {
        return [
            'handle.min' => 'The handle is too short.',
        ];
    }

    public function handle(): int
    {
        $this->line('ok');
        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Write the test**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/WithValidateTest.php`:

```php
<?php

use Kobylinski\Beetroot\Tests\Stubs\AddUserCommand;

beforeEach(function () {
    $this->app[\Illuminate\Contracts\Console\Kernel::class]->registerCommand(
        new AddUserCommand()
    );
});

test('valid input calls handle and returns success', function () {
    $this->artisan('test:add-user', ['handle' => 'alice'])
        ->expectsOutput('ok')
        ->assertExitCode(0);
});

test('invalid input surfaces validation errors and exits non-zero', function () {
    $this->artisan('test:add-user', ['handle' => 'ab'])
        ->assertExitCode(1);
});

test('custom messages override default text', function () {
    $this->artisan('test:add-user', ['handle' => 'ab'])
        ->expectsOutputToContain('The handle is too short.')
        ->assertExitCode(1);
});
```

- [ ] **Step 3: Run the WithValidate tests**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest tests/WithValidateTest.php
```

Expected: 3 tests pass. If Testbench can't find a default database migrator / app key, that's fine — these tests don't touch the DB. If a test complains about `expectsOutputToContain` (introduced in Laravel 10.5), upgrade Testbench; we require L10+.

- [ ] **Step 4: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add tests/Stubs/AddUserCommand.php tests/WithValidateTest.php
git commit -m "test: cover WithValidate success, failure, and custom messages"
```

---

## Task 6: Test WithNamedParameters

**Files:**
- Create: `tests/Stubs/MyCustomRule.php`
- Create: `tests/WithNamedParametersTest.php`

- [ ] **Step 1: Write the fake rule**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/Stubs/MyCustomRule.php`:

```php
<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Closure;
use Kobylinski\Beetroot\Attributes\NamedParameter\Flag;
use Kobylinski\Beetroot\Attributes\NamedParameter\Rule;
use Kobylinski\Beetroot\Attributes\NamedParameter\Sequence;
use Kobylinski\Beetroot\Attributes\NamedParameter\Value;
use Kobylinski\Beetroot\WithNamedParameters;

#[Rule('my_rule')]
class MyCustomRule
{
    use WithNamedParameters;

    #[Value('category', default: 'default_cat')]
    #[Value('mode', dictionary: ['strict', 'lenient'])]
    #[Flag('active', default: false)]
    #[Sequence('ids_to_exclude')]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->category !== 'expected' && $this->category !== 'default_cat') {
            $fail("bad category: {$this->category}");
        }
        if ($this->mode === 'strict' && strlen((string) $value) < 5) {
            $fail('too short for strict mode');
        }
        if ($this->active && is_array($this->ids_to_exclude) && in_array($value, $this->ids_to_exclude, true)) {
            $fail('excluded');
        }
    }
}
```

- [ ] **Step 2: Write the test**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/WithNamedParametersTest.php`:

```php
<?php

use Illuminate\Support\Facades\Validator;
use Kobylinski\Beetroot\Tests\Stubs\MyCustomRule;

beforeEach(function () {
    MyCustomRule::register();
});

test('named parameters map to class properties via attributes', function () {
    $v = Validator::make(
        ['field' => 'hello'],
        ['field' => 'my_rule:expected,strict,yes,1.2.3']
    );

    expect($v->fails())->toBeFalse();
});

test('strict mode rejects short values', function () {
    $v = Validator::make(
        ['field' => 'hi'],
        ['field' => 'my_rule:expected,strict']
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->first('field'))->toContain('too short');
});

test('dictionary rejects out-of-range values', function () {
    $v = Validator::make(
        ['field' => 'hello'],
        ['field' => 'my_rule:expected,unknown_mode']
    );

    // Value::adjust throws, Laravel wraps it in a ValidationException.
    expect(fn() => $v->passes())->toThrow(Exception::class);
});

test('defaults apply when parameters are omitted', function () {
    $v = Validator::make(
        ['field' => 'hello'],
        ['field' => 'my_rule']
    );

    // default_cat passes the category check, mode is null -> no strict check.
    expect($v->fails())->toBeFalse();
});

test('sequence parameter splits on dots', function () {
    $v = Validator::make(
        ['field' => '2'],
        ['field' => 'my_rule:expected,lenient,yes,1.2.3']
    );

    expect($v->fails())->toBeTrue();
    expect($v->errors()->first('field'))->toContain('excluded');
});
```

- [ ] **Step 3: Run the tests**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest tests/WithNamedParametersTest.php
```

Expected: 5 tests pass.

- [ ] **Step 4: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add tests/Stubs/MyCustomRule.php tests/WithNamedParametersTest.php
git commit -m "test: cover WithNamedParameters mapping, defaults, dictionary, sequence"
```

---

## Task 7: Test WithSubcommands

**Files:**
- Create: `tests/Stubs/UserCommand.php`
- Create: `tests/WithSubcommandsTest.php`

- [ ] **Step 1: Write the fake command**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/Stubs/UserCommand.php`:

```php
<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithSubcommands;

class UserCommand extends Command
{
    use WithSubcommands;

    protected $signature = 'user
        {UserCommand
            (add {handle})
            (remove {handle})
        }';

    protected $description = 'Fake subcommand host';

    public function handle(): int
    {
        $branch = $this->argument('UserCommand');
        $this->line("branch={$branch} handle=" . $this->argument('handle'));
        return self::SUCCESS;
    }
}
```

- [ ] **Step 2: Write the test**

Full contents of `/Users/marek/Projects/garden/beetroot/tests/WithSubcommandsTest.php`:

```php
<?php

use Kobylinski\Beetroot\Tests\Stubs\UserCommand;

beforeEach(function () {
    // WithSubcommands::configureUsingFluentDefinition reads $_SERVER['argv']
    // via Parser::currentArgs, so simulate the CLI invocation.
    $_SERVER['argv'] = ['artisan', 'user', 'add', 'alice'];

    $this->app[\Illuminate\Contracts\Console\Kernel::class]->registerCommand(
        new UserCommand()
    );
});

afterEach(function () {
    $_SERVER['argv'] = ['artisan'];
});

test('runs the correct subcommand branch', function () {
    $this->artisan('user', ['UserCommand' => 'add', 'handle' => 'alice'])
        ->expectsOutput('branch=add handle=alice')
        ->assertExitCode(0);
});

test('input definition contains the branch-specific argument', function () {
    $command = new UserCommand();
    $definition = $command->getDefinition();

    expect($definition->hasArgument('UserCommand'))->toBeTrue();
    expect($definition->hasArgument('handle'))->toBeTrue();
});
```

- [ ] **Step 3: Run the tests**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest tests/WithSubcommandsTest.php
```

Expected: 2 tests pass.

- [ ] **Step 4: Run the full suite**

```bash
cd /Users/marek/Projects/garden/beetroot
vendor/bin/pest
```

Expected: **all** tests pass on the local Laravel version.

- [ ] **Step 5: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git add tests/Stubs/UserCommand.php tests/WithSubcommandsTest.php
git commit -m "test: cover WithSubcommands branch execution and definition"
```

---

## Task 8: Add the matrix CI workflow

**Files:**
- Create: `.github/workflows/tests.yml`

- [ ] **Step 1: Write the workflow**

Full contents of `/Users/marek/Projects/garden/beetroot/.github/workflows/tests.yml`:

```yaml
name: tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    name: PHP ${{ matrix.php }} - Laravel ${{ matrix.laravel }}
    runs-on: ubuntu-latest

    strategy:
      fail-fast: false
      matrix:
        php: ['8.1', '8.2', '8.3', '8.4']
        laravel: ['10.*', '11.*', '12.*', '13.*']
        include:
          - laravel: '10.*'
            testbench: '8.*'
          - laravel: '11.*'
            testbench: '9.*'
          - laravel: '12.*'
            testbench: '10.*'
          - laravel: '13.*'
            testbench: '11.*'
        exclude:
          - php: '8.1'
            laravel: '11.*'
          - php: '8.1'
            laravel: '12.*'
          - php: '8.1'
            laravel: '13.*'
          - php: '8.2'
            laravel: '13.*'
          - php: '8.4'
            laravel: '10.*'

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none
          tools: composer:v2

      - name: Pin Laravel ${{ matrix.laravel }} and Testbench ${{ matrix.testbench }}
        run: |
          composer require \
            "illuminate/console:${{ matrix.laravel }}" \
            "illuminate/support:${{ matrix.laravel }}" \
            "illuminate/validation:${{ matrix.laravel }}" \
            "orchestra/testbench:${{ matrix.testbench }}" \
            --no-interaction --no-update

      - name: Install dependencies
        run: composer update --prefer-dist --no-progress

      - name: Run tests
        run: vendor/bin/pest
```

Notes for the engineer:
- The matrix `include:` block only adds fields to existing cells (pairs testbench with each Laravel) — it does not add new cells. The `exclude:` block trims the full `4×4` grid down to the 11 valid cells.
- We pin `illuminate/*` rather than `laravel/framework` because that's what the package actually depends on — `laravel/framework` is not in `require`.

- [ ] **Step 2: Branch, commit, and push**

If the engineer is still on `main`, the previous tasks' commits all sit on `main`. Move them to a feature branch before adding CI:

```bash
cd /Users/marek/Projects/garden/beetroot
git checkout -b feat/laravel-12-13-support
git add .github/workflows/tests.yml
git commit -m "ci: add {PHP 8.1..8.4} x {L10..L13} Pest matrix"
git push -u origin feat/laravel-12-13-support
```

If the engineer was already on a feature branch from Task 1, skip the `checkout -b` and just do the add/commit/push.

- [ ] **Step 3: Watch CI**

Open the Actions tab in the GitHub UI and wait for all 11 matrix cells to finish. Failures on this first run are expected; the goal is to see *which* cells fail.

- [ ] **Step 4: Triage results**

Three possible outcomes:

1. **All 11 jobs green** — skip Task 9, go straight to Task 10.
2. **Only Laravel 10 jobs fail** — highly unlikely (code works there today). If so, inspect the error, revert the composer constraint to `^10|^11`, and stop to reconsider. The tests or Testbench version mapping probably needs a fix.
3. **Laravel 12 and/or 13 jobs fail** — the expected case. Proceed to Task 9.

---

## Task 9: Fix source-code breakage surfaced by CI (contingent)

**Files:**
- Most likely: `src/Subcommands/Parser.php`
- Less likely: `src/WithValidate.php`, `src/WithNamedParameters.php`, `src/WithSubcommands.php`

This task runs **only if** Task 8 revealed failures. If CI is already green, skip to Task 10.

- [ ] **Step 1: Capture the exact failure**

From the first red matrix cell, copy:
- The PHP/Laravel combination.
- The failing test name.
- The top of the stack trace (class + method).

Paste all three into the commit message of the fix so future readers know which version surfaced the break.

- [ ] **Step 2: Read the parent class on the failing Laravel version**

For Parser drift, the parent is `Illuminate\Console\Parser`. Pull the version-specific source from the failing job's `vendor/`:

```bash
cd /Users/marek/Projects/garden/beetroot
composer require "illuminate/console:<exact-failing-version>" --no-interaction --no-update
composer update --prefer-dist --no-progress
cat vendor/laravel/framework/src/Illuminate/Console/Parser.php
```

Compare that to the version beetroot was written against. Look specifically at:
- Method visibility (`protected` vs `private`).
- Method signatures (added parameters, return types).
- Behaviour of `name()`, `parseArgument()`, `parseOption()` — the three parent methods the subclass uses.

- [ ] **Step 3: Choose the fix and apply it**

One of three shapes, in order of preference:

**Shape A (preferred):** Add a compatibility shim inside `src/Subcommands/Parser.php`. Example pattern if, say, `parseArgument` moved from `protected` to `private`:

```php
protected static function parseArgument(string $token)
{
    // Compatibility shim: on Laravel N the parent hid this method;
    // fall back to copying the same logic inline.
    if (method_exists(BaseParser::class, 'parseArgument')) {
        return parent::parseArgument($token);
    }
    // Inlined copy (keep in sync with framework if it changes again):
    // ... minimal reproduction of the framework logic ...
}
```

**Shape B:** Adjust a method signature (e.g. add a return type) to match the newer parent.

**Shape C (last resort):** Compose instead of inherit — instantiate `BaseParser` internally rather than extending it.

- [ ] **Step 4: Re-run tests locally against the failing version**

```bash
cd /Users/marek/Projects/garden/beetroot
composer require \
  "illuminate/console:<failing-version>" \
  "illuminate/support:<failing-version>" \
  "illuminate/validation:<failing-version>" \
  --no-interaction
vendor/bin/pest
```

Expected: all tests pass locally. Then restore the normal constraint:

```bash
git checkout composer.json
composer update --prefer-dist --no-progress
```

- [ ] **Step 5: Commit and push**

```bash
cd /Users/marek/Projects/garden/beetroot
git add src/
git commit -m "fix: compatibility with Laravel <version> <component>"
git push
```

- [ ] **Step 6: Watch CI; loop back to Step 1 for each new failure**

Iterate until the matrix is all green.

---

## Task 10: Merge and tag the release

- [ ] **Step 1: Open a PR**

```bash
cd /Users/marek/Projects/garden/beetroot
gh pr create --title "Support Laravel 12 and 13" --body "$(cat <<'EOF'
## Summary
- Widen composer constraint to `^10|^11|^12|^13`, PHP floor `>=8.1`.
- Add Pest test suite covering Parser, WithValidate, WithNamedParameters, WithSubcommands.
- Add GitHub Actions matrix (11 cells) for `{PHP 8.1..8.4} × {Laravel 10..13}`.
- Remove `composer.lock` from git (this is a library).

## Test plan
- [x] Matrix is green on GitHub Actions
- [ ] Tag a release after merge
EOF
)"
```

- [ ] **Step 2: Merge once green**

Squash-merge via the GitHub UI, or:

```bash
cd /Users/marek/Projects/garden/beetroot
gh pr merge --squash --delete-branch
```

- [ ] **Step 3: Tag a release**

Confirm the intended version with the user before tagging. If the latest tag is `v1.0.x`, the new tag is `v1.1.0` (minor bump — new supported Laravel majors is a feature, not a breaking change for existing users). If no tags exist yet, use `v1.0.0`.

```bash
cd /Users/marek/Projects/garden/beetroot
git checkout main
git pull
git tag -a v1.1.0 -m "Support Laravel 10/11/12/13"
git push origin v1.1.0
```

Packagist picks up the new tag via its GitHub webhook. If Packagist doesn't refresh within a few minutes, trigger a manual update on the package's Packagist page.

---

## Task 11: Add Dependabot configuration

**Files:**
- Create: `.github/dependabot.yml`

- [ ] **Step 1: Write the config**

Full contents of `/Users/marek/Projects/garden/beetroot/.github/dependabot.yml`:

```yaml
version: 2
updates:
  - package-ecosystem: composer
    directory: /
    schedule:
      interval: weekly
    open-pull-requests-limit: 5
    labels:
      - dependencies

  - package-ecosystem: github-actions
    directory: /
    schedule:
      interval: weekly
    labels:
      - dependencies
      - ci
```

- [ ] **Step 2: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git checkout -b chore/dependabot
git add .github/dependabot.yml
git commit -m "chore: enable Dependabot for composer and github-actions"
gh pr create --title "Enable Dependabot" --body "Weekly updates for composer + github-actions." --fill
```

After merge, confirm in the GitHub UI under Insights → Dependency graph → Dependabot that the config is recognized. Dependabot opens its first PR within a day.

---

## Task 12: Add the Copilot-ready Laravel upgrade issue template

**Files:**
- Create: `.github/ISSUE_TEMPLATE/laravel-upgrade.md`

- [ ] **Step 1: Write the template**

Full contents of `/Users/marek/Projects/garden/beetroot/.github/ISSUE_TEMPLATE/laravel-upgrade.md`:

```markdown
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
```

- [ ] **Step 2: Commit**

```bash
cd /Users/marek/Projects/garden/beetroot
git checkout -b chore/laravel-upgrade-template
git add .github/ISSUE_TEMPLATE/laravel-upgrade.md
git commit -m "chore: add Copilot-ready Laravel upgrade issue template"
gh pr create --title "Issue template for Laravel upgrades" --body "Reusable for future Laravel majors." --fill
```

---

## Task 13: Out-of-band repository configuration

**No files to edit.** These settings live in the GitHub UI and cannot be committed; run them after Task 12 is merged.

- [ ] **Step 1: Require the matrix to pass before merge**

GitHub UI path: `Settings → Branches → Branch protection rules → Add rule`
- Branch name pattern: `main`.
- Check "Require a pull request before merging".
- Check "Require status checks to pass before merging".
- Search for and tick every `tests / PHP X.Y - Laravel Z.*` check that appears after one successful matrix run (11 checks total).
- Save.

- [ ] **Step 2: Enable Copilot coding agent on the repository**

GitHub UI path: `Settings → Code & automation → Copilot` (exact label may be `Copilot coding agent` — GitHub moves it around).
- Confirm the account has a Copilot Pro / Pro+ / Business subscription that includes the coding agent (UI will say "unavailable" if not).
- Toggle on for this repository.

- [ ] **Step 3: (Optional) Allow auto-merge for green Dependabot PRs**

GitHub UI path: `Settings → General → Pull Requests → Allow auto-merge`. Turn on.

This lets Dependabot mark PRs as auto-merge; they squash-merge themselves once the matrix is green. Minor+patch bumps of dev deps get handled without human action.

- [ ] **Step 4: Sanity-check the flow end-to-end**

Open a dummy issue using the `laravel-upgrade.md` template, substituting a real-ish target (e.g. "Laravel 13 backport"), and assign it to `@copilot`. Confirm the agent picks it up and opens a PR. Close both the PR and the issue without merging — this is only a smoke test of the wiring.

---

## Self-review checklist

Before handing off:

- [ ] Every task references exact file paths.
- [ ] Every code step shows the full code to write or edit.
- [ ] `composer.json` in Task 2 matches the spec.
- [ ] Matrix cells in Task 8 match the design doc's table (11 cells).
- [ ] Test stub class names in Tasks 5, 6, 7 match how they're `use`d in the corresponding test files.
- [ ] No task references a symbol not defined in this plan or in `src/`.
- [ ] Tasks 9 and 13 are the only non-deterministic ones; both explain what the engineer must observe before acting.
