<?php

namespace Kobylinski\Beetroot\Attributes\NamedParameter;

abstract class NamedParameter
{
  abstract function adjust(string $value): mixed;
}
