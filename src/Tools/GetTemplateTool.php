<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetTemplateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.template.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/templates/{id} - Ruft ein Template mit Sektionen und Feldern ab. Parameter: template_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Templates (ERFORDERLICH).',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
            ],
            'required' => ['template_id'],
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

            $id = (int)($arguments['template_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = QmTemplate::query()
                ->with(['templateSections.section.sectionFields.fieldDefinition.fieldType'])
                ->withCount('instances')
                ->forTeam($teamId)
                ->find($id);

            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $sections = $template->templateSections->map(function ($ts) {
                $fields = $ts->section->sectionFields->map(function ($sf) {
                    return [
                        'section_field_id' => $sf->id,
                        'field_definition_id' => $sf->qm_field_definition_id,
                        'title' => $sf->fieldDefinition->title,
                        'field_type_key' => $sf->fieldDefinition->fieldType->key,
                        'position' => $sf->position,
                        'is_required' => $sf->is_required,
                        'behavior_rule' => $sf->behavior_rule,
                        'behavior_config' => $sf->behavior_config,
                    ];
                })->values()->toArray();

                return [
                    'template_section_id' => $ts->id,
                    'section_id' => $ts->qm_section_id,
                    'title' => $ts->section->title,
                    'description' => $ts->section->description,
                    'position' => $ts->position,
                    'is_required' => $ts->is_required,
                    'fields' => $fields,
                    'fields_count' => count($fields),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'description' => $template->description,
                'status' => $template->status,
                'version' => $template->version,
                'settings' => $template->settings,
                'sections' => $sections,
                'sections_count' => count($sections),
                'instances_count' => $template->instances_count,
                'created_at' => $template->created_at?->toISOString(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'templates', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
