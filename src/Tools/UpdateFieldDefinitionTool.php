<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmFieldDefinition;
use Platform\Qm\Services\QmFieldDefinitionService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class UpdateFieldDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-definitions.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/field-definitions/{id} - Aktualisiert eine Feld-Definition. Parameter: field_definition_id (required). Optional: title, description, config, validation_rules, i18n.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'field_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Feld-Definition (ERFORDERLICH).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'config' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Konfiguration.',
                ],
                'validation_rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Validierungsregeln.',
                ],
                'i18n' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Uebersetzungen.',
                ],
            ],
            'required' => ['field_definition_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $id = (int)($arguments['field_definition_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'field_definition_id ist erforderlich.');
            }

            $fd = QmFieldDefinition::query()->forTeam($teamId)->find($id);
            if (!$fd) {
                return ToolResult::error('NOT_FOUND', 'Feld-Definition nicht gefunden.');
            }

            $service = new QmFieldDefinitionService();
            $fd = $service->update($fd, $arguments);

            return ToolResult::success([
                'id' => $fd->id,
                'uuid' => $fd->uuid,
                'title' => $fd->title,
                'message' => 'Feld-Definition aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'field-definitions', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
