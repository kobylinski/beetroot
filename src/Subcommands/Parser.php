<?php

namespace Kobylinski\Beetroot\Subcommands;

use Illuminate\Console\Parser as BaseParser;
use Symfony\Component\Console\Input\InputArgument;

class Parser extends BaseParser
{
  public static function parse(string $expression)
  {
    $name = static::name($expression);

    // Match nested subcommands or the regular parameters
    if (
      preg_match_all("/\{(?:[^{}]*|(?R))*\}/", $expression, $matches) &&
      count($matches[0])
    ) {
      $parameters = static::parameters($matches[0]);
      return array_merge([$name], $parameters);
    }

    return [$name, [], []];
  }

  protected static function parameters(
    array $tokens,
    array $arguments = [],
    array $options = []
  ) {
    foreach ($tokens as $token) {
      // Check for nested subcommands
      $token = trim($token, " {}\t\n");
      $token = preg_replace("/[\s\n]+/", " ", $token);
      if (preg_match("/^\w+\s?\(/", $token)) {
        $name = static::name($token);
        $commands = static::parseSubcommand($name, $token);
        $arguments[] = $commands["argument"];
        $command = self::currentArgs(count($arguments));
        if ($command && isset($commands["commands"][$command])) {
          [$arguments, $options] = static::parameters(
            $commands["commands"][$command],
            $arguments,
            $options
          );
          $forceRequired = false;
          for ($i = count($arguments) - 1; $i >= 0; $i--) {
            if ($arguments[$i]->getName() === $name) {
              if ($forceRequired) {
                $arguments[$i] = new InputArgument(
                  $name,
                  InputArgument::REQUIRED,
                  $arguments[$i]->getDescription(),
                  null,
                  array_keys($commands["commands"])
                );
              }
              break;
            } else {
              if ($arguments[$i]->isRequired()) {
                $forceRequired = true;
              }
            }
          }
        }
      } elseif (preg_match("/^-{2,}(.*)/", $token, $matches)) {
        $options[] = static::parseOption($matches[1]);
      } else {
        $arguments[] = static::parseArgument($token);
      }
    }

    return [$arguments, $options];
  }

  protected static function parseSubcommand(string $name, string $token)
  {
    $commands = [];
    $default = null;
    $required = true;
    if (
      preg_match_all("/\((?:[^()]*|(?R))*\)/", $token, $matches) &&
      count($matches[0])
    ) {
      foreach ($matches[0] as $match) {
        $match = trim($match, " ()\t\n");
        $command = static::name($match);
        if (str_starts_with($command, "*")) {
          $command = substr($command, 1);
          $default = $command;

          $required = false;
        }
        $sharing = explode("|", $command);
        $definition = [];
        if (
          preg_match_all("/\{(?:[^{}]*|(?R))*\}/", $match, $subMatches) &&
          count($subMatches[0])
        ) {
          $definition = $subMatches[0];
        }
        foreach ($sharing as $command) {
          $command = trim($command);
          $commands[$command] = $definition;
        }
      }
    }
    return [
      "commands" => $commands,
      "argument" => new InputArgument(
        $name,
        $required ? InputArgument::REQUIRED : InputArgument::OPTIONAL,
        "One of: " . implode(", ", array_keys($commands)),
        $default,
        array_keys($commands)
      ),
    ];
  }

  private static function currentArgs(?int $index = null): array|string|null
  {
    $argv = [...(array) $_SERVER["argv"]];
    array_shift($argv);
    $args = array_filter(
      $argv,
      fn($arg) => !str_starts_with($arg, "-") &&
        $arg !== "--" &&
        $arg !== "help"
    );
    if (null !== $index) {
      if (isset($args[$index])) {
        return $args[$index];
      }
      return null;
    }
    return $args;
  }
}
