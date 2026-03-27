<?php

namespace Platform\Qm\Livewire\Section;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmSection;

class Show extends Component
{
    public QmSection $section;

    public function mount(QmSection $section)
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int)$section->team_id !== $teamId) {
            abort(403);
        }

        $this->section = $section;
    }

    public function render()
    {
        $this->section->load([
            'createdByUser',
            'sectionFields.fieldDefinition.fieldType',
            'templates',
        ]);

        return view('qm::livewire.section.show')->layout('platform::layouts.app');
    }
}
