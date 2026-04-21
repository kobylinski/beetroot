<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Closure;
use Kobylinski\Beetroot\Attributes\NamedParameter\Flag;
use Kobylinski\Beetroot\Attributes\NamedParameter\Rule;
use Kobylinski\Beetroot\Attributes\NamedParameter\Sequence;
use Kobylinski\Beetroot\Attributes\NamedParameter\Value;
use Kobylinski\Beetroot\WithNamedParameters;

#[Rule('my_rule')]
class MyCustomRule
{
    use WithNamedParameters;

    #[Value('category', default: 'default_cat')]
    #[Value('mode', dictionary: ['strict', 'lenient'])]
    #[Flag('active', default: false)]
    #[Sequence('ids_to_exclude')]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->category !== 'expected' && $this->category !== 'default_cat') {
            $fail("bad category: {$this->category}");
        }
        if ($this->mode === 'strict' && strlen((string) $value) < 5) {
            $fail('too short for strict mode');
        }
        if ($this->active && is_array($this->ids_to_exclude) && in_array($value, $this->ids_to_exclude, true)) {
            $fail('excluded');
        }
    }
}
