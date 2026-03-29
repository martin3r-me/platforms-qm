<?php

namespace Platform\Qm\Livewire\Wizard;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Services\QmWizardService;

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
        $service = new QmWizardService();
        $config = $service->getWizardConfig($this->template);

        return view('qm::livewire.wizard.show', [
            'config' => $config,
        ])->layout('platform::layouts.app');
    }
}
