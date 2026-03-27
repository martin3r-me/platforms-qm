<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmTemplateService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class DuplicateTemplateTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.templates.duplicate.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/templates/{id}/duplicate - Dupliziert ein Template (Deep Copy der Sektions-Zuordnungen). Parameter: template_id (required), name (required).';
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
                    'description' => 'ID des Quell-Templates (ERFORDERLICH).',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des neuen Templates (ERFORDERLICH).',
                ],
            ],
            'required' => ['template_id', 'name'],
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
            $name = trim((string)($arguments['name'] ?? ''));

            if ($templateId <= 0 || $name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'template_id und name sind erforderlich.');
            }

            $template = QmTemplate::query()
                ->with('templateSections')
                ->forTeam($teamId)
                ->find($templateId);

            if (!$template) {
                return ToolResult::error('NOT_FOUND', 'Template nicht gefunden.');
            }

            $service = new QmTemplateService();
            $newTemplate = $service->duplicate($template, $name, $context->user->id);

            return ToolResult::success([
                'id' => $newTemplate->id,
                'uuid' => $newTemplate->uuid,
                'name' => $newTemplate->name,
                'status' => $newTemplate->status,
                'sections_count' => $newTemplate->templateSections->count(),
                'source_template_id' => $templateId,
                'message' => 'Template dupliziert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Duplizieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'templates', 'duplicate'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
