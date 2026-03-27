<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmFieldDefinition;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetFieldDefinitionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-definition.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/field-definitions/{id} - Ruft eine einzelne Feld-Definition ab. Parameter: field_definition_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'field_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Feld-Definition (ERFORDERLICH).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
            ],
            'required' => ['field_definition_id'],
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

            $id = (int)($arguments['field_definition_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'field_definition_id ist erforderlich.');
            }

            $fd = QmFieldDefinition::query()
                ->with(['fieldType', 'sectionFields.section'])
                ->forTeam($teamId)
                ->find($id);

            if (!$fd) {
                return ToolResult::error('NOT_FOUND', 'Feld-Definition nicht gefunden.');
            }

            return ToolResult::success([
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
                'i18n' => $fd->i18n,
                'used_in_sections' => $fd->sectionFields->map(fn ($sf) => [
                    'section_id' => $sf->section->id,
                    'section_title' => $sf->section->title,
                    'is_required' => $sf->is_required,
                    'behavior_rule' => $sf->behavior_rule,
                ])->values()->toArray(),
                'created_at' => $fd->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Feld-Definition: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'field-definitions', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
