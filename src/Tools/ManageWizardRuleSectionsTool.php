<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmWizardRule;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageWizardRuleSectionsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.rule-sections.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/wizard/rule-sections - Weist Sections einer Wizard-Regel zu. Definiert welche Template-Sections bei Regel-Match angezeigt (show) oder versteckt (hide) werden.';
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
                'rule_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Wizard-Regel (ERFORDERLICH).',
                ],
                'sections' => [
                    'type' => 'array',
                    'description' => 'ERFORDERLICH: Array von Sections mit Effekt. Ersetzt alle bisherigen Zuweisungen.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'template_section_id' => [
                                'type' => 'integer',
                                'description' => 'ID der Template-Section.',
                            ],
                            'effect' => [
                                'type' => 'string',
                                'enum' => ['show', 'hide'],
                                'description' => 'Effekt: show = Section einblenden, hide = Section ausblenden. Default: show.',
                            ],
                        ],
                        'required' => ['template_section_id'],
                    ],
                ],
            ],
            'required' => ['template_id', 'rule_id', 'sections'],
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
            $ruleId = (int)($arguments['rule_id'] ?? 0);

            if ($templateId <= 0 || $ruleId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id und rule_id sind erforderlich.');
            }

            $template = QmTemplate::forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $rule = $template->wizardRules()->find($ruleId);
            if (!$rule) {
                return ToolResult::error('NOT_FOUND', 'Wizard-Regel nicht gefunden.');
            }

            $sections = $arguments['sections'] ?? [];
            if (empty($sections)) {
                return ToolResult::error('VALIDATION_ERROR', 'sections Array ist erforderlich.');
            }

            // Validate template_section_ids
            $validSectionIds = $template->templateSections()->pluck('id')->toArray();
            foreach ($sections as $s) {
                $tsId = (int)($s['template_section_id'] ?? 0);
                if (!in_array($tsId, $validSectionIds)) {
                    return ToolResult::error('NOT_FOUND', "Template-Section {$tsId} gehoert nicht zu diesem Template.");
                }
            }

            $service = new QmWizardService();
            $service->setRuleSections($rule, $sections);

            // Reload to return current state
            $rule->load('ruleSections.templateSection.section');

            $assigned = $rule->ruleSections->map(fn ($rs) => [
                'template_section_id' => $rs->qm_template_section_id,
                'section_title' => $rs->templateSection?->section?->title,
                'effect' => $rs->effect,
            ])->toArray();

            return ToolResult::success([
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'sections_count' => count($assigned),
                'sections' => $assigned,
                'message' => 'Sections der Regel zugewiesen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'wizard', 'rules', 'sections'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
