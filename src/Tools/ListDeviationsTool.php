<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListDeviationsTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.deviations.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/deviations - Listet Abweichungen. Optional: status, severity, instance_id Filter.';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                    'status' => [
                        'type' => 'string',
                        'enum' => ['open', 'acknowledged', 'resolved', 'verified'],
                        'description' => 'Optional: Filter nach Status.',
                    ],
                    'severity' => [
                        'type' => 'string',
                        'enum' => ['low', 'medium', 'high', 'critical'],
                        'description' => 'Optional: Filter nach Schweregrad.',
                    ],
                    'instance_id' => ['type' => 'integer', 'description' => 'Optional: Filter nach Instanz-ID.'],
                    'open_only' => ['type' => 'boolean', 'description' => 'Optional: Nur offene Abweichungen.'],
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

            $query = QmDeviation::query()
                ->with(['instance:id,uuid,title', 'createdByUser:id,name'])
                ->whereHas('instance', function ($q) use ($teamId) {
                    $q->where('team_id', $teamId);
                });

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }
            if (isset($arguments['severity'])) {
                $query->bySeverity($arguments['severity']);
            }
            if (isset($arguments['instance_id'])) {
                $query->forInstance((int)$arguments['instance_id']);
            }
            if (!empty($arguments['open_only'])) {
                $query->open();
            }

            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['title', 'severity', 'status', 'created_at', 'escalation_level'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (QmDeviation $d) {
                return [
                    'id' => $d->id,
                    'uuid' => $d->uuid,
                    'title' => $d->title,
                    'severity' => $d->severity,
                    'status' => $d->status,
                    'workflow_type' => $d->workflow_type,
                    'escalation_level' => $d->escalation_level,
                    'instance' => $d->instance ? ['id' => $d->instance->id, 'title' => $d->instance->title] : null,
                    'created_by' => $d->createdByUser?->name,
                    'created_at' => $d->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Abweichungen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true, 'category' => 'read',
            'tags' => ['qm', 'deviations', 'list'],
            'risk_level' => 'safe', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
