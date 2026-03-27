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

class DeleteFieldDefinitionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-definitions.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /qm/field-definitions/{id} - Soft-loescht eine Feld-Definition. Fehlschlag wenn in Sektionen verwendet. Parameter: field_definition_id, confirm (required=true).';
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
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um zu loeschen.',
                ],
            ],
            'required' => ['field_definition_id', 'confirm'],
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

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');
            }

            $id = (int)($arguments['field_definition_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'field_definition_id ist erforderlich.');
            }

            $fd = QmFieldDefinition::query()->forTeam($teamId)->find($id);
            if (!$fd) {
                return ToolResult::error('NOT_FOUND', 'Feld-Definition nicht gefunden.');
            }

            $service = new QmFieldDefinitionService();
            $service->delete($fd);

            return ToolResult::success([
                'field_definition_id' => $id,
                'title' => $fd->title,
                'message' => 'Feld-Definition geloescht.',
            ]);
        } catch (\RuntimeException $e) {
            return ToolResult::error('IN_USE', $e->getMessage());
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'field-definitions', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
