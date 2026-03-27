<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Services\QmDeviationService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class EscalateDeviationTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.deviations.escalate.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/deviations/:id/escalate - Eskaliert eine Abweichung auf das naechste Level. ERFORDERLICH: id. Optional: level (int, default: aktuelles Level + 1).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'id' => ['type' => 'integer', 'description' => 'ID der Abweichung (ERFORDERLICH).'],
                'level' => ['type' => 'integer', 'description' => 'Optional: Eskalationsstufe. Default: aktuelles Level + 1.'],
            ],
            'required' => ['id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $deviation = QmDeviation::whereHas('instance', fn($q) => $q->where('team_id', $teamId))
                ->find((int)$arguments['id']);

            if (!$deviation) {
                return ToolResult::error('NOT_FOUND', 'Abweichung nicht gefunden.');
            }

            if (in_array($deviation->status, ['resolved', 'verified'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Abweichung ist bereits abgeschlossen.');
            }

            $level = (int)($arguments['level'] ?? (($deviation->escalation_level ?? 0) + 1));

            $service = new QmDeviationService();
            $deviation = $service->escalate($deviation, $level);

            return ToolResult::success([
                'id' => $deviation->id,
                'uuid' => $deviation->uuid,
                'title' => $deviation->title,
                'escalation_level' => $deviation->escalation_level,
                'escalated_at' => $deviation->escalated_at?->toISOString(),
                'message' => "Abweichung auf Level {$level} eskaliert.",
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Eskalieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action',
            'tags' => ['qm', 'deviations', 'escalate'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => false,
        ];
    }
}
