<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmWizardField;
use Platform\Qm\Models\QmWizardRule;
use Platform\Qm\Models\QmWizardRuleSection;
use Platform\Qm\Models\QmInstance;

class QmWizardService
{
    /**
     * Get full wizard configuration for a template.
     */
    public function getWizardConfig(QmTemplate $template): array
    {
        $template->load([
            'wizardFields.lookupTable.entries',
            'wizardRules.ruleSections.templateSection.section',
            'templateSections.section',
        ]);

        $fields = $template->wizardFields->map(function (QmWizardField $field) {
            $data = [
                'id' => $field->id,
                'uuid' => $field->uuid,
                'technical_name' => $field->technical_name,
                'label' => $field->label,
                'input_type' => $field->input_type,
                'is_required' => $field->is_required,
                'sort_order' => $field->sort_order,
                'description' => $field->description,
                'config' => $field->config,
            ];

            if ($field->lookupTable) {
                $data['lookup_table'] = [
                    'id' => $field->lookupTable->id,
                    'name' => $field->lookupTable->name,
                    'entries' => $field->lookupTable->entries->where('is_active', true)->map(fn ($e) => [
                        'id' => $e->id,
                        'label' => $e->label,
                        'value' => $e->value,
                    ])->values()->toArray(),
                ];
            }

            return $data;
        })->toArray();

        $rules = $template->wizardRules->map(function (QmWizardRule $rule) {
            return [
                'id' => $rule->id,
                'uuid' => $rule->uuid,
                'name' => $rule->name,
                'rule_type' => $rule->rule_type,
                'condition_field' => $rule->condition_field,
                'condition_operator' => $rule->condition_operator,
                'condition_value' => $rule->condition_value,
                'is_active' => $rule->is_active,
                'sort_order' => $rule->sort_order,
                'sections' => $rule->ruleSections->map(fn ($rs) => [
                    'template_section_id' => $rs->qm_template_section_id,
                    'section_title' => $rs->templateSection?->section?->title,
                    'effect' => $rs->effect,
                ])->toArray(),
            ];
        })->toArray();

        $templateSections = $template->templateSections->map(fn ($ts) => [
            'id' => $ts->id,
            'section_id' => $ts->qm_section_id,
            'section_title' => $ts->section?->title,
            'section_category' => $ts->section?->category ?? 'standard',
            'position' => $ts->position,
            'is_required' => $ts->is_required,
            'phase_label' => $ts->phase_label,
        ])->toArray();

        return [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'fields' => $fields,
            'rules' => $rules,
            'template_sections' => $templateSections,
        ];
    }

    /**
     * Add a wizard field to a template.
     */
    public function addField(QmTemplate $template, array $data): QmWizardField
    {
        $maxSort = $template->wizardFields()->max('sort_order') ?? -1;

        return QmWizardField::create([
            'qm_template_id' => $template->id,
            'technical_name' => $data['technical_name'],
            'label' => $data['label'],
            'input_type' => $data['input_type'],
            'qm_lookup_table_id' => $data['qm_lookup_table_id'] ?? null,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
            'is_required' => $data['is_required'] ?? false,
            'description' => $data['description'] ?? null,
            'config' => $data['config'] ?? null,
        ]);
    }

    /**
     * Update a wizard field.
     */
    public function updateField(QmWizardField $field, array $data): QmWizardField
    {
        $fillable = ['technical_name', 'label', 'input_type', 'qm_lookup_table_id', 'sort_order', 'is_required', 'description', 'config'];

        foreach ($fillable as $key) {
            if (array_key_exists($key, $data)) {
                $field->{$key} = $data[$key];
            }
        }

        $field->save();
        return $field;
    }

    /**
     * Remove a wizard field.
     */
    public function removeField(QmWizardField $field): void
    {
        // Remove any rules referencing this field
        QmWizardRule::where('qm_template_id', $field->qm_template_id)
            ->where('condition_field', $field->technical_name)
            ->delete();

        $field->delete();
    }

    /**
     * Reorder wizard fields.
     */
    public function reorderFields(QmTemplate $template, array $fieldIds): void
    {
        foreach ($fieldIds as $position => $id) {
            QmWizardField::where('id', $id)
                ->where('qm_template_id', $template->id)
                ->update(['sort_order' => $position]);
        }
    }

    /**
     * Add a wizard rule to a template.
     */
    public function addRule(QmTemplate $template, array $data): QmWizardRule
    {
        $maxSort = $template->wizardRules()->max('sort_order') ?? -1;

        return QmWizardRule::create([
            'qm_template_id' => $template->id,
            'name' => $data['name'],
            'rule_type' => $data['rule_type'],
            'condition_field' => $data['condition_field'],
            'condition_operator' => $data['condition_operator'],
            'condition_value' => $data['condition_value'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
        ]);
    }

    /**
     * Update a wizard rule.
     */
    public function updateRule(QmWizardRule $rule, array $data): QmWizardRule
    {
        $fillable = ['name', 'rule_type', 'condition_field', 'condition_operator', 'condition_value', 'description', 'is_active', 'sort_order'];

        foreach ($fillable as $key) {
            if (array_key_exists($key, $data)) {
                $rule->{$key} = $data[$key];
            }
        }

        $rule->save();
        return $rule;
    }

    /**
     * Remove a wizard rule.
     */
    public function removeRule(QmWizardRule $rule): void
    {
        $rule->ruleSections()->delete();
        $rule->delete();
    }

    /**
     * Set sections for a rule.
     *
     * @param array $sections Array of ['template_section_id' => int, 'effect' => 'show'|'hide']
     */
    public function setRuleSections(QmWizardRule $rule, array $sections): void
    {
        $rule->ruleSections()->delete();

        foreach ($sections as $section) {
            QmWizardRuleSection::create([
                'qm_wizard_rule_id' => $rule->id,
                'qm_template_section_id' => $section['template_section_id'],
                'effect' => $section['effect'] ?? 'show',
            ]);
        }
    }

    /**
     * Evaluate wizard answers and return the list of active template section IDs.
     *
     * @param array $answers Key-value pairs: technical_name => value
     * @return array ['active_section_ids' => int[], 'matched_rules' => array]
     */
    public function evaluateWizard(QmTemplate $template, array $answers): array
    {
        $template->load([
            'templateSections.section',
            'wizardRules' => fn ($q) => $q->where('is_active', true),
            'wizardRules.ruleSections',
        ]);

        // Start with all template sections
        $allSectionIds = $template->templateSections->pluck('id')->toArray();
        $showSections = [];
        $hideSections = [];
        $matchedRules = [];

        foreach ($template->wizardRules as $rule) {
            if ($this->evaluateRule($rule, $answers)) {
                $matchedRules[] = [
                    'id' => $rule->id,
                    'name' => $rule->name,
                ];

                foreach ($rule->ruleSections as $rs) {
                    if ($rs->effect === 'show') {
                        $showSections[] = $rs->qm_template_section_id;
                    } elseif ($rs->effect === 'hide') {
                        $hideSections[] = $rs->qm_template_section_id;
                    }
                }
            }
        }

        // Logic: If any rule explicitly shows sections, start from those.
        // Then remove any hidden sections.
        // If no show-rules matched, all sections are active by default.
        if (!empty($showSections)) {
            $activeSectionIds = array_unique($showSections);
        } else {
            $activeSectionIds = $allSectionIds;
        }

        // Remove hidden sections
        $activeSectionIds = array_values(array_diff($activeSectionIds, $hideSections));

        // Always include required sections (is_required = true)
        $requiredSectionIds = $template->templateSections
            ->where('is_required', true)
            ->pluck('id')
            ->toArray();

        $activeSectionIds = array_values(array_unique(array_merge($activeSectionIds, $requiredSectionIds)));

        // Map back to section info
        $activeSections = $template->templateSections
            ->whereIn('id', $activeSectionIds)
            ->sortBy('position')
            ->map(fn ($ts) => [
                'template_section_id' => $ts->id,
                'section_id' => $ts->qm_section_id,
                'section_title' => $ts->section?->title,
                'section_category' => $ts->section?->category ?? 'standard',
                'position' => $ts->position,
                'phase_label' => $ts->phase_label,
            ])
            ->values()
            ->toArray();

        return [
            'active_section_ids' => $activeSectionIds,
            'active_sections' => $activeSections,
            'matched_rules' => $matchedRules,
            'total_sections' => count($allSectionIds),
            'active_count' => count($activeSectionIds),
        ];
    }

    /**
     * Create an instance from wizard answers.
     */
    public function createInstanceFromWizard(QmTemplate $template, array $answers, array $instanceData): QmInstance
    {
        $evaluation = $this->evaluateWizard($template, $answers);
        $activeSectionIds = $evaluation['active_section_ids'];

        // Build a filtered snapshot
        $snapshot = $this->buildFilteredSnapshot($template, $activeSectionIds, $answers);

        return QmInstance::create([
            'team_id' => $instanceData['team_id'],
            'qm_template_id' => $template->id,
            'title' => $instanceData['title'] ?? $template->name . ' - ' . now()->format('d.m.Y H:i'),
            'description' => $instanceData['description'] ?? null,
            'status' => 'open',
            'snapshot_data' => $snapshot,
            'due_at' => $instanceData['due_at'] ?? null,
            'created_by_user_id' => $instanceData['created_by_user_id'],
        ]);
    }

    /**
     * Build a snapshot with only the active sections.
     */
    protected function buildFilteredSnapshot(QmTemplate $template, array $activeSectionIds, array $wizardAnswers): array
    {
        $template->loadMissing(['templateSections.section.sectionFields.fieldDefinition.fieldType']);

        $sections = [];
        foreach ($template->templateSections as $ts) {
            if (!in_array($ts->id, $activeSectionIds)) {
                continue;
            }

            $fields = [];
            foreach ($ts->section->sectionFields as $sf) {
                $fields[] = [
                    'field_definition_id' => $sf->qm_field_definition_id,
                    'title' => $sf->fieldDefinition->title,
                    'description' => $sf->fieldDefinition->description,
                    'field_type_key' => $sf->fieldDefinition->fieldType->key,
                    'config' => $sf->fieldDefinition->config,
                    'validation_rules' => $sf->fieldDefinition->validation_rules,
                    'position' => $sf->position,
                    'is_required' => $sf->is_required,
                    'behavior_rule' => $sf->behavior_rule,
                    'behavior_config' => $sf->behavior_config,
                ];
            }

            $sections[] = [
                'section_id' => $ts->qm_section_id,
                'template_section_id' => $ts->id,
                'title' => $ts->section->title,
                'description' => $ts->section->description,
                'category' => $ts->section->category ?? 'standard',
                'position' => $ts->position,
                'is_required' => $ts->is_required,
                'phase_label' => $ts->phase_label,
                'fields' => $fields,
            ];
        }

        return [
            'template' => [
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'version' => $template->version,
                'settings' => $template->settings,
            ],
            'wizard_answers' => $wizardAnswers,
            'sections' => $sections,
        ];
    }

    /**
     * Evaluate a single rule against wizard answers.
     */
    protected function evaluateRule(QmWizardRule $rule, array $answers): bool
    {
        $fieldName = $rule->condition_field;
        $answerValue = $answers[$fieldName] ?? null;
        $conditionValue = $rule->condition_value;

        return match ($rule->condition_operator) {
            '=' => $this->isEqual($answerValue, $conditionValue),
            '!=' => !$this->isEqual($answerValue, $conditionValue),
            'in' => is_array($conditionValue) && in_array($answerValue, $conditionValue),
            'not_in' => is_array($conditionValue) && !in_array($answerValue, $conditionValue),
            'contains' => $this->containsValue($answerValue, $conditionValue),
            'exists' => $answerValue !== null && $answerValue !== '' && $answerValue !== [],
            default => false,
        };
    }

    protected function isEqual($answerValue, $conditionValue): bool
    {
        // condition_value is always stored as array/JSON, unwrap single values
        if (is_array($conditionValue) && count($conditionValue) === 1) {
            $conditionValue = reset($conditionValue);
        }

        return (string) $answerValue === (string) $conditionValue;
    }

    protected function containsValue($answerValue, $conditionValue): bool
    {
        if (!is_array($answerValue)) {
            return false;
        }

        // condition_value can be a single value or array
        $checkValues = is_array($conditionValue) ? $conditionValue : [$conditionValue];

        foreach ($checkValues as $cv) {
            if (in_array($cv, $answerValue)) {
                return true;
            }
        }

        return false;
    }
}
