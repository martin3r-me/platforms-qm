<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmSection;
use Platform\Qm\Models\QmFieldDefinition;
use Platform\Qm\Services\QmSectionService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageSectionFieldsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.sections.fields.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/sections/{id}/fields - Verwaltet Felder einer Sektion: hinzufuegen, entfernen, umordnen oder Feld-Eigenschaften aendern. Parameter: section_id (required), action (required: add|remove|reorder|update).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Sektion (ERFORDERLICH).',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['add', 'remove', 'reorder', 'update'],
                    'description' => 'ERFORDERLICH: add = Feld hinzufuegen, remove = Feld entfernen, reorder = Reihenfolge aendern, update = Feld-Eigenschaften aendern.',
                ],
                'field_definition_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Feld-Definition. ERFORDERLICH fuer add/remove.',
                ],
                'section_field_id' => [
                    'type' => 'integer',
                    'description' => 'ID des SectionField-Eintrags. ERFORDERLICH fuer update.',
                ],
                'is_required' => [
                    'type' => 'boolean',
                    'description' => 'Optional (add/update): Pflichtfeld? Default: false.',
                ],
                'behavior_rule' => [
                    'type' => 'string',
                    'enum' => ['always', 'conditional', 'random', 'time_based', 'risk_based'],
                    'description' => 'Optional (add/update): Verhaltensregel. Default: always.',
                ],
                'behavior_config' => [
                    'type' => 'object',
                    'description' => 'Optional (add/update): Regel-Konfiguration. z.B. {"sampling_rate": 25} fuer random.',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional (add/update): Position.',
                ],
                'section_field_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'ERFORDERLICH fuer reorder: Array von SectionField-IDs in gewuenschter Reihenfolge.',
                ],
            ],
            'required' => ['section_id', 'action'],
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

            $sectionId = (int)($arguments['section_id'] ?? 0);
            if ($sectionId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');
            }

            $section = QmSection::query()->forTeam($teamId)->find($sectionId);
            if (!$section) {
                return ToolResult::error('NOT_FOUND', 'Sektion nicht gefunden.');
            }

            $action = $arguments['action'] ?? '';
            $service = new QmSectionService();

            switch ($action) {
                case 'add':
                    $fdId = (int)($arguments['field_definition_id'] ?? 0);
                    if ($fdId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'field_definition_id ist erforderlich fuer add.');
                    }

                    $fd = QmFieldDefinition::query()->forTeam($teamId)->find($fdId);
                    if (!$fd) {
                        return ToolResult::error('NOT_FOUND', 'Feld-Definition nicht gefunden.');
                    }

                    $existing = $section->sectionFields()->where('qm_field_definition_id', $fdId)->exists();
                    if ($existing) {
                        return ToolResult::error('DUPLICATE', 'Feld ist bereits in dieser Sektion.');
                    }

                    $sf = $service->addField($section, [
                        'field_definition_id' => $fdId,
                        'is_required' => $arguments['is_required'] ?? false,
                        'behavior_rule' => $arguments['behavior_rule'] ?? 'always',
                        'behavior_config' => $arguments['behavior_config'] ?? null,
                        'position' => $arguments['position'] ?? null,
                    ]);

                    return ToolResult::success([
                        'section_field_id' => $sf->id,
                        'field_definition_id' => $fdId,
                        'position' => $sf->position,
                        'message' => 'Feld zur Sektion hinzugefuegt.',
                    ]);

                case 'remove':
                    $fdId = (int)($arguments['field_definition_id'] ?? 0);
                    if ($fdId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'field_definition_id ist erforderlich fuer remove.');
                    }

                    $service->removeField($section, $fdId);

                    return ToolResult::success([
                        'field_definition_id' => $fdId,
                        'message' => 'Feld aus Sektion entfernt.',
                    ]);

                case 'reorder':
                    $ids = $arguments['section_field_ids'] ?? [];
                    if (empty($ids)) {
                        return ToolResult::error('VALIDATION_ERROR', 'section_field_ids ist erforderlich fuer reorder.');
                    }

                    $service->reorderFields($section, $ids);

                    return ToolResult::success([
                        'message' => 'Felder umgeordnet.',
                        'new_order' => $ids,
                    ]);

                case 'update':
                    $sfId = (int)($arguments['section_field_id'] ?? 0);
                    if ($sfId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'section_field_id ist erforderlich fuer update.');
                    }

                    $sf = $section->sectionFields()->find($sfId);
                    if (!$sf) {
                        return ToolResult::error('NOT_FOUND', 'SectionField nicht gefunden.');
                    }

                    $sf = $service->updateField($sf, $arguments);

                    return ToolResult::success([
                        'section_field_id' => $sf->id,
                        'is_required' => $sf->is_required,
                        'behavior_rule' => $sf->behavior_rule,
                        'message' => 'Feld-Eigenschaften aktualisiert.',
                    ]);

                default:
                    return ToolResult::error('VALIDATION_ERROR', 'Unbekannte action. Erlaubt: add, remove, reorder, update.');
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
            'tags' => ['qm', 'sections', 'fields', 'manage'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
