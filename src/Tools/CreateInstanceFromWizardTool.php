<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateInstanceFromWizardTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.create-instance.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/wizard/create-instance - Erstellt eine QM-Instanz basierend auf Wizard-Antworten. Nur die relevanten Sections werden in den Snapshot uebernommen.';
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
                    'description' => 'ID des Templates (ERFORDERLICH).',
                ],
                'answers' => [
                    'type' => 'object',
                    'description' => 'ERFORDERLICH: Wizard-Antworten. Key = technical_name, Value = Antwort.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Titel der Instanz. Default: Templatename + Datum.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'due_at' => [
                    'type' => 'string',
                    'description' => 'Optional: Faelligkeitsdatum (ISO 8601).',
                ],
            ],
            'required' => ['template_id', 'answers'],
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
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = QmTemplate::forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            if ($template->status !== 'active') {
                return ToolResult::error('VALIDATION_ERROR', 'Template muss Status "active" haben. Aktuell: ' . $template->status);
            }

            $answers = $arguments['answers'] ?? [];
            if (empty($answers)) {
                return ToolResult::error('VALIDATION_ERROR', 'answers ist erforderlich.');
            }

            // Validate required wizard fields
            $requiredFields = $template->wizardFields()->where('is_required', true)->get();
            foreach ($requiredFields as $field) {
                if (!isset($answers[$field->technical_name]) || $answers[$field->technical_name] === '' || $answers[$field->technical_name] === null) {
                    return ToolResult::error('VALIDATION_ERROR', "Pflichtfeld '{$field->label}' ({$field->technical_name}) fehlt in answers.");
                }
            }

            $wizardService = new QmWizardService();
            $instance = $wizardService->createInstanceFromWizard($template, $answers, [
                'team_id' => $teamId,
                'title' => $arguments['title'] ?? null,
                'description' => $arguments['description'] ?? null,
                'due_at' => isset($arguments['due_at']) ? \Carbon\Carbon::parse($arguments['due_at']) : null,
                'created_by_user_id' => $context->user->id,
            ]);

            $instanceService = new QmInstanceService();
            $stats = $instanceService->getCompletionStats($instance);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'title' => $instance->title,
                'status' => $instance->status,
                'template' => ['id' => $template->id, 'name' => $template->name],
                'wizard_answers' => $answers,
                'sections_count' => count($instance->snapshot_data['sections'] ?? []),
                'completion_stats' => $stats,
                'message' => 'Instanz aus Wizard erstellt. Nutze "qm.instances.responses.PUT" um Antworten zu erfassen.',
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
            'tags' => ['qm', 'wizard', 'instance', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
