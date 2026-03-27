<?php

namespace Platform\Qm\Console;

use Illuminate\Console\Command;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmDeviationService;

class ProcessEscalationsCommand extends Command
{
    protected $signature = 'qm:process-escalations';

    protected $description = 'Prueft offene Abweichungen und eskaliert automatisch basierend auf Template-Eskalationsregeln.';

    public function handle(): int
    {
        $this->info('Pruefe Eskalationen...');

        $escalated = 0;

        // Find open deviations that haven't been escalated recently
        $deviations = QmDeviation::query()
            ->open()
            ->with(['instance.template'])
            ->get();

        $service = new QmDeviationService();

        foreach ($deviations as $deviation) {
            $template = $deviation->instance?->template;
            if (!$template) {
                continue;
            }

            $settings = $template->settings ?? [];
            if (empty($settings['escalation_enabled'])) {
                continue;
            }

            $levels = $settings['escalation_levels'] ?? [];
            if (empty($levels)) {
                continue;
            }

            $currentLevel = $deviation->escalation_level ?? 0;
            $createdAt = $deviation->escalated_at ?? $deviation->created_at;

            foreach ($levels as $levelConfig) {
                $level = (int)($levelConfig['level'] ?? 0);
                $afterMinutes = (int)($levelConfig['after_minutes'] ?? 60);

                if ($level <= $currentLevel) {
                    continue;
                }

                if ($createdAt->diffInMinutes(now()) >= $afterMinutes) {
                    $service->escalate($deviation, $level);
                    $escalated++;
                    $this->line("  Abweichung #{$deviation->id} \"{$deviation->title}\" auf Level {$level} eskaliert.");
                    break;
                }
            }
        }

        if ($escalated > 0) {
            $this->info("{$escalated} Abweichung(en) eskaliert.");
        } else {
            $this->info('Keine Eskalationen notwendig.');
        }

        return self::SUCCESS;
    }
}
