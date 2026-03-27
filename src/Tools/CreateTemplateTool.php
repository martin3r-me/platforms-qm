<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Services\QmTemplateService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateTemplateTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.templates.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/templates - Erstellt ein neues QM Template. ERFORDERLICH: name. Optional: description, status, version, settings, i18n.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name des Templates (ERFORDERLICH). z.B. "Tageskontrolle Kueche".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'archived'],
                    'description' => 'Optional: Status. Default: draft.',
                ],
                'version' => [
                    'type' => 'string',
                    'description' => 'Optional: Version. Default: 1.0.',
                ],
                'settings' => [
                    'type' => 'object',
                    'description' => 'Optional: Template-Settings. Keys: haccp_enabled (bool), deviation_workflow (simple|full), escalation_enabled (bool), require_signature (bool), allow_ad_hoc_fields (bool), auto_close_after_hours (int|null).',
                ],
                'i18n' => [
                    'type' => 'object',
                    'description' => 'Optional: Uebersetzungen.',
                ],
            ],
            'required' => ['name'],
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

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $service = new QmTemplateService();
            $template = $service->create([
                'team_id' => $teamId,
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'status' => $arguments['status'] ?? 'draft',
                'version' => $arguments['version'] ?? '1.0',
                'settings' => $arguments['settings'] ?? null,
                'i18n' => $arguments['i18n'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $template->id,
                'uuid' => $template->uuid,
                'name' => $template->name,
                'status' => $template->status,
                'settings' => $template->settings,
                'message' => 'Template erstellt. Nutze "qm.templates.sections.PUT" um Sektionen hinzuzufuegen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Templates: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'templates', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
