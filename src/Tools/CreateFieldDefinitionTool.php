<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Services\QmFieldDefinitionService;
use Platform\Qm\Services\QmFieldTypeService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateFieldDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-definitions.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/field-definitions - Erstellt eine neue Feld-Definition. ERFORDERLICH: field_type_id, title. Optional: description, config, validation_rules, i18n.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'field_type_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Feldtyps (ERFORDERLICH). Nutze "qm.field-types.GET" um IDs zu finden.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel des Feldes (ERFORDERLICH). z.B. "Kerntemperatur Fleisch".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung/Hilfetext.',
                ],
                'config' => [
                    'type' => 'object',
                    'description' => 'Optional: Typ-spezifische Konfiguration (ueberschreibt default_config des FieldType). z.B. {"unit": "°C", "min": -20, "max": 200}.',
                ],
                'validation_rules' => [
                    'type' => 'object',
                    'description' => 'Optional: Validierungsregeln. z.B. {"required": true, "min": 0, "max": 100}.',
                ],
                'i18n' => [
                    'type' => 'object',
                    'description' => 'Optional: Uebersetzungen. z.B. {"en": {"title": "Core Temperature Meat"}}.',
                ],
            ],
            'required' => ['field_type_id', 'title'],
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

            $title = trim((string)($arguments['title'] ?? ''));
            $fieldTypeId = (int)($arguments['field_type_id'] ?? 0);

            if ($title === '' || $fieldTypeId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'field_type_id und title sind erforderlich.');
            }

            // Validate field type exists and is accessible
            $ftService = new QmFieldTypeService();
            $fieldType = $ftService->findForTeam($fieldTypeId, $teamId);
            if (!$fieldType) {
                return ToolResult::error('NOT_FOUND', 'Feldtyp nicht gefunden. Nutze "qm.field-types.GET".');
            }

            $service = new QmFieldDefinitionService();
            $fd = $service->create([
                'team_id' => $teamId,
                'qm_field_type_id' => $fieldTypeId,
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'config' => $arguments['config'] ?? null,
                'validation_rules' => $arguments['validation_rules'] ?? null,
                'i18n' => $arguments['i18n'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $fd->id,
                'uuid' => $fd->uuid,
                'title' => $fd->title,
                'field_type_key' => $fieldType->key,
                'message' => 'Feld-Definition erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Feld-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'field-definitions', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
