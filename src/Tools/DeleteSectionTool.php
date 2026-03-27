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

class DeleteSectionTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.sections.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /qm/sections/{id} - Soft-loescht eine Sektion. Fehlschlag wenn in Templates verwendet. Parameter: section_id, confirm (required=true).';
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
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true.',
                ],
            ],
            'required' => ['section_id', 'confirm'],
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

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestaetige mit confirm: true.');
            }

            $id = (int)($arguments['section_id'] ?? 0);
            if ($id <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'section_id ist erforderlich.');
            }

            $section = QmSection::query()->forTeam($teamId)->find($id);
            if (!$section) {
                return ToolResult::error('NOT_FOUND', 'Sektion nicht gefunden.');
            }

            $service = new QmSectionService();
            $service->delete($section);

            return ToolResult::success([
                'section_id' => $id,
                'title' => $section->title,
                'message' => 'Sektion geloescht.',
            ]);
        } catch (\RuntimeException $e) {
            return ToolResult::error('IN_USE', $e->getMessage());
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Loeschen: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'sections', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
