<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetDeviationTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.deviations.id.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/deviations/:id - Zeigt eine Abweichung mit allen Details, Workflow-Status und Zeitstempeln.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'id' => ['type' => 'integer', 'description' => 'ID der Abweichung (ERFORDERLICH).'],
            ],
            'required' => ['id'],
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

            $deviation = QmDeviation::with([
                'instance:id,uuid,title,status',
                'response.fieldDefinition:id,title',
                'createdByUser:id,name',
                'resolvedByUser:id,name',
                'acknowledgedByUser:id,name',
                'verifiedByUser:id,name',
            ])
                ->whereHas('instance', fn($q) => $q->where('team_id', $teamId))
                ->find((int)$arguments['id']);

            if (!$deviation) {
                return ToolResult::error('NOT_FOUND', 'Abweichung nicht gefunden.');
            }

            return ToolResult::success([
                'id' => $deviation->id,
                'uuid' => $deviation->uuid,
                'title' => $deviation->title,
                'description' => $deviation->description,
                'severity' => $deviation->severity,
                'status' => $deviation->status,
                'workflow_type' => $deviation->workflow_type,
                'corrective_action' => $deviation->corrective_action,
                'root_cause' => $deviation->root_cause,
                'preventive_action' => $deviation->preventive_action,
                'escalation_level' => $deviation->escalation_level,
                'instance' => $deviation->instance ? [
                    'id' => $deviation->instance->id,
                    'title' => $deviation->instance->title,
                    'status' => $deviation->instance->status,
                ] : null,
                'response_field' => $deviation->response?->fieldDefinition?->title,
                'created_by' => $deviation->createdByUser?->name,
                'acknowledged_by' => $deviation->acknowledgedByUser?->name,
                'acknowledged_at' => $deviation->acknowledged_at?->toISOString(),
                'resolved_by' => $deviation->resolvedByUser?->name,
                'resolved_at' => $deviation->resolved_at?->toISOString(),
                'verified_by' => $deviation->verifiedByUser?->name,
                'verified_at' => $deviation->verified_at?->toISOString(),
                'escalated_at' => $deviation->escalated_at?->toISOString(),
                'created_at' => $deviation->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Abweichung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read',
            'tags' => ['qm', 'deviations', 'detail'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
