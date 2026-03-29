<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageWizardRulesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.rules.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/wizard/rules - Verwaltet Wizard-Aktivierungsregeln eines Templates: hinzufuegen, aktualisieren, entfernen. Parameter: template_id (required), action (required: add|update|remove).';
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
                    'enum' => ['add', 'update', 'remove'],
                    'description' => 'ERFORDERLICH: add, update oder remove.',
                ],
                'rule_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Regel. ERFORDERLICH fuer update/remove.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Regel (ERFORDERLICH fuer add). z.B. "Grossveranstaltung".',
                ],
                'rule_type' => [
                    'type' => 'string',
                    'enum' => ['field_value', 'multi_select_contains'],
                    'description' => 'Regel-Typ (ERFORDERLICH fuer add).',
                ],
                'condition_field' => [
                    'type' => 'string',
                    'description' => 'technical_name des Wizard-Felds (ERFORDERLICH fuer add). z.B. "event_type_id".',
                ],
                'condition_operator' => [
                    'type' => 'string',
                    'enum' => ['=', '!=', 'in', 'not_in', 'contains', 'exists'],
                    'description' => 'Vergleichsoperator (ERFORDERLICH fuer add).',
                ],
                'condition_value' => [
                    'description' => 'Vergleichswert(e) als JSON (ERFORDERLICH fuer add). z.B. ["gross"] oder ["standard", "tagung"].',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Aktiv? Default: true.',
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

            $template = QmTemplate::forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $action = $arguments['action'] ?? '';
            $service = new QmWizardService();

            switch ($action) {
                case 'add':
                    $name = trim((string)($arguments['name'] ?? ''));
                    $ruleType = trim((string)($arguments['rule_type'] ?? ''));
                    $conditionField = trim((string)($arguments['condition_field'] ?? ''));
                    $conditionOperator = trim((string)($arguments['condition_operator'] ?? ''));
                    $conditionValue = $arguments['condition_value'] ?? null;

                    if ($name === '' || $ruleType === '' || $conditionField === '' || $conditionOperator === '' || $conditionValue === null) {
                        return ToolResult::error('VALIDATION_ERROR', 'name, rule_type, condition_field, condition_operator und condition_value sind erforderlich fuer add.');
                    }

                    // Validate condition_field exists as wizard field
                    $fieldExists = $template->wizardFields()->where('technical_name', $conditionField)->exists();
                    if (!$fieldExists) {
                        return ToolResult::error('VALIDATION_ERROR', "Wizard-Feld '{$conditionField}' nicht gefunden. Nutze 'qm.wizard.GET' um Felder zu sehen.");
                    }

                    $rule = $service->addRule($template, [
                        'name' => $name,
                        'rule_type' => $ruleType,
                        'condition_field' => $conditionField,
                        'condition_operator' => $conditionOperator,
                        'condition_value' => is_array($conditionValue) ? $conditionValue : [$conditionValue],
                        'description' => $arguments['description'] ?? null,
                        'is_active' => $arguments['is_active'] ?? true,
                    ]);

                    return ToolResult::success([
                        'rule_id' => $rule->id,
                        'uuid' => $rule->uuid,
                        'name' => $rule->name,
                        'message' => 'Wizard-Regel erstellt. Nutze "qm.wizard.rule-sections.PUT" um Sections zuzuweisen.',
                    ]);

                case 'update':
                    $ruleId = (int)($arguments['rule_id'] ?? 0);
                    if ($ruleId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'rule_id ist erforderlich fuer update.');
                    }

                    $rule = $template->wizardRules()->find($ruleId);
                    if (!$rule) {
                        return ToolResult::error('NOT_FOUND', 'Wizard-Regel nicht gefunden.');
                    }

                    // Wrap condition_value in array if scalar
                    if (array_key_exists('condition_value', $arguments) && !is_array($arguments['condition_value'])) {
                        $arguments['condition_value'] = [$arguments['condition_value']];
                    }

                    $rule = $service->updateRule($rule, $arguments);

                    return ToolResult::success([
                        'rule_id' => $rule->id,
                        'name' => $rule->name,
                        'is_active' => $rule->is_active,
                        'message' => 'Wizard-Regel aktualisiert.',
                    ]);

                case 'remove':
                    $ruleId = (int)($arguments['rule_id'] ?? 0);
                    if ($ruleId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'rule_id ist erforderlich fuer remove.');
                    }

                    $rule = $template->wizardRules()->find($ruleId);
                    if (!$rule) {
                        return ToolResult::error('NOT_FOUND', 'Wizard-Regel nicht gefunden.');
                    }

                    $service->removeRule($rule);

                    return ToolResult::success([
                        'rule_id' => $ruleId,
                        'message' => 'Wizard-Regel und Sections-Zuweisungen entfernt.',
                    ]);

                default:
                    return ToolResult::error('VALIDATION_ERROR', 'Unbekannte action. Erlaubt: add, update, remove.');
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
            'tags' => ['qm', 'wizard', 'rules', 'manage'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
