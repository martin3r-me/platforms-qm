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

class PublicLinkTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesQmTeam;

    public function getName(): string
    {
        return 'qm.instances.public-link.POST';
    }

    public function getDescription(): string
    {
        return 'POST /qm/instances/:id/public-link - Generiert oder widerruft einen oeffentlichen Link fuer eine QM Instanz. ERFORDERLICH: id. Optional: action (generate|revoke, default: generate).';
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
                'action' => [
                    'type' => 'string',
                    'enum' => ['generate', 'revoke'],
                    'description' => 'Optional: "generate" (Default) erstellt einen Token, "revoke" entfernt ihn.',
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

            $instance = QmInstance::forTeam($teamId)->find((int)$arguments['id']);
            if (!$instance) {
                return ToolResult::error('NOT_FOUND', 'Instanz nicht gefunden.');
            }

            $action = $arguments['action'] ?? 'generate';
            $service = new QmInstanceService();

            if ($action === 'revoke') {
                $service->revokePublicToken($instance);

                return ToolResult::success([
                    'id' => $instance->id,
                    'public_token' => null,
                    'message' => 'Oeffentlicher Link widerrufen.',
                ]);
            }

            $token = $service->generatePublicToken($instance);

            return ToolResult::success([
                'id' => $instance->id,
                'uuid' => $instance->uuid,
                'public_token' => $token,
                'message' => 'Oeffentlicher Link generiert. Der Token kann fuer Guest-Zugriff verwendet werden.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Verwalten des Public Links: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['qm', 'instances', 'public-link'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
