<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmFieldDefinition;

class QmFieldDefinitionService
{
    public function create(array $data): QmFieldDefinition
    {
        return QmFieldDefinition::create($data);
    }

    public function update(QmFieldDefinition $definition, array $data): QmFieldDefinition
    {
        $fillable = ['title', 'description', 'config', 'validation_rules', 'i18n', 'qm_field_type_id'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $definition->{$field} = $data[$field];
            }
        }

        $definition->save();
        return $definition;
    }

    public function delete(QmFieldDefinition $definition): void
    {
        // Check if field is used in any section
        $usageCount = $definition->sectionFields()->count();
        if ($usageCount > 0) {
            throw new \RuntimeException("Feld wird in {$usageCount} Sektion(en) verwendet und kann nicht geloescht werden.");
        }

        $definition->delete();
    }
}
