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

class VerifyDeviationTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.deviations.verify.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/deviations/:id/verify - Verifiziert eine abgeschlossene Abweichung (HACCP full Workflow). ERFORDERLICH: id. Setzt Status auf "verified".';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'id' => ['type' => 'integer', 'description' => 'ID der Abweichung (ERFORDERLICH).'],
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

            if ($deviation->status !== 'resolved') {
                return ToolResult::error('VALIDATION_ERROR', 'Nur abgeschlossene Abweichungen koennen verifiziert werden. Aktueller Status: ' . $deviation->status);
            }

            $service = new QmDeviationService();
            $deviation = $service->verify($deviation, $context->user->id);

            return ToolResult::success([
                'id' => $deviation->id,
                'uuid' => $deviation->uuid,
                'title' => $deviation->title,
                'status' => $deviation->status,
                'verified_at' => $deviation->verified_at?->toISOString(),
                'message' => 'Abweichung verifiziert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verifizieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action',
            'tags' => ['qm', 'deviations', 'verify'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
