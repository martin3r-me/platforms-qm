<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Models\QmLookupEntry;
use Platform\Qm\Services\QmLookupService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ManageLookupEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.lookup-entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/lookup-entries - Verwaltet Eintraege einer Lookup-Tabelle: hinzufuegen, aktualisieren, entfernen, umordnen. Parameter: lookup_table_id (required), action (required: add|update|remove|reorder).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'lookup_table_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Lookup-Tabelle (ERFORDERLICH).',
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['add', 'update', 'remove', 'reorder'],
                    'description' => 'ERFORDERLICH: add = Eintrag hinzufuegen, update = Eintrag aendern, remove = Eintrag loeschen, reorder = Sortierung aendern.',
                ],
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Eintrags. ERFORDERLICH fuer update/remove.',
                ],
                'label' => [
                    'type' => 'string',
                    'description' => 'Anzeige-Name (ERFORDERLICH fuer add). z.B. "Standardveranstaltung".',
                ],
                'value' => [
                    'type' => 'string',
                    'description' => 'Technischer Wert (ERFORDERLICH fuer add). z.B. "standard".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'description' => 'Optional (add/update): Aktiv? Default: true.',
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Optional (add/update): Beliebige Zusatzdaten als JSON.',
                ],
                'entry_ids' => [
                    'type' => 'array',
                    'items' => ['type' => 'integer'],
                    'description' => 'ERFORDERLICH fuer reorder: Array von Entry-IDs in gewuenschter Reihenfolge.',
                ],
            ],
            'required' => ['lookup_table_id', 'action'],
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

            $tableId = (int)($arguments['lookup_table_id'] ?? 0);
            if ($tableId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'lookup_table_id ist erforderlich.');
            }

            $table = QmLookupTable::query()->forTeam($teamId)->find($tableId);
            if (!$table) {
                return ToolResult::error('NOT_FOUND', 'Lookup-Tabelle nicht gefunden.');
            }

            $action = $arguments['action'] ?? '';
            $service = new QmLookupService();

            switch ($action) {
                case 'add':
                    $label = trim((string)($arguments['label'] ?? ''));
                    $value = trim((string)($arguments['value'] ?? ''));
                    if ($label === '' || $value === '') {
                        return ToolResult::error('VALIDATION_ERROR', 'label und value sind erforderlich fuer add.');
                    }

                    $maxSort = QmLookupEntry::where('qm_lookup_table_id', $tableId)->max('sort_order') ?? -1;

                    $entry = $service->createEntry([
                        'qm_lookup_table_id' => $tableId,
                        'label' => $label,
                        'value' => $value,
                        'description' => $arguments['description'] ?? null,
                        'sort_order' => $arguments['sort_order'] ?? ($maxSort + 1),
                        'is_active' => $arguments['is_active'] ?? true,
                        'metadata' => $arguments['metadata'] ?? null,
                    ]);

                    return ToolResult::success([
                        'entry_id' => $entry->id,
                        'uuid' => $entry->uuid,
                        'label' => $entry->label,
                        'value' => $entry->value,
                        'message' => 'Eintrag hinzugefuegt.',
                    ]);

                case 'update':
                    $entryId = (int)($arguments['entry_id'] ?? 0);
                    if ($entryId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'entry_id ist erforderlich fuer update.');
                    }

                    $entry = QmLookupEntry::where('qm_lookup_table_id', $tableId)->find($entryId);
                    if (!$entry) {
                        return ToolResult::error('NOT_FOUND', 'Eintrag nicht gefunden.');
                    }

                    $entry = $service->updateEntry($entry, $arguments);

                    return ToolResult::success([
                        'entry_id' => $entry->id,
                        'label' => $entry->label,
                        'value' => $entry->value,
                        'is_active' => $entry->is_active,
                        'message' => 'Eintrag aktualisiert.',
                    ]);

                case 'remove':
                    $entryId = (int)($arguments['entry_id'] ?? 0);
                    if ($entryId <= 0) {
                        return ToolResult::error('VALIDATION_ERROR', 'entry_id ist erforderlich fuer remove.');
                    }

                    $entry = QmLookupEntry::where('qm_lookup_table_id', $tableId)->find($entryId);
                    if (!$entry) {
                        return ToolResult::error('NOT_FOUND', 'Eintrag nicht gefunden.');
                    }

                    $service->deleteEntry($entry);

                    return ToolResult::success([
                        'entry_id' => $entryId,
                        'message' => 'Eintrag geloescht.',
                    ]);

                case 'reorder':
                    $ids = $arguments['entry_ids'] ?? [];
                    if (empty($ids)) {
                        return ToolResult::error('VALIDATION_ERROR', 'entry_ids ist erforderlich fuer reorder.');
                    }

                    $service->reorderEntries($tableId, $ids);

                    return ToolResult::success([
                        'message' => 'Eintraege umgeordnet.',
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
            'tags' => ['qm', 'lookup', 'entries', 'manage'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
