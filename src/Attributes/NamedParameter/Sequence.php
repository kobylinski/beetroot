<?php

namespace Kobylinski\Beetroot\Attributes\NamedParameter;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Sequence extends NamedParameter
{
  public function __construct(
    public readonly string $name,
    public readonly ?array $default = null
  ) {
  }

  public function adjust(string $value): mixed
  {
    if (empty($value)) {
      return $this->default;
    }
    return explode(".", $value);
  }
}
