<?php

namespace Kobylinski\Beetroot;

use Kobylinski\Beetroot\Subcommands\Parser;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

trait WithSubcommands
{
  /**
   * Helper property to store subcommands with their index on the signature
   *
   * @var array<string, int>
   */
  private array $subcommands = [];

  /**
   * Overwrite the default configure method to include subcommands
   *
   * @return void
   */
  protected function configureUsingFluentDefinition()
  {
    [$name, $arguments, $options, $this->subcommands] = Parser::parse($this->signature);

    $reflection = new ReflectionMethod(Command::class, "__construct");
    $reflection->invoke($this, $this->name = $name);

    // After parsing the signature we will spin through the arguments and options
    // and set them on this command. These will already be changed into proper
    // instances of these "InputArgument" and "InputOption" Symfony classes.
    $this->getDefinition()->addArguments($arguments);
    $this->getDefinition()->addOptions($options);
  }

  /**
   * Overwrite the default getSynopsis method to include subcommands
   *
   * @param bool $short Whether to show the short version of the synopsis
   *
   * @return string The synopsis
   */
  public function getSynopsis(bool $short = false): string
  {
    $synopsis = parent::getSynopsis($short);
    foreach($this->subcommands as $subcommand => $index) {
      $current = Parser::currentArgs($index);
      if( $current ){
        $synopsis = str_replace([
          "<{$subcommand}>",
          "[<{$subcommand}>]"
        ], $current, $synopsis);
      }
    }

    return $synopsis;
  }
}
