<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetInstanceTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.id.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/instances/:id - Zeigt eine QM Instanz mit allen Responses, Snapshot und Completion-Stats.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
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

            $instance = QmInstance::with([
                'template:id,uuid,name,status,version',
                'responses.fieldDefinition:id,uuid,title',
                'responses.section:id,uuid,title',
                'responses.respondedByUser:id,name',
                'deviations',
                'createdByUser:id,name',
                'completedByUser:id,name',
            ])
                ->forTeam($teamId)
                ->find((int)$arguments['id']);

            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            $service = new QmInstanceService();
            $stats = $service->getCompletionStats($instance);

            $responses = $instance->responses->map(function ($r) {
                return [
                    'id' => $r->id,
                    'uuid' => $r->uuid,
                    'field_definition' => $r->fieldDefinition ? [
                        'id' => $r->fieldDefinition->id,
                        'title' => $r->fieldDefinition->title,
                    ] : null,
                    'section' => $r->section ? [
                        'id' => $r->section->id,
                        'title' => $r->section->title,
                    ] : null,
                    'value' => $r->value,
                    'is_deviation' => $r->is_deviation,
                    'notes' => $r->notes,
                    'responded_by' => $r->respondedByUser?->name,
                    'responded_at' => $r->responded_at?->toISOString(),
                ];
            })->toArray();

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'description' => $instance->description,
                'status' => $instance->status,
                'score' => $instance->score,
                'template' => $instance->template ? [
                    'id' => $instance->template->id,
                    'name' => $instance->template->name,
                    'version' => $instance->template->version,
                ] : null,
                'snapshot_data' => $instance->snapshot_data,
                'responses' => $responses,
                'deviations_count' => $instance->deviations->count(),
                'completion_stats' => $stats,
                'public_token' => $instance->public_token,
                'due_at' => $instance->due_at?->toISOString(),
                'completed_at' => $instance->completed_at?->toISOString(),
                'created_by' => $instance->createdByUser?->name,
                'completed_by' => $instance->completedByUser?->name,
                'created_at' => $instance->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Instanz: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'instances', 'detail'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
