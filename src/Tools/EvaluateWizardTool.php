<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class EvaluateWizardTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.evaluate.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/wizard/evaluate - Dry-Run: Wertet Wizard-Antworten aus und zeigt welche Sections aktiv waeren. Erstellt KEINE Instanz.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
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
                    'description' => 'ERFORDERLICH: Key-Value-Paare der Wizard-Antworten. Key = technical_name, Value = Antwort. z.B. {"event_type_id": "gross", "pax": 500}.',
                ],
            ],
            'required' => ['template_id', 'answers'],
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

            $templateId = (int)($arguments['template_id'] ?? 0);
            if ($templateId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'template_id ist erforderlich.');
            }

            $template = QmTemplate::forTeam($teamId)->find($templateId);
            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $answers = $arguments['answers'] ?? [];
            if (empty($answers)) {
                return ToolResult::error('VALIDATION_ERROR', 'answers ist erforderlich.');
            }

            $service = new QmWizardService();
            $result = $service->evaluateWizard($template, $answers);

            return ToolResult::success([
                'template_id' => $template->id,
                'template_name' => $template->name,
                'answers' => $answers,
                'evaluation' => $result,
                'hint' => 'Dies ist ein Dry-Run. Nutze "qm.wizard.create-instance.POST" um eine Instanz zu erstellen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Auswerten des Wizards: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'wizard', 'evaluate', 'dry-run'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
