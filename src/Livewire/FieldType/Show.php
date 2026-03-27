<?php

namespace Platform\Qm\Livewire\FieldType;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmFieldType;

class Show extends Component
{
    public QmFieldType $fieldType;

    public function mount(QmFieldType $fieldType)
    {
        $teamId = Auth::user()?->current_team_id;

        // System types are accessible to all, custom types only to their team
        if (!$fieldType->is_system && $fieldType->team_id !== $teamId) {
            abort(403);
        }

        $this->fieldType = $fieldType;
    }

    public function render()
    {
        $teamId = Auth::user()?->current_team_id;

        $this->fieldType->loadCount('fieldDefinitions');

        $fieldDefinitions = $this->fieldType->fieldDefinitions()
            ->where('team_id', $teamId)
            ->with('createdByUser')
            ->latest()
            ->get();

        return view('qm::livewire.field-type.show', [
            'fieldDefinitions' => $fieldDefinitions,
        ])->layout('platform::layouts.app');
    }
}
