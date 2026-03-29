<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetLookupTableTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.lookup-table.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/lookup-tables/{id} - Zeigt eine Lookup-Tabelle mit allen Eintraegen.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'lookup_table_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Lookup-Tabelle (ERFORDERLICH).',
                ],
            ],
            'required' => ['lookup_table_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $id = (int)($arguments['lookup_table_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'lookup_table_id ist erforderlich.');
            }

            $table = QmLookupTable::query()
                ->forTeam($teamId)
                ->with(['entries' => fn ($q) => $q->orderBy('sort_order'), 'createdByUser'])
                ->withCount('wizardFields')
                ->find($id);

            if (!$table) {
                return ToolResult::error('NOT_FOUND', 'Lookup-Tabelle nicht gefunden.');
            }

            return ToolResult::success([
                'id' => $table->id,
                'uuid' => $table->uuid,
                'name' => $table->name,
                'description' => $table->description,
                'is_active' => $table->is_active,
                'sort_order' => $table->sort_order,
                'wizard_fields_count' => $table->wizard_fields_count,
                'created_by' => $table->createdByUser?->name,
                'created_at' => $table->created_at?->toISOString(),
                'entries' => $table->entries->map(fn ($e) => [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'label' => $e->label,
                    'value' => $e->value,
                    'description' => $e->description,
                    'is_active' => $e->is_active,
                    'sort_order' => $e->sort_order,
                    'metadata' => $e->metadata,
                ])->toArray(),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Lookup-Tabelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'lookup', 'detail'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
