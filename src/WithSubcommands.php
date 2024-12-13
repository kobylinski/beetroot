<?php

namespace Kobylinski\Beetroot;

use Kobylinski\Beetroot\Subcommands\Parser;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

trait WithSubcommands
{
  /**
   * Configure the console command using a fluent definition.
   *
   * @return void
   */
  protected function configureUsingFluentDefinition()
  {
    [$name, $arguments, $options] = Parser::parse($this->signature);

    $reflection = new ReflectionMethod(Command::class, "__construct");
    $reflection->invoke($this, $this->name = $name);

    // After parsing the signature we will spin through the arguments and options
    // and set them on this command. These will already be changed into proper
    // instances of these "InputArgument" and "InputOption" Symfony classes.
    $this->getDefinition()->addArguments($arguments);
    $this->getDefinition()->addOptions($options);
  }
}
