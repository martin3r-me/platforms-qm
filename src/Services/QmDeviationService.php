<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmInstanceResponse;

class QmDeviationService
{
    /**
     * Create a deviation from a response.
     */
    public function createFromResponse(QmInstanceResponse $response, array $data): QmDeviation
    {
        return QmDeviation::create([
            'qm_instance_id' => $response->qm_instance_id,
            'qm_instance_response_id' => $response->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'severity' => $data['severity'] ?? 'low',
            'status' => 'open',
            'workflow_type' => $data['workflow_type'] ?? 'simple',
            'created_by_user_id' => $data['created_by_user_id'],
        ]);
    }

    /**
     * Create a standalone deviation for an instance.
     */
    public function create(array $data): QmDeviation
    {
        return QmDeviation::create([
            'qm_instance_id' => $data['instance_id'],
            'qm_instance_response_id' => $data['response_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'severity' => $data['severity'] ?? 'low',
            'status' => 'open',
            'workflow_type' => $data['workflow_type'] ?? 'simple',
            'created_by_user_id' => $data['created_by_user_id'],
        ]);
    }

    /**
     * Update deviation metadata.
     */
    public function update(QmDeviation $deviation, array $data): QmDeviation
    {
        $fillable = ['title', 'description', 'severity', 'corrective_action', 'root_cause', 'preventive_action'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $deviation->{$field} = $data[$field];
            }
        }

        $deviation->save();
        return $deviation;
    }

    /**
     * Acknowledge a deviation (HACCP full workflow).
     */
    public function acknowledge(QmDeviation $deviation, int $userId): QmDeviation
    {
        $deviation->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $userId,
        ]);

        return $deviation->fresh();
    }

    /**
     * Resolve a deviation (simple: open -> resolved, full: acknowledged -> resolved).
     */
    public function resolve(QmDeviation $deviation, array $data, int $userId): QmDeviation
    {
        $updateData = [
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by_user_id' => $userId,
        ];

        if (isset($data['corrective_action'])) {
            $updateData['corrective_action'] = $data['corrective_action'];
        }
        if (isset($data['root_cause'])) {
            $updateData['root_cause'] = $data['root_cause'];
        }
        if (isset($data['preventive_action'])) {
            $updateData['preventive_action'] = $data['preventive_action'];
        }

        $deviation->update($updateData);
        return $deviation->fresh();
    }

    /**
     * Escalate a deviation.
     */
    public function escalate(QmDeviation $deviation, int $level): QmDeviation
    {
        $deviation->update([
            'escalation_level' => $level,
            'escalated_at' => now(),
        ]);

        return $deviation->fresh();
    }

    /**
     * Verify a resolved deviation (HACCP full workflow).
     */
    public function verify(QmDeviation $deviation, int $userId): QmDeviation
    {
        $deviation->update([
            'status' => 'verified',
            'verified_at' => now(),
            'verified_by_user_id' => $userId,
        ]);

        return $deviation->fresh();
    }

    /**
     * Get deviation stats for a team.
     */
    public function getTeamStats(int $teamId): array
    {
        $query = QmDeviation::whereHas('instance', function ($q) use ($teamId) {
            $q->where('team_id', $teamId);
        });

        return [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->byStatus('open')->count(),
            'acknowledged' => (clone $query)->byStatus('acknowledged')->count(),
            'resolved' => (clone $query)->byStatus('resolved')->count(),
            'verified' => (clone $query)->byStatus('verified')->count(),
            'critical' => (clone $query)->bySeverity('critical')->open()->count(),
            'high' => (clone $query)->bySeverity('high')->open()->count(),
        ];
    }
}
