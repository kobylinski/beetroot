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
