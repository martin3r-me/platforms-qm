<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmRecurrenceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ScheduleTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.schedule.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/instances/:id/schedule - Konfiguriert Wiederholung fuer eine Instanz. ERFORDERLICH: id. Optional: recurrence_config (setzen) oder action="remove" (entfernen). recurrence_config: { frequency: "daily|weekly|monthly", interval: 1, days: ["mon","wed"], day_of_month: 1, time: "08:00", ends_at: "2025-12-31", max_occurrences: 100, auto_due_hours: 24 }.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'id' => ['type' => 'integer', 'description' => 'ID der Instanz (ERFORDERLICH).'],
                'recurrence_config' => [
                    'type' => 'object',
                    'description' => 'Wiederholungskonfiguration. Keys: frequency (daily|weekly|monthly), interval (int), days (array, z.B. ["mon","wed"]), day_of_month (int), time (string "HH:MM"), ends_at (ISO-Datum), max_occurrences (int), auto_due_hours (int).',
                    'properties' => [
                        'frequency' => ['type' => 'string', 'enum' => ['daily', 'weekly', 'monthly']],
                        'interval' => ['type' => 'integer', 'description' => 'Alle N Tage/Wochen/Monate.'],
                        'days' => ['type' => 'array', 'description' => 'Wochentage (weekly). z.B. ["mon","wed","fri"].', 'items' => ['type' => 'string']],
                        'day_of_month' => ['type' => 'integer', 'description' => 'Tag im Monat (monthly).'],
                        'time' => ['type' => 'string', 'description' => 'Uhrzeit. z.B. "08:00".'],
                        'ends_at' => ['type' => 'string', 'description' => 'Enddatum (ISO 8601).'],
                        'max_occurrences' => ['type' => 'integer', 'description' => 'Maximale Wiederholungen.'],
                        'auto_due_hours' => ['type' => 'integer', 'description' => 'Faelligkeitsfrist in Stunden nach Erstellung.'],
                    ],
                ],
                'action' => [
                    'type' => 'string',
                    'enum' => ['set', 'remove', 'list'],
                    'description' => 'Optional: "set" (Default) setzt recurrence_config, "remove" entfernt sie, "list" zeigt alle Wiederholungen im Team.',
                ],
            ],
            'required' => ['id'],
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

            $service = new QmRecurrenceService();
            $action = $arguments['action'] ?? 'set';

            // List recurring instances for team
            if ($action === 'list') {
                $recurring = $service->getRecurringForTeam($teamId);
                return ToolResult::success([
                    'recurring_instances' => $recurring,
                    'count' => count($recurring),
                    'team_id' => $teamId,
                ]);
            }

            $instance = QmInstance::forTeam($teamId)->find((int)$arguments['id']);
            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            if ($action === 'remove') {
                $instance = $service->removeRecurrence($instance);
                return ToolResult::success([
                    'id' => $instance->id,
                    'recurrence_config' => null,
                    'message' => 'Wiederholung entfernt.',
                ]);
            }

            // Set recurrence
            $config = $arguments['recurrence_config'] ?? null;
            if (!$config || empty($config['frequency'])) {
                return ToolResult::error('VALIDATION_ERROR', 'recurrence_config mit frequency ist erforderlich. Gueltige Werte: daily, weekly, monthly.');
            }

            $instance = $service->setRecurrence($instance, $config);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'recurrence_config' => $instance->recurrence_config,
                'message' => 'Wiederholung konfiguriert. Neue Instanzen werden automatisch erstellt wenn die vorherige abgeschlossen ist.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Konfigurieren der Wiederholung: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'schedule', 'recurrence'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
