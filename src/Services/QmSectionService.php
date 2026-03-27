<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmSection;
use Platform\Qm\Models\QmSectionField;

class QmSectionService
{
    public function create(array $data): QmSection
    {
        return QmSection::create($data);
    }

    public function update(QmSection $section, array $data): QmSection
    {
        $fillable = ['title', 'description', 'i18n'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $section->{$field} = $data[$field];
            }
        }

        $section->save();
        return $section;
    }

    public function delete(QmSection $section): void
    {
        $usageCount = $section->templates()->count();
        if ($usageCount > 0) {
            throw new \RuntimeException("Sektion wird in {$usageCount} Template(s) verwendet und kann nicht geloescht werden.");
        }

        $section->delete();
    }

    /**
     * Add a field to a section.
     */
    public function addField(QmSection $section, array $data): QmSectionField
    {
        $maxPosition = $section->sectionFields()->max('position') ?? -1;

        return $section->sectionFields()->create([
            'qm_field_definition_id' => $data['field_definition_id'],
            'position' => $data['position'] ?? ($maxPosition + 1),
            'is_required' => $data['is_required'] ?? false,
            'behavior_rule' => $data['behavior_rule'] ?? 'always',
            'behavior_config' => $data['behavior_config'] ?? null,
        ]);
    }

    /**
     * Remove a field from a section.
     */
    public function removeField(QmSection $section, int $fieldDefinitionId): void
    {
        $section->sectionFields()
            ->where('qm_field_definition_id', $fieldDefinitionId)
            ->delete();
    }

    /**
     * Reorder fields in a section.
     */
    public function reorderFields(QmSection $section, array $fieldIds): void
    {
        foreach ($fieldIds as $position => $sectionFieldId) {
            $section->sectionFields()
                ->where('id', $sectionFieldId)
                ->update(['position' => $position]);
        }
    }

    /**
     * Update a section field's properties.
     */
    public function updateField(QmSectionField $sectionField, array $data): QmSectionField
    {
        $fillable = ['is_required', 'behavior_rule', 'behavior_config', 'position'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $sectionField->{$field} = $data[$field];
            }
        }

        $sectionField->save();
        return $sectionField;
    }
}
