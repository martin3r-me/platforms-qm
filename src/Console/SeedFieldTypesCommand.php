<?php

namespace Platform\Qm\Console;

use Illuminate\Console\Command;
use Platform\Qm\Database\Seeders\QmFieldTypeSeeder;

class SeedFieldTypesCommand extends Command
{
    protected $signature = 'qm:seed-field-types';

    protected $description = 'Seeded die 17 System-Feldtypen (idempotent, kann beliebig oft ausgefuehrt werden).';

    public function handle(): int
    {
        $this->info('Seede QM System-Feldtypen...');

        $seeder = new QmFieldTypeSeeder();
        $seeder->run();

        $this->info('17 System-Feldtypen geseeded.');

        return self::SUCCESS;
    }
}
