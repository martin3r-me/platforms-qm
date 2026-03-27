<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Services\QmSectionService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class CreateSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.sections.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/sections - Erstellt eine neue Sektion. ERFORDERLICH: title. Optional: description, i18n.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel der Sektion (ERFORDERLICH). z.B. "Temperaturkontrolle".',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'i18n' => [
                    'type' => 'object',
                    'description' => 'Optional: Uebersetzungen.',
                ],
            ],
            'required' => ['title'],
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

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $service = new QmSectionService();
            $section = $service->create([
                'team_id' => $teamId,
                'title' => $title,
                'description' => $arguments['description'] ?? null,
                'i18n' => $arguments['i18n'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'message' => 'Sektion erstellt. Nutze "qm.sections.fields.PUT" um Felder hinzuzufuegen.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der Sektion: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'sections', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
