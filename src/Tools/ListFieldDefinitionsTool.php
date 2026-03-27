<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Qm\Models\QmFieldDefinition;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListFieldDefinitionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-definitions.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/field-definitions - Listet Feld-Definitionen. Optional: field_type_id Filter.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID.',
                    ],
                    'field_type_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Feldtyp-ID.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $query = QmFieldDefinition::query()
                ->with('fieldType')
                ->forTeam($teamId);

            if (isset($arguments['field_type_id'])) {
                $query->where('qm_field_type_id', (int)$arguments['field_type_id']);
            }

            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['title', 'created_at', 'updated_at'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (QmFieldDefinition $fd) {
                return [
                    'id' => $fd->id,
                    'uuid' => $fd->uuid,
                    'title' => $fd->title,
                    'description' => $fd->description,
                    'field_type' => [
                        'id' => $fd->fieldType->id,
                        'key' => $fd->fieldType->key,
                        'label' => $fd->fieldType->label,
                    ],
                    'config' => $fd->config,
                    'validation_rules' => $fd->validation_rules,
                    'created_at' => $fd->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Feld-Definitionen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'field-definitions', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
