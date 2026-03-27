<?php

namespace Platform\Qm\Livewire\FieldDefinition;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmFieldDefinition;

class Show extends Component
{
    public QmFieldDefinition $fieldDefinition;

    public function mount(QmFieldDefinition $fieldDefinition)
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int)$fieldDefinition->team_id !== $teamId) {
            abort(403);
        }

        $this->fieldDefinition = $fieldDefinition;
    }

    public function render()
    {
        $this->fieldDefinition->load(['fieldType', 'createdByUser', 'sectionFields.section']);

        return view('qm::livewire.field-definition.show')->layout('platform::layouts.app');
    }
}
