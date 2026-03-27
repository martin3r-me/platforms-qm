<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class UpdateInstanceTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/instances/:id - Aktualisiert eine QM Instanz. ERFORDERLICH: id. Optional: title, description, status, due_at.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Instanz (ERFORDERLICH).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['open', 'in_progress', 'completed', 'cancelled'],
                    'description' => 'Optional: Neuer Status.',
                ],
                'due_at' => [
                    'type' => 'string',
                    'description' => 'Optional: Neues Faelligkeitsdatum (ISO 8601 oder null).',
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

            $instance = QmInstance::forTeam($teamId)->find((int)$arguments['id']);
            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            $updateData = [];
            foreach (['title', 'description', 'status'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (array_key_exists('due_at', $arguments)) {
                $updateData['due_at'] = $arguments['due_at'] ? \Carbon\Carbon::parse($arguments['due_at']) : null;
            }

            $service = new QmInstanceService();
            $instance = $service->update($instance, $updateData);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'status' => $instance->status,
                'due_at' => $instance->due_at?->toISOString(),
                'message' => 'Instanz aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren der Instanz: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
