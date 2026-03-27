<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Services\QmFieldTypeService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetFieldTypeTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-type.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/field-types/{id} - Ruft einen einzelnen Feldtyp ab. Parameter: field_type_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'field_type_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Feldtyps (ERFORDERLICH).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
            ],
            'required' => ['field_type_id'],
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

            $id = (int)($arguments['field_type_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'field_type_id ist erforderlich.');
            }

            $service = new QmFieldTypeService();
            $type = $service->findForTeam($id, $teamId);

            if (!$type) {
                return ToolResult::error('NOT_FOUND', 'Feldtyp nicht gefunden (oder kein Zugriff).');
            }

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'key' => $type->key,
                'label' => $type->label,
                'description' => $type->description,
                'is_system' => $type->is_system,
                'default_config' => $type->default_config,
                'field_definitions_count' => $type->fieldDefinitions()->count(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Feldtyps: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'field-types', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
