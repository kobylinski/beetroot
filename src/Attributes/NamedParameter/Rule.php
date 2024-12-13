<?php

namespace Kobylinski\Beetroot\Attributes\NamedParameter;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Rule
{
  public function __construct(public readonly string $name)
  {
  }
}
