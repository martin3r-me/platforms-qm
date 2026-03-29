<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmWizardService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class GetWizardConfigTool implements ToolContract, ToolMetadataContract
{
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.wizard.GET';
    }

    public function getDescription(): string
    {
        return 'GET /qm/wizard/{template_id} - Zeigt die komplette Wizard-Konfiguration eines Templates (Felder, Regeln, Sections-Matrix).';
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
            ],
            'required' => ['template_id'],
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

            $service = new QmWizardService();
            $config = $service->getWizardConfig($template);

            return ToolResult::success($config);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Wizard-Konfiguration: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['qm', 'wizard', 'config'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
