<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmFieldDefinition;
use Platform\Qm\Models\QmSection;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class QmStatsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.stats.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/stats - Zeigt QM-Statistiken: Templates, Instanzen, Abweichungen, Scores, Trends.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'days' => ['type' => 'integer', 'description' => 'Optional: Zeitraum in Tagen fuer Trends. Default: 30.'],
            ],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];
            $days = (int)($arguments['days'] ?? 30);

            // Templates
            $templatesTotal = QmTemplate::forTeam($teamId)->count();
            $templatesActive = QmTemplate::forTeam($teamId)->byStatus('active')->count();

            // Instances
            $instancesTotal = QmInstance::forTeam($teamId)->count();
            $instancesOpen = QmInstance::forTeam($teamId)->byStatus('open')->count();
            $instancesInProgress = QmInstance::forTeam($teamId)->byStatus('in_progress')->count();
            $instancesCompleted = QmInstance::forTeam($teamId)->byStatus('completed')->count();
            $instancesOverdue = QmInstance::forTeam($teamId)->overdue()->count();

            // Recent instances
            $recentInstances = QmInstance::forTeam($teamId)
                ->where('created_at', '>=', now()->subDays($days))
                ->count();

            // Scores
            $avgScore = QmInstance::forTeam($teamId)
                ->byStatus('completed')
                ->whereNotNull('score')
                ->avg('score');

            // Deviations
            $deviationsTotal = QmDeviation::whereHas('instance', fn($q) => $q->where('team_id', $teamId))->count();
            $deviationsOpen = QmDeviation::whereHas('instance', fn($q) => $q->where('team_id', $teamId))->open()->count();
            $deviationsCritical = QmDeviation::whereHas('instance', fn($q) => $q->where('team_id', $teamId))->bySeverity('critical')->open()->count();

            // Building blocks
            $fieldDefinitions = QmFieldDefinition::where('team_id', $teamId)->count();
            $sections = QmSection::where('team_id', $teamId)->count();

            return ToolResult::success([
                'team_id' => $teamId,
                'period_days' => $days,
                'templates' => [
                    'total' => $templatesTotal,
                    'active' => $templatesActive,
                ],
                'instances' => [
                    'total' => $instancesTotal,
                    'open' => $instancesOpen,
                    'in_progress' => $instancesInProgress,
                    'completed' => $instancesCompleted,
                    'overdue' => $instancesOverdue,
                    'recent' => $recentInstances,
                ],
                'scores' => [
                    'average' => $avgScore ? round($avgScore, 1) : null,
                ],
                'deviations' => [
                    'total' => $deviationsTotal,
                    'open' => $deviationsOpen,
                    'critical_open' => $deviationsCritical,
                ],
                'building_blocks' => [
                    'field_definitions' => $fieldDefinitions,
                    'sections' => $sections,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Statistiken: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read',
            'tags' => ['qm', 'stats', 'analytics'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
