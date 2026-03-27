<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CompleteInstanceTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.complete.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/instances/:id/complete - Schliesst eine QM Instanz ab. ERFORDERLICH: id. Setzt Status auf "completed", berechnet Score, speichert completed_at.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Instanz (ERFORDERLICH).',
                ],
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

            $instance = QmInstance::forTeam($teamId)->find((int)$arguments['id']);
            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            if ($instance->status === 'completed') {
                return ToolResult::error('VALIDATION_ERROR', 'Instanz ist bereits abgeschlossen.');
            }

            if ($instance->status === 'cancelled') {
                return ToolResult::error('VALIDATION_ERROR', 'Abgebrochene Instanz kann nicht abgeschlossen werden.');
            }

            $service = new QmInstanceService();
            $instance = $service->complete($instance, $context->user->id);
            $stats = $service->getCompletionStats($instance);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'status' => $instance->status,
                'score' => $instance->score,
                'completion_stats' => $stats,
                'completed_at' => $instance->completed_at?->toISOString(),
                'message' => 'Instanz abgeschlossen. Score: ' . ($instance->score ?? 0) . '%.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Abschliessen der Instanz: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'complete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
