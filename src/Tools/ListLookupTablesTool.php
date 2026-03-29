<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListLookupTablesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.lookup-tables.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/lookup-tables - Listet Lookup-Tabellen (Stammdaten) eines Teams. z.B. Event-Typen, Locations, Maschinentypen.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID.',
                    ],
                    'active_only' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur aktive Tabellen. Default: false.',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $query = QmLookupTable::query()
                ->withCount('entries')
                ->forTeam($teamId);

            if ($arguments['active_only'] ?? false) {
                $query->active();
            }

            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, ['name', 'sort_order', 'created_at'], 'sort_order', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (QmLookupTable $table) {
                return [
                    'id' => $table->id,
                    'uuid' => $table->uuid,
                    'name' => $table->name,
                    'description' => $table->description,
                    'entries_count' => $table->entries_count,
                    'is_active' => $table->is_active,
                    'sort_order' => $table->sort_order,
                    'created_at' => $table->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Lookup-Tabellen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'lookup', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
