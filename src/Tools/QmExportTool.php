<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class QmExportTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.export.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/export - Exportiert eine Instanz als strukturiertes JSON mit allen Sektionen, Feldern und Antworten. ERFORDERLICH: instance_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'instance_id' => ['type' => 'integer', 'description' => 'ID der Instanz (ERFORDERLICH).'],
            ],
            'required' => ['instance_id'],
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
                'template:id,name,version',
                'responses.fieldDefinition:id,title',
                'responses.section:id,title',
                'responses.respondedByUser:id,name',
                'deviations.createdByUser:id,name',
                'createdByUser:id,name',
                'completedByUser:id,name',
            ])
                ->forTeam($teamId)
                ->find((int)$arguments['instance_id']);

            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            $snapshot = $instance->snapshot_data ?? [];
            $responsesMap = $instance->responses->keyBy('qm_field_definition_id');

            $exportSections = [];
            foreach ($snapshot['sections'] ?? [] as $section) {
                $fields = [];
                foreach ($section['fields'] ?? [] as $field) {
                    $response = $responsesMap->get($field['field_definition_id']);
                    $fields[] = [
                        'title' => $field['title'],
                        'field_type' => $field['field_type_key'] ?? null,
                        'is_required' => $field['is_required'] ?? false,
                        'value' => $response?->value,
                        'is_deviation' => $response?->is_deviation ?? false,
                        'notes' => $response?->notes,
                        'responded_by' => $response?->respondedByUser?->name,
                        'responded_at' => $response?->responded_at?->toISOString(),
                    ];
                }
                $exportSections[] = [
                    'title' => $section['title'],
                    'fields' => $fields,
                ];
            }

            $exportDeviations = $instance->deviations->map(function ($d) {
                return [
                    'title' => $d->title,
                    'severity' => $d->severity,
                    'status' => $d->status,
                    'corrective_action' => $d->corrective_action,
                    'root_cause' => $d->root_cause,
                    'created_at' => $d->created_at?->toISOString(),
                ];
            })->toArray();

            return ToolResult::success([
                'export' => [
                    'instance' => [
                        'title' => $instance->title,
                        'status' => $instance->status,
                        'score' => $instance->score,
                        'created_by' => $instance->createdByUser?->name,
                        'created_at' => $instance->created_at?->toISOString(),
                        'completed_by' => $instance->completedByUser?->name,
                        'completed_at' => $instance->completed_at?->toISOString(),
                    ],
                    'template' => $instance->template ? [
                        'name' => $instance->template->name,
                        'version' => $instance->template->version,
                    ] : null,
                    'sections' => $exportSections,
                    'deviations' => $exportDeviations,
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Exportieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read',
            'tags' => ['qm', 'export', 'analytics'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
