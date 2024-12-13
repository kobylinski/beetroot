<?php

namespace Kobylinski\Beetroot\Attributes\NamedParameter;

use Attribute;
use Exception;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Value extends NamedParameter
{
  public function __construct(
    public readonly string $name,
    public readonly ?string $default = null,
    public readonly ?array $dictionary = null
  ) {
  }

  public function adjust(string $value): mixed
  {
    if (empty($value)) {
      return $this->default;
    }
    if (null !== $this->dictionary && !in_array($value, $this->dictionary)) {
      throw new Exception(
        "Value: $value is not allowend in validator configuration."
      );
    }
    return $value;
  }
}
