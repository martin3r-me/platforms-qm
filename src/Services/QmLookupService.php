<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Models\QmLookupEntry;

class QmLookupService
{
    public function listTablesForTeam(int $teamId): \Illuminate\Database\Eloquent\Collection
    {
        return QmLookupTable::forTeam($teamId)
            ->withCount('entries')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function createTable(array $data): QmLookupTable
    {
        return QmLookupTable::create($data);
    }

    public function updateTable(QmLookupTable $table, array $data): QmLookupTable
    {
        $fillable = ['name', 'description', 'sort_order', 'is_active'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $table->{$field} = $data[$field];
            }
        }

        $table->save();
        return $table;
    }

    public function deleteTable(QmLookupTable $table): void
    {
        $usageCount = $table->wizardFields()->count();
        if ($usageCount > 0) {
            throw new \RuntimeException("Lookup-Tabelle wird in {$usageCount} Wizard-Feld(ern) verwendet und kann nicht geloescht werden.");
        }

        $table->delete();
    }

    public function listEntries(int $tableId): \Illuminate\Database\Eloquent\Collection
    {
        return QmLookupEntry::where('qm_lookup_table_id', $tableId)
            ->orderBy('sort_order')
            ->get();
    }

    public function createEntry(array $data): QmLookupEntry
    {
        return QmLookupEntry::create($data);
    }

    public function updateEntry(QmLookupEntry $entry, array $data): QmLookupEntry
    {
        $fillable = ['label', 'value', 'description', 'sort_order', 'is_active', 'metadata'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $entry->{$field} = $data[$field];
            }
        }

        $entry->save();
        return $entry;
    }

    public function deleteEntry(QmLookupEntry $entry): void
    {
        $entry->delete();
    }

    public function reorderEntries(int $tableId, array $entryIds): void
    {
        foreach ($entryIds as $position => $id) {
            QmLookupEntry::where('id', $id)
                ->where('qm_lookup_table_id', $tableId)
                ->update(['sort_order' => $position]);
        }
    }
}
