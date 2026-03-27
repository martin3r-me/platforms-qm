<?php

namespace Platform\Qm\Console;

use Illuminate\Console\Command;
use Platform\Qm\Services\QmRecurrenceService;

class ProcessRecurrencesCommand extends Command
{
    protected $signature = 'qm:process-recurrences';

    protected $description = 'Verarbeitet wiederkehrende QM-Checklisten und erstellt neue Instanzen wenn faellig.';

    public function handle(): int
    {
        $this->info('Verarbeite wiederkehrende QM-Checklisten...');

        $service = new QmRecurrenceService();
        $created = $service->processRecurrences();

        if ($created > 0) {
            $this->info("{$created} neue Instanz(en) erstellt.");
        } else {
            $this->info('Keine faelligen Wiederholungen gefunden.');
        }

        return self::SUCCESS;
    }
}
