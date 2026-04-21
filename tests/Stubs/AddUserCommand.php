<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithValidate;

class AddUserCommand extends Command
{
    use WithValidate;

    protected $signature = 'test:add-user {handle}';

    protected $description = 'Fake command for tests';

    protected function rules(): array
    {
        return [
            'handle' => ['required', 'string', 'min:3'],
        ];
    }

    protected function messages(): array
    {
        return [
            'handle.min' => 'The handle is too short.',
        ];
    }

    public function handle(): int
    {
        $this->line('ok');
        return self::SUCCESS;
    }
}
