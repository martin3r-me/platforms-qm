<?php

namespace Platform\Qm\Livewire\Instance;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;

class Show extends Component
{
    public QmInstance $instance;
    public string $activePhase = '';

    public function mount(QmInstance $instance): void
    {
        $this->instance = $instance->load([
            'template:id,uuid,name,status,version',
            'responses',
            'deviations',
            'createdByUser:id,name',
            'completedByUser:id,name',
        ]);

        // Set initial active phase from first section's phase_label
        $snapshot = $this->instance->snapshot_data ?? [];
        foreach ($snapshot['sections'] ?? [] as $sectionData) {
            if (!empty($sectionData['phase_label'])) {
                $this->activePhase = $sectionData['phase_label'];
                break;
            }
        }
        if (empty($this->activePhase)) {
            $this->activePhase = 'Allgemein';
        }
    }

    public function setPhase(string $phase): void
    {
        $this->activePhase = $phase;
    }

    public function toggleField(int $fieldDefinitionId, int $sectionId): void
    {
        if (in_array($this->instance->status, ['completed', 'cancelled'])) {
            return;
        }

        $user = Auth::user();
        $service = new QmInstanceService();

        // Check if response exists
        $existing = $this->instance->responses()
            ->where('qm_field_definition_id', $fieldDefinitionId)
            ->where('qm_section_id', $sectionId)
            ->first();

        if ($existing) {
            // Unchecked: delete response
            $existing->delete();
        } else {
            // Checked: create response
            $service->submitResponses($this->instance, [
                [
                    'field_definition_id' => $fieldDefinitionId,
                    'section_id' => $sectionId,
                    'value' => true,
                ],
            ], $user->id);
        }

        // Refresh instance data
        $this->instance->load('responses');
        $service->recalculateScore($this->instance);
        $this->instance->refresh();
    }

    public function completeInstance(): void
    {
        $user = Auth::user();
        $service = new QmInstanceService();
        $this->instance = $service->complete($this->instance, $user->id);
        $this->instance->load([
            'template:id,uuid,name,status,version',
            'responses',
            'deviations',
            'createdByUser:id,name',
            'completedByUser:id,name',
        ]);
    }

    public function render()
    {
        $service = new QmInstanceService();
        $stats = $service->getCompletionStats($this->instance);

        $snapshot = $this->instance->snapshot_data ?? [];
        $responsesMap = $this->instance->responses->groupBy(function ($r) {
            return $r->qm_field_definition_id . '-' . $r->qm_section_id;
        });

        // Build phases with sections and field data
        $phases = [];
        foreach ($snapshot['sections'] ?? [] as $sectionData) {
            $phase = $sectionData['phase_label'] ?? 'Allgemein';

            $fields = [];
            $answeredCount = 0;
            foreach ($sectionData['fields'] ?? [] as $fieldData) {
                $key = $fieldData['field_definition_id'] . '-' . ($sectionData['section_id'] ?? '');
                $response = $responsesMap->get($key)?->first();
                $isChecked = $response !== null;
                if ($isChecked) {
                    $answeredCount++;
                }

                $fields[] = [
                    'field_definition_id' => $fieldData['field_definition_id'],
                    'title' => $fieldData['title'],
                    'field_type' => $fieldData['field_type_key'] ?? '-',
                    'is_required' => $fieldData['is_required'] ?? false,
                    'is_checked' => $isChecked,
                    'response' => $response,
                ];
            }

            if (!isset($phases[$phase])) {
                $phases[$phase] = [
                    'label' => $phase,
                    'sections' => [],
                    'total_fields' => 0,
                    'answered_fields' => 0,
                ];
            }

            $phases[$phase]['sections'][] = [
                'section_id' => $sectionData['section_id'] ?? null,
                'title' => $sectionData['title'],
                'description' => $sectionData['description'] ?? null,
                'fields' => $fields,
                'total' => count($fields),
                'answered' => $answeredCount,
            ];
            $phases[$phase]['total_fields'] += count($fields);
            $phases[$phase]['answered_fields'] += $answeredCount;
        }

        // Check if all required fields are answered
        $allRequiredAnswered = true;
        foreach ($phases as $phase) {
            foreach ($phase['sections'] as $section) {
                foreach ($section['fields'] as $field) {
                    if ($field['is_required'] && !$field['is_checked']) {
                        $allRequiredAnswered = false;
                        break 3;
                    }
                }
            }
        }

        return view('qm::livewire.instance.show', [
            'stats' => $stats,
            'phases' => $phases,
            'allRequiredAnswered' => $allRequiredAnswered,
        ])->layout('platform::layouts.app');
    }
}
