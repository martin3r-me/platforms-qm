<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmSection;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetSectionTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.section.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/sections/{id} - Ruft eine Sektion mit allen Feldern ab. Parameter: section_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Sektion (ERFORDERLICH).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
            ],
            'required' => ['section_id'],
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

            $id = (int)($arguments['section_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');
            }

            $section = QmSection::query()
                ->with(['sectionFields.fieldDefinition.fieldType'])
                ->forTeam($teamId)
                ->find($id);

            if (!$section) {
                return ToolResult::error('NOT_FOUND', 'Sektion nicht gefunden.');
            }

            $fields = $section->sectionFields->map(function ($sf) {
                return [
                    'section_field_id' => $sf->id,
                    'field_definition_id' => $sf->qm_field_definition_id,
                    'title' => $sf->fieldDefinition->title,
                    'description' => $sf->fieldDefinition->description,
                    'field_type_key' => $sf->fieldDefinition->fieldType->key,
                    'field_type_label' => $sf->fieldDefinition->fieldType->label,
                    'config' => $sf->fieldDefinition->config,
                    'position' => $sf->position,
                    'is_required' => $sf->is_required,
                    'behavior_rule' => $sf->behavior_rule,
                    'behavior_config' => $sf->behavior_config,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'description' => $section->description,
                'i18n' => $section->i18n,
                'fields' => $fields,
                'fields_count' => count($fields),
                'created_at' => $section->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Sektion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'sections', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
