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
