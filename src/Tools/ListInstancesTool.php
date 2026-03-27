<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListInstancesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/instances - Listet QM Checklisten-Instanzen. Optional: status, template_id Filter.';
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
                    'status' => [
                        'type' => 'string',
                        'enum' => ['open', 'in_progress', 'completed', 'cancelled'],
                        'description' => 'Optional: Filter nach Status.',
                    ],
                    'template_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Filter nach Template-ID.',
                    ],
                    'overdue' => [
                        'type' => 'boolean',
                        'description' => 'Optional: Nur ueberfaellige Instanzen.',
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

            $query = QmInstance::query()
                ->with(['template:id,name,uuid', 'createdByUser:id,name'])
                ->withCount(['responses', 'deviations'])
                ->forTeam($teamId);

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }

            if (isset($arguments['template_id'])) {
                $query->where('qm_template_id', (int)$arguments['template_id']);
            }

            if (!empty($arguments['overdue'])) {
                $query->overdue();
            }

            $this->applyStandardSearch($query, $arguments, ['title', 'description']);
            $this->applyStandardSort($query, $arguments, ['title', 'status', 'created_at', 'due_at', 'completed_at', 'score'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (QmInstance $i) {
                return [
                    'id' => $i->id,
                    'uuid' => $i->uuid,
                    'title' => $i->title,
                    'status' => $i->status,
                    'score' => $i->score,
                    'template' => $i->template ? ['id' => $i->template->id, 'name' => $i->template->name] : null,
                    'responses_count' => $i->responses_count,
                    'deviations_count' => $i->deviations_count,
                    'due_at' => $i->due_at?->toISOString(),
                    'completed_at' => $i->completed_at?->toISOString(),
                    'created_by' => $i->createdByUser?->name,
                    'created_at' => $i->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Instanzen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'instances', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
