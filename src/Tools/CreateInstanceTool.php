<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateInstanceTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/instances - Erstellt eine neue QM Checklisten-Instanz aus einem Template. ERFORDERLICH: template_id. Optional: title, description, due_at.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'template_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Templates, aus dem die Instanz erstellt wird (ERFORDERLICH).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Titel. Default: Templatename + Datum.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'due_at' => [
                    'type' => 'string',
                    'description' => 'Optional: Faelligkeitsdatum (ISO 8601). z.B. "2025-01-15T17:00:00Z".',
                ],
            ],
            'required' => ['template_id'],
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

            $templateId = (int)($arguments['template_id'] ?? 0);
            if ($templateId === 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = QmTemplate::forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            if ($template->status !== 'active') {
                return ToolResult::error('VALIDATION_ERROR', 'Template muss Status "active" haben. Aktuell: ' . $template->status);
            }

            $service = new QmInstanceService();
            $instance = $service->createFromTemplate($template, [
                'team_id' => $teamId,
                'title' => $arguments['title'] ?? null,
                'description' => $arguments['description'] ?? null,
                'due_at' => isset($arguments['due_at']) ? \Carbon\Carbon::parse($arguments['due_at']) : null,
                'created_by_user_id' => $context->user->id,
            ]);

            $stats = $service->getCompletionStats($instance);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'status' => $instance->status,
                'template' => ['id' => $template->id, 'name' => $template->name],
                'completion_stats' => $stats,
                'message' => 'Instanz erstellt. Nutze "qm.instances.responses.PUT" um Antworten zu erfassen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Instanz: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
