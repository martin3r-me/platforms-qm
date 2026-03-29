<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmWizardField;
use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageWizardFieldsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.fields.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/wizard/fields - Verwaltet Wizard-Felder eines Templates: hinzufuegen, aktualisieren, entfernen, umordnen. Parameter: template_id (required), action (required: add|update|remove|reorder).';
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
                    'enum' => ['add', 'update', 'remove', 'reorder'],
                    'description' => 'ERFORDERLICH: add, update, remove oder reorder.',
                ],
                'field_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Wizard-Felds. ERFORDERLICH fuer update/remove.',
                ],
                'technical_name' => [
                    'type' => 'string',
                    'description' => 'Technischer Name (ERFORDERLICH fuer add). z.B. "event_type_id", "pax", "location_id".',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Anzeige-Name (ERFORDERLICH fuer add). z.B. "Veranstaltungstyp".',
                ],
                'input_type' => [
                    'type' => 'string',
                    'enum' => ['text', 'number', 'date', 'currency', 'single_select', 'multi_select', 'boolean'],
                    'description' => 'Eingabetyp (ERFORDERLICH fuer add).',
                ],
                'lookup_table_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID der Lookup-Tabelle als Datenquelle (fuer single_select/multi_select).',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional: Pflichtfeld? Default: false.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung/Hilfetext.',
                ],
                'config' => [
                    'type' => 'object',
                    'description' => 'Optional: Zusaetzliche Konfiguration (z.B. Min/Max, Filter auf Lookup-Entries).',
                ],
                'field_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'ERFORDERLICH fuer reorder: Array von Field-IDs in gewuenschter Reihenfolge.',
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
                    $technicalName = trim((string)($arguments['technical_name'] ?? ''));
                    $label = trim((string)($arguments['label'] ?? ''));
                    $inputType = trim((string)($arguments['input_type'] ?? ''));

                    if ($technicalName === '' || $label === '' || $inputType === '') {
                        return ToolResult::error('VALIDATION_ERROR', 'technical_name, label und input_type sind erforderlich fuer add.');
                    }

                    // Check duplicate technical_name
                    $exists = $template->wizardFields()->where('technical_name', $technicalName)->exists();
                    if ($exists) {
                        return ToolResult::error('DUPLICATE', "Wizard-Feld mit technical_name '{$technicalName}' existiert bereits.");
                    }

                    // Validate lookup_table if provided
                    if (!empty($arguments['lookup_table_id'])) {
                        $lt = QmLookupTable::query()->forTeam($teamId)->find((int)$arguments['lookup_table_id']);
                        if (!$lt) {
                            return ToolResult::error('NOT_FOUND', 'Lookup-Tabelle nicht gefunden.');
                        }
                    }

                    $field = $service->addField($template, [
                        'technical_name' => $technicalName,
                        'label' => $label,
                        'input_type' => $inputType,
                        'qm_lookup_table_id' => $arguments['lookup_table_id'] ?? null,
                        'is_required' => $arguments['is_required'] ?? false,
                        'description' => $arguments['description'] ?? null,
                        'config' => $arguments['config'] ?? null,
                    ]);

                    return ToolResult::success([
                        'field_id' => $field->id,
                        'uuid' => $field->uuid,
                        'technical_name' => $field->technical_name,
                        'label' => $field->label,
                        'message' => 'Wizard-Feld hinzugefuegt.',
                    ]);

                case 'update':
                    $fieldId = (int)($arguments['field_id'] ?? 0);
                    if ($fieldId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'field_id ist erforderlich fuer update.');
                    }

                    $field = $template->wizardFields()->find($fieldId);
                    if (!$field) {
                        return ToolResult::error('NOT_FOUND', 'Wizard-Feld nicht gefunden.');
                    }

                    $field = $service->updateField($field, $arguments);

                    return ToolResult::success([
                        'field_id' => $field->id,
                        'technical_name' => $field->technical_name,
                        'label' => $field->label,
                        'message' => 'Wizard-Feld aktualisiert.',
                    ]);

                case 'remove':
                    $fieldId = (int)($arguments['field_id'] ?? 0);
                    if ($fieldId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'field_id ist erforderlich fuer remove.');
                    }

                    $field = $template->wizardFields()->find($fieldId);
                    if (!$field) {
                        return ToolResult::error('NOT_FOUND', 'Wizard-Feld nicht gefunden.');
                    }

                    $service->removeField($field);

                    return ToolResult::success([
                        'field_id' => $fieldId,
                        'message' => 'Wizard-Feld und zugehoerige Regeln entfernt.',
                    ]);

                case 'reorder':
                    $ids = $arguments['field_ids'] ?? [];
                    if (empty($ids)) {
                        return ToolResult::error('VALIDATION_ERROR', 'field_ids ist erforderlich fuer reorder.');
                    }

                    $service->reorderFields($template, $ids);

                    return ToolResult::success([
                        'message' => 'Wizard-Felder umgeordnet.',
                        'new_order' => $ids,
                    ]);

                default:
                    return ToolResult::error('VALIDATION_ERROR', 'Unbekannte action. Erlaubt: add, update, remove, reorder.');
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
            'tags' => ['qm', 'wizard', 'fields', 'manage'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
