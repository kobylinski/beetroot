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
    $names = array_map(fn($a) => $a->getName(), $arguments);
    expect($names)->toContain('UserCommand');
    expect($subcommands)->toHaveKey('UserCommand');
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
