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

    // Value::adjust throws when a dictionary value is out-of-range.
    expect(fn() => $v->passes())->toThrow(Exception::class);
});

test('defaults apply when parameters are omitted', function () {
    $v = Validator::make(
        ['field' => 'hello'],
        ['field' => 'my_rule']
    );

    // default_cat passes the category check; mode is null so no strict check.
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
