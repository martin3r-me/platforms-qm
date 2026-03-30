<?php

namespace Platform\Qm\Livewire\Instance;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;

class Show extends Component
{
    public QmInstance $instance;
    public int $activeSection = 0;

    public function mount(QmInstance $instance): void
    {
        $this->instance = $instance->load([
            'template:id,uuid,name,status,version',
            'responses',
            'deviations',
            'createdByUser:id,name',
            'completedByUser:id,name',
        ]);
    }

    public function setSection(int $index): void
    {
        $this->activeSection = $index;
    }

    public function prevSection(): void
    {
        if ($this->activeSection > 0) {
            $this->activeSection--;
        }
    }

    public function nextSection(): void
    {
        $snapshot = $this->instance->snapshot_data ?? [];
        $maxIndex = count($snapshot['sections'] ?? []) - 1;
        if ($this->activeSection < $maxIndex) {
            $this->activeSection++;
        }
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

        // Build flat sections array
        $sections = [];
        $allRequiredAnswered = true;

        foreach ($snapshot['sections'] ?? [] as $sectionData) {
            $fields = [];
            $answeredCount = 0;
            foreach ($sectionData['fields'] ?? [] as $fieldData) {
                $key = $fieldData['field_definition_id'] . '-' . ($sectionData['section_id'] ?? '');
                $response = $responsesMap->get($key)?->first();
                $isChecked = $response !== null;
                if ($isChecked) {
                    $answeredCount++;
                }

                if (($fieldData['is_required'] ?? false) && !$isChecked) {
                    $allRequiredAnswered = false;
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

            $sections[] = [
                'section_id' => $sectionData['section_id'] ?? null,
                'title' => $sectionData['title'],
                'description' => $sectionData['description'] ?? null,
                'phase_label' => $sectionData['phase_label'] ?? 'Allgemein',
                'fields' => $fields,
                'total' => count($fields),
                'answered' => $answeredCount,
            ];
        }

        // Clamp activeSection
        if ($this->activeSection >= count($sections)) {
            $this->activeSection = max(0, count($sections) - 1);
        }

        return view('qm::livewire.instance.show', [
            'stats' => $stats,
            'sections' => $sections,
            'allRequiredAnswered' => $allRequiredAnswered,
        ])->layout('platform::layouts.app');
    }
}
