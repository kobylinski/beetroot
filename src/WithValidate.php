<?php

namespace Kobylinski\Beetroot;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

trait WithValidate
{
  abstract protected function rules(): array;

  /**
   * Tests command input and pass to handle method or print validate errors
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   */
  protected function execute(
    InputInterface $input,
    OutputInterface $output
  ): int {
    $validator = Validator::make(
      collect($this->arguments())
        ->merge($this->options())
        ->filter(function ($value) {
          return !empty($value);
        })
        ->toArray(),
      $this->rules(),
      $this->messages()
    );

    if ($validator->fails()) {
      $this->output->newLine();
      foreach ($validator->errors()->all() as $error) {
        $this->output->writeln("  <fg=red>✖</fg=red> {$error}");
      }
      $this->output->newLine();
      return 1;
    }

    return parent::execute($input, $output);
  }

  protected function messages(): array
  {
    return [];
  }
}
