<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmSection;
use Platform\Qm\Services\QmTemplateService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageTemplateSectionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.templates.sections.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/templates/{id}/sections - Verwaltet Sektionen eines Templates: hinzufuegen, entfernen, umordnen. Parameter: template_id (required), action (required: add|remove|reorder).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Templates (ERFORDERLICH).',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['add', 'remove', 'reorder'],
                    'description' => 'ERFORDERLICH: add, remove, oder reorder.',
                ],
                'section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Sektion. ERFORDERLICH fuer add/remove.',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional (add): Sektion pflicht? Default: true.',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional (add): Position.',
                ],
                'template_section_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'ERFORDERLICH fuer reorder: Array von TemplateSection-IDs.',
                ],
            ],
            'required' => ['template_id', 'action'],
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

            $templateId = (int)($arguments['template_id'] ?? 0);
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = QmTemplate::query()->forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $action = $arguments['action'] ?? '';
            $service = new QmTemplateService();

            switch ($action) {
                case 'add':
                    $sectionId = (int)($arguments['section_id'] ?? 0);
                    if ($sectionId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich fuer add.');
                    }

                    $section = QmSection::query()->forTeam($teamId)->find($sectionId);
                    if (!$section) {
                        return ToolResult::error('NOT_FOUND', 'Sektion nicht gefunden.');
                    }

                    $existing = $template->templateSections()->where('qm_section_id', $sectionId)->exists();
                    if ($existing) {
                        return ToolResult::error('DUPLICATE', 'Sektion ist bereits im Template.');
                    }

                    $ts = $service->addSection($template, $sectionId, [
                        'is_required' => $arguments['is_required'] ?? true,
                        'position' => $arguments['position'] ?? null,
                    ]);

                    return ToolResult::success([
                        'template_section_id' => $ts->id,
                        'section_id' => $sectionId,
                        'position' => $ts->position,
                        'message' => 'Sektion zum Template hinzugefuegt.',
                    ]);

                case 'remove':
                    $sectionId = (int)($arguments['section_id'] ?? 0);
                    if ($sectionId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich fuer remove.');
                    }

                    $service->removeSection($template, $sectionId);

                    return ToolResult::success([
                        'section_id' => $sectionId,
                        'message' => 'Sektion aus Template entfernt.',
                    ]);

                case 'reorder':
                    $ids = $arguments['template_section_ids'] ?? [];
                    if (empty($ids)) {
                        return ToolResult::error('VALIDATION_ERROR', 'template_section_ids ist erforderlich fuer reorder.');
                    }

                    $service->reorderSections($template, $ids);

                    return ToolResult::success([
                        'message' => 'Sektionen umgeordnet.',
                        'new_order' => $ids,
                    ]);

                default:
                    return ToolResult::error('VALIDATION_ERROR', 'Unbekannte action. Erlaubt: add, remove, reorder.');
            }
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'templates', 'sections', 'manage'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
