<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmSection;
use Platform\Qm\Services\QmSectionService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class UpdateSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.sections.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/sections/{id} - Aktualisiert eine Sektion. Parameter: section_id (required). Optional: title, description, i18n.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'section_id' => [
                    'type' => 'integer',
                    'description' => 'ID der Sektion (ERFORDERLICH).',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'i18n' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Uebersetzungen.',
                ],
            ],
            'required' => ['section_id'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $id = (int)($arguments['section_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');
            }

            $section = QmSection::query()->forTeam($teamId)->find($id);
            if (!$section) {
                return ToolResult::error('NOT_FOUND', 'Sektion nicht gefunden.');
            }

            $service = new QmSectionService();
            $section = $service->update($section, $arguments);

            return ToolResult::success([
                'id' => $section->id,
                'uuid' => $section->uuid,
                'title' => $section->title,
                'message' => 'Sektion aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'sections', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
