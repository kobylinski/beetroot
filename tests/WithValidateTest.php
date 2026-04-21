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
