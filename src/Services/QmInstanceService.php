<?php

namespace Platform\Qm\Services;

use Illuminate\Support\Str;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmInstanceResponse;
use Platform\Qm\Models\QmTemplate;

class QmInstanceService
{
    /**
     * Create a new instance from a template.
     */
    public function createFromTemplate(QmTemplate $template, array $data): QmInstance
    {
        $snapshot = $template->toSnapshotArray();

        return QmInstance::create([
            'team_id' => $data['team_id'],
            'qm_template_id' => $template->id,
            'title' => $data['title'] ?? $template->name . ' - ' . now()->format('d.m.Y H:i'),
            'description' => $data['description'] ?? null,
            'status' => 'open',
            'snapshot_data' => $snapshot,
            'due_at' => $data['due_at'] ?? null,
            'parent_instance_id' => $data['parent_instance_id'] ?? null,
            'recurrence_config' => $data['recurrence_config'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'],
        ]);
    }

    /**
     * Create an ad-hoc instance (no template).
     */
    public function createAdHoc(array $data): QmInstance
    {
        return QmInstance::create([
            'team_id' => $data['team_id'],
            'qm_template_id' => null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => 'open',
            'snapshot_data' => $data['snapshot_data'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'created_by_user_id' => $data['created_by_user_id'],
        ]);
    }

    /**
     * Update instance metadata.
     */
    public function update(QmInstance $instance, array $data): QmInstance
    {
        $fillable = ['title', 'description', 'status', 'due_at'];

        foreach ($fillable as $field) {
            if (array_key_exists($field, $data)) {
                $instance->{$field} = $data[$field];
            }
        }

        $instance->save();
        return $instance;
    }

    /**
     * Submit responses for an instance.
     * $responses = [ ['field_definition_id' => ..., 'section_id' => ..., 'value' => ..., 'notes' => ...], ... ]
     */
    public function submitResponses(QmInstance $instance, array $responses, int $userId): array
    {
        $created = [];

        foreach ($responses as $responseData) {
            $response = QmInstanceResponse::updateOrCreate(
                [
                    'qm_instance_id' => $instance->id,
                    'qm_field_definition_id' => $responseData['field_definition_id'],
                    'qm_section_id' => $responseData['section_id'] ?? null,
                ],
                [
                    'value' => $responseData['value'],
                    'notes' => $responseData['notes'] ?? null,
                    'is_deviation' => $responseData['is_deviation'] ?? false,
                    'responded_by_user_id' => $userId,
                    'responded_at' => now(),
                ]
            );

            $created[] = $response;
        }

        // Update status to in_progress if still open
        if ($instance->status === 'open') {
            $instance->update(['status' => 'in_progress']);
        }

        // Recalculate score
        $this->recalculateScore($instance);

        return $created;
    }

    /**
     * Complete an instance.
     */
    public function complete(QmInstance $instance, int $userId): QmInstance
    {
        $instance->update([
            'status' => 'completed',
            'completed_at' => now(),
            'completed_by_user_id' => $userId,
        ]);

        $this->recalculateScore($instance);

        return $instance->fresh();
    }

    /**
     * Generate or retrieve a public token for an instance.
     */
    public function generatePublicToken(QmInstance $instance): string
    {
        if (!$instance->public_token) {
            $instance->update([
                'public_token' => Str::random(64),
            ]);
        }

        return $instance->public_token;
    }

    /**
     * Revoke public access.
     */
    public function revokePublicToken(QmInstance $instance): void
    {
        $instance->update(['public_token' => null]);
    }

    /**
     * Recalculate instance score based on responses.
     */
    public function recalculateScore(QmInstance $instance): void
    {
        if (!$instance->snapshot_data) {
            return;
        }

        $snapshot = $instance->snapshot_data;
        $totalRequired = 0;
        $totalAnswered = 0;

        foreach ($snapshot['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if ($field['is_required'] ?? false) {
                    $totalRequired++;
                }
            }
        }

        if ($totalRequired === 0) {
            return;
        }

        $answeredIds = $instance->responses()->pluck('qm_field_definition_id')->toArray();

        foreach ($snapshot['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                if (($field['is_required'] ?? false) && in_array($field['field_definition_id'], $answeredIds)) {
                    $totalAnswered++;
                }
            }
        }

        $score = round(($totalAnswered / $totalRequired) * 100, 2);
        $instance->update(['score' => $score]);
    }

    /**
     * Get completion stats for an instance.
     */
    public function getCompletionStats(QmInstance $instance): array
    {
        $snapshot = $instance->snapshot_data ?? [];
        $totalFields = 0;
        $requiredFields = 0;

        foreach ($snapshot['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $totalFields++;
                if ($field['is_required'] ?? false) {
                    $requiredFields++;
                }
            }
        }

        $responsesCount = $instance->responses()->count();
        $deviationsCount = $instance->responses()->where('is_deviation', true)->count();

        return [
            'total_fields' => $totalFields,
            'required_fields' => $requiredFields,
            'responses_count' => $responsesCount,
            'deviations_count' => $deviationsCount,
            'completion_percent' => $totalFields > 0 ? round(($responsesCount / $totalFields) * 100, 1) : 0,
            'score' => $instance->score,
        ];
    }
}
