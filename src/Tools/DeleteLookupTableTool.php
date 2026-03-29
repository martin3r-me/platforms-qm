<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmLookupTable;
use Platform\Qm\Services\QmLookupService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class DeleteLookupTableTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.lookup-tables.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /qm/lookup-tables/{id} - Loescht eine Lookup-Tabelle (soft delete). Schlaegt fehl wenn in Wizard-Feldern verwendet.';
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
            ],
            'required' => ['lookup_table_id'],
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

            $id = (int)($arguments['lookup_table_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'lookup_table_id ist erforderlich.');
            }

            $table = QmLookupTable::query()->forTeam($teamId)->find($id);
            if (!$table) {
                return ToolResult::error('NOT_FOUND', 'Lookup-Tabelle nicht gefunden.');
            }

            $service = new QmLookupService();
            $service->deleteTable($table);

            return ToolResult::success([
                'id' => $id,
                'message' => 'Lookup-Tabelle geloescht.',
            ]);
        } catch (\RuntimeException $e) {
            return ToolResult::error('IN_USE', $e->getMessage());
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen der Lookup-Tabelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'lookup', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
