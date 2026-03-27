<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmFieldType;

class QmFieldTypeService
{
    /**
     * Get all field types available for a team (system + custom).
     */
    public function listForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection
    {
        return QmFieldType::forTeam($teamId)
            ->orderBy('is_system', 'desc')
            ->orderBy('key')
            ->get();
    }

    /**
     * Create a custom field type for a team.
     */
    public function createCustomType(array $data): QmFieldType
    {
        return QmFieldType::create([
            'key' => $data['key'],
            'label' => $data['label'],
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'team_id' => $data['team_id'],
            'default_config' => $data['default_config'] ?? null,
        ]);
    }

    /**
     * Find a field type by ID, scoped to team access.
     */
    public function findForTeam(int $id, int $teamId): ?QmFieldType
    {
        return QmFieldType::forTeam($teamId)->find($id);
    }
}
