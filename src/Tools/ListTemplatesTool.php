<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListTemplatesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.templates.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/templates - Listet QM Templates. Optional: status Filter.';
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
                        'enum' => ['draft', 'active', 'archived'],
                        'description' => 'Optional: Filter nach Status.',
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

            $query = QmTemplate::query()
                ->withCount(['templateSections', 'instances'])
                ->forTeam($teamId);

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }

            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, ['name', 'status', 'created_at', 'updated_at'], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (QmTemplate $t) {
                return [
                    'id' => $t->id,
                    'uuid' => $t->uuid,
                    'name' => $t->name,
                    'description' => $t->description,
                    'status' => $t->status,
                    'version' => $t->version,
                    'sections_count' => $t->template_sections_count,
                    'instances_count' => $t->instances_count,
                    'haccp_enabled' => $t->getSetting('haccp_enabled'),
                    'created_at' => $t->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'templates', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
