<?php

namespace Kobylinski\Beetroot\Attributes\NamedParameter;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Flag extends NamedParameter
{
  public function __construct(
    public readonly string $name,
    public readonly bool $default = true,
    public readonly string $positive = "positive",
    public readonly string $negative = "negative"
  ) {
  }

  public function adjust(string $value): mixed
  {
    if (empty($value)) {
      return $this->default;
    }

    return match ($value) {
      "1", "true", "on", "yes", "enabled", $this->positive => true,
      "0", "false", "off", "no", "disabled", $this->negative => false,
    };
  }
}
