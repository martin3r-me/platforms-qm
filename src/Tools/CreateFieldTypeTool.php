<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Services\QmFieldTypeService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateFieldTypeTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-types.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/field-types - Erstellt einen neuen Custom-Feldtyp fuer das Team. ERFORDERLICH: key, label. Optional: description, default_config.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'key' => [
                    'type' => 'string',
                    'description' => 'Eindeutiger Schluessel (ERFORDERLICH). z.B. "custom_rating".',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Anzeigename (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'default_config' => [
                    'type' => 'object',
                    'description' => 'Optional: Standard-Konfiguration fuer diesen Typ.',
                ],
            ],
            'required' => ['key', 'label'],
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

            $key = trim((string)($arguments['key'] ?? ''));
            $label = trim((string)($arguments['label'] ?? ''));
            if ($key === '' || $label === '') {
                return ToolResult::error('VALIDATION_ERROR', 'key und label sind erforderlich.');
            }

            $service = new QmFieldTypeService();
            $type = $service->createCustomType([
                'key' => $key,
                'label' => $label,
                'description' => $arguments['description'] ?? null,
                'team_id' => $teamId,
                'default_config' => $arguments['default_config'] ?? null,
            ]);

            return ToolResult::success([
                'id' => $type->id,
                'uuid' => $type->uuid,
                'key' => $type->key,
                'label' => $type->label,
                'is_system' => false,
                'message' => 'Custom-Feldtyp erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Feldtyps: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'field-types', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
