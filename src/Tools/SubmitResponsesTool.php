<?php

namespace Platform\Qm\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;
use Platform\Qm\Tools\Concerns\ResolvesQmTeam;

class SubmitResponsesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.responses.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /qm/instances/:id/responses - Erfasst Antworten fuer eine QM Instanz. ERFORDERLICH: id, responses (Array). Jede Response: field_definition_id, value, optional: section_id, notes, is_deviation.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID.',
                ],
                'id' => [
                    'type' => 'integer',
                    'description' => 'ID der Instanz (ERFORDERLICH).',
                ],
                'responses' => [
                    'type' => 'array',
                    'description' => 'Array von Responses (ERFORDERLICH). Jede Response braucht: field_definition_id (int), value (any). Optional: section_id (int), notes (string), is_deviation (bool).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'field_definition_id' => ['type' => 'integer', 'description' => 'ID der Feld-Definition.'],
                            'section_id' => ['type' => 'integer', 'description' => 'Optional: ID der Sektion.'],
                            'value' => ['description' => 'Der Wert der Antwort. Typ haengt vom Feldtyp ab.'],
                            'notes' => ['type' => 'string', 'description' => 'Optional: Notizen zur Antwort.'],
                            'is_deviation' => ['type' => 'boolean', 'description' => 'Optional: Ist dies eine Abweichung?'],
                        ],
                        'required' => ['field_definition_id', 'value'],
                    ],
                ],
            ],
            'required' => ['id', 'responses'],
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

            $instance = QmInstance::forTeam($teamId)->find((int)$arguments['id']);
            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            if (in_array($instance->status, ['completed', 'cancelled'])) {
                return ToolResult::error('VALIDATION_ERROR', 'Instanz ist bereits abgeschlossen oder abgebrochen.');
            }

            $responses = $arguments['responses'] ?? [];
            if (empty($responses)) {
                return ToolResult::error('VALIDATION_ERROR', 'responses ist erforderlich und darf nicht leer sein.');
            }

            $service = new QmInstanceService();
            $created = $service->submitResponses($instance, $responses, $context->user->id);

            $instance->refresh();
            $stats = $service->getCompletionStats($instance);

            $deviationCount = collect($created)->where('is_deviation', true)->count();

            return ToolResult::success([
                'instance_id' => $instance->id,
                'responses_submitted' => count($created),
                'deviations_detected' => $deviationCount,
                'instance_status' => $instance->status,
                'completion_stats' => $stats,
                'message' => count($created) . ' Response(s) erfasst.' . ($deviationCount > 0 ? " {$deviationCount} Abweichung(en) erkannt." : ''),
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erfassen der Responses: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'responses', 'submit'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
