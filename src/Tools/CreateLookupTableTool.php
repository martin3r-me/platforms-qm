<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Services\QmLookupService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateLookupTableTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.lookup-tables.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/lookup-tables - Erstellt eine neue Lookup-Tabelle (Stammdaten). ERFORDERLICH: name. Optional: description, sort_order, is_active.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der Lookup-Tabelle (ERFORDERLICH). z.B. "Event-Typ", "Location", "Maschinentyp".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'sort_order' => [
                    'type' => 'integer',
                    'description' => 'Optional: Sortierung. Default: 0.',
                ],
            ],
            'required' => ['name'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $service = new QmLookupService();
            $table = $service->createTable([
                'team_id' => $teamId,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'sort_order' => $arguments['sort_order'] ?? 0,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $table->id,
                'uuid' => $table->uuid,
                'name' => $table->name,
                'message' => 'Lookup-Tabelle erstellt. Nutze "qm.lookup-entries.PUT" um Eintraege hinzuzufuegen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Lookup-Tabelle: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'lookup', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
