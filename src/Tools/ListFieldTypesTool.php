<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Services\QmFieldTypeService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class ListFieldTypesTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.field-types.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/field-types - Listet alle verfuegbaren Feldtypen (System + Custom fuer Team).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => [],
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

            $service = new QmFieldTypeService();
            $types = $service->listForTeam($teamId);

            $data = $types->map(function ($type) {
                return [
                    'id' => $type->id,
                    'uuid' => $type->uuid,
                    'key' => $type->key,
                    'label' => $type->label,
                    'description' => $type->description,
                    'is_system' => $type->is_system,
                    'default_config' => $type->default_config,
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'total' => count($data),
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Feldtypen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'field-types', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
