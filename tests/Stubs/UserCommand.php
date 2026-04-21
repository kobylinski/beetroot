<?php

namespace Kobylinski\Beetroot\Tests\Stubs;

use Illuminate\Console\Command;
use Kobylinski\Beetroot\WithSubcommands;

class UserCommand extends Command
{
    use WithSubcommands;

    protected $signature = 'user
        {UserCommand
            (add {handle})
            (remove {handle})
        }';

    protected $description = 'Fake subcommand host';

    public function handle(): int
    {
        $branch = $this->argument('UserCommand');
        $this->line("branch={$branch} handle=" . $this->argument('handle'));
        return self::SUCCESS;
    }
}
