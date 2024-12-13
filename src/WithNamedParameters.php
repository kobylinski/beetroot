<?php

namespace Kobylinski\Beetroot;

use Kobylinski\Beetroot\Attributes\NamedParameter\NamedParameter;
use Kobylinski\Beetroot\Attributes\NamedParameter\Rule;
use Closure;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * Helps to read validator configuration
 */
trait WithNamedParameters
{
  /**
   * The rule names for the validator.
   *
   * @var string[]
   */
  private static array $names = [];

  /**
   * The parameters definition for the rule.
   *
   * @var array
   */
  private array $definition = [];

  /**
   * The parameters for the rule.
   *
   * @var array
   */
  private array $parameters = [];

  /**
   * Indicates if the validation invokable failed.
   *
   * @var bool
   */
  private bool $failed = false;

  /**
   * The current validator.
   *
   * @var Validator
   */
  private Validator $validator;

  /**
   * Access to configuration of validator from the rule.
   *
   * @param string $name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->parameters[$name] ?? null;
  }

  /**
   * Setup the parameters for the rule.
   *
   * @return void
   */
  protected function setupParameters(): void
  {
    $this->definition = [];
    $ref = new ReflectionMethod($this, "validate");
    /** @var ReflectionAttribute $attribute */
    foreach (
      $ref->getAttributes(
        NamedParameter::class,
        ReflectionAttribute::IS_INSTANCEOF
      )
      as $attribute
    ) {
      $this->definition[] = $attribute->newInstance();
    }
  }

  /**
   * Read the parameters from the provided array.
   *
   * @param array $provided
   * @return array
   */
  protected function loadParameters(array $provided): array
  {
    $result = [];
    foreach ($this->definition as $index => $config) {
      if (isset($provided[$index]) && !empty($provided[$index])) {
        $result[$config->name] = $config->adjust($provided[$index]);
      } else {
        $result[$config->name] = $config->default;
      }
    }
    return $result;
  }

  /**
   * Placeholder for the validation logic.
   *
   * @param string $attribute
   * @param mixed $value
   * @param Closure(string, ?string=): void $fail
   * @return void
   */
  abstract public function validate(
    string $attribute,
    mixed $value,
    Closure $fail
  ): void;

  /**
   * Bootstrap and run the rule.
   *
   * @param string $attribute
   * @param mixed $value
   * @param array $parameters
   * @param Validator $validator
   * @return boolean
   */
  public function passes(
    $attribute,
    $value,
    $parameters,
    Validator $validator
  ): bool {
    $this->setupParameters();
    $this->parameters = $this->loadParameters($parameters);
    $this->validate($attribute, $value, function (?string $message = null) use (
      $attribute,
      $validator,
      $parameters
    ) {
      if (null !== $message) {
        $validator->setCustomMessages([
          $attribute => $message,
        ]);
      }
      $validator->addFailure(
        $attribute,
        static::$names[static::class],
        $parameters
      );
      $this->failed = true;
    });
    return !$this->failed;
  }

  /**
   * Replace all place-holders for the validator message.
   *
   * @param string $message
   * @param string $attribute
   * @param string $rule
   * @param array $parameters
   * @return string
   */
  public function replacer($message, $attribute, $rule, $parameters): string
  {
    foreach ($parameters as $key => $value) {
      $message = str_replace(':' . $key, $value, $message);
    }
    return $message;
  }

  /**
   * Register the rule with the validator.
   *
   * @return void
   */
  public static function register(): void
  {
    $ref = new ReflectionClass(static::class);
    $attributes = $ref->getAttributes(Rule::class);
    if (empty($attributes)) {
      $name = Str::of(class_basename(static::class))->snake();
    } else {
      $name = $attributes[0]->newInstance()->name;
    }
    static::$names[static::class] = $name;
    ValidatorFacade::extend($name, static::class . "@passes");
    ValidatorFacade::replacer($name, static::class . "@replacer");
  }
}
