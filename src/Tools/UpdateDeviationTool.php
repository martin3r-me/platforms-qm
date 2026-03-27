<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Services\QmDeviationService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class UpdateDeviationTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.deviations.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/deviations/:id - Aktualisiert eine Abweichung. ERFORDERLICH: id. Optional: title, description, severity, corrective_action, root_cause, preventive_action. Fuer Workflow-Aktionen nutze acknowledge/resolve/verify/escalate Tools.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => ['type' => 'integer', 'description' => 'Optional: Team-ID.'],
                'id' => ['type' => 'integer', 'description' => 'ID der Abweichung (ERFORDERLICH).'],
                'title' => ['type' => 'string', 'description' => 'Optional: Neuer Titel.'],
                'description' => ['type' => 'string', 'description' => 'Optional: Neue Beschreibung.'],
                'severity' => [
                    'type' => 'string',
                    'enum' => ['low', 'medium', 'high', 'critical'],
                    'description' => 'Optional: Neuer Schweregrad.',
                ],
                'corrective_action' => ['type' => 'string', 'description' => 'Optional: Sofortmassnahme.'],
                'root_cause' => ['type' => 'string', 'description' => 'Optional: Ursachenanalyse (HACCP).'],
                'preventive_action' => ['type' => 'string', 'description' => 'Optional: Praeventivmassnahme (HACCP).'],
                'action' => [
                    'type' => 'string',
                    'enum' => ['acknowledge', 'resolve', 'verify'],
                    'description' => 'Optional: Workflow-Aktion. acknowledge = Kenntnisnahme (HACCP), resolve = Abschliessen, verify = Verifizieren (HACCP).',
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

            $deviation = QmDeviation::whereHas('instance', fn($q) => $q->where('team_id', $teamId))
                ->find((int)$arguments['id']);

            if (!$deviation) {
                return ToolResult::error('NOT_FOUND', 'Abweichung nicht gefunden.');
            }

            $service = new QmDeviationService();

            // Handle workflow actions
            if (isset($arguments['action'])) {
                $deviation = match ($arguments['action']) {
                    'acknowledge' => $service->acknowledge($deviation, $context->user->id),
                    'resolve' => $service->resolve($deviation, $arguments, $context->user->id),
                    'verify' => $service->verify($deviation, $context->user->id),
                    default => $deviation,
                };
            }

            // Handle field updates
            $updateData = [];
            foreach (['title', 'description', 'severity', 'corrective_action', 'root_cause', 'preventive_action'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $updateData[$field] = $arguments[$field];
                }
            }

            if (!empty($updateData)) {
                $deviation = $service->update($deviation, $updateData);
            }

            return ToolResult::success([
                'id' => $deviation->id,
                'uuid' => $deviation->uuid,
                'title' => $deviation->title,
                'severity' => $deviation->severity,
                'status' => $deviation->status,
                'message' => 'Abweichung aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false, 'category' => 'action',
            'tags' => ['qm', 'deviations', 'update'],
            'risk_level' => 'write', 'requires_auth' => true, 'requires_team' => true, 'idempotent' => true,
        ];
    }
}
