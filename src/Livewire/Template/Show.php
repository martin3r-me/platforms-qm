<?php

namespace Platform\Qm\Livewire\Template;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;

class Show extends Component
{
    public QmTemplate $template;

    public function mount(QmTemplate $template)
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int)$template->team_id !== $teamId) {
            abort(403);
        }

        $this->template = $template;
    }

    public function render()
    {
        $this->template->load([
            'createdByUser',
            'templateSections.section.sectionFields.fieldDefinition.fieldType',
        ]);
        $this->template->loadCount('instances');

        return view('qm::livewire.template.show')->layout('platform::layouts.app');
    }
}
