<?php

namespace Platform\Qm\Livewire\Instance;

use Livewire\Component;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Services\QmInstanceService;

class Show extends Component
{
    public QmInstance $instance;

    public function mount(QmInstance $instance): void
    {
        $this->instance = $instance->load([
            'template:id,uuid,name,status,version',
            'responses.fieldDefinition:id,title',
            'responses.section:id,title',
            'responses.respondedByUser:id,name',
            'deviations',
            'createdByUser:id,name',
            'completedByUser:id,name',
        ]);
    }

    public function render()
    {
        $service = new QmInstanceService();
        $stats = $service->getCompletionStats($this->instance);

        // Group responses by section from snapshot
        $sections = [];
        $snapshot = $this->instance->snapshot_data ?? [];
        $responsesMap = $this->instance->responses->keyBy('qm_field_definition_id');

        foreach ($snapshot['sections'] ?? [] as $sectionData) {
            $fields = [];
            foreach ($sectionData['fields'] ?? [] as $fieldData) {
                $response = $responsesMap->get($fieldData['field_definition_id']);
                $fields[] = [
                    'title' => $fieldData['title'],
                    'field_type' => $fieldData['field_type_key'] ?? '-',
                    'is_required' => $fieldData['is_required'] ?? false,
                    'response' => $response,
                ];
            }
            $sections[] = [
                'title' => $sectionData['title'],
                'description' => $sectionData['description'] ?? null,
                'fields' => $fields,
            ];
        }

        return view('qm::livewire.instance.show', [
            'stats' => $stats,
            'sections' => $sections,
        ])->layout('platform::layouts.app');
    }
}
