<?php

namespace Platform\Qm\Livewire\Lookup;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmLookupTable;

class Show extends Component
{
    public QmLookupTable $lookupTable;

    public function mount(QmLookupTable $lookupTable)
    {
        $teamId = Auth::user()?->current_team_id;

        if ((int)$lookupTable->team_id !== $teamId) {
            abort(403);
        }

        $this->lookupTable = $lookupTable;
    }

    public function render()
    {
        $this->lookupTable->load([
            'entries' => fn ($q) => $q->orderBy('sort_order'),
            'createdByUser',
        ]);
        $this->lookupTable->loadCount(['entries', 'wizardFields']);

        return view('qm::livewire.lookup.show')->layout('platform::layouts.app');
    }
}
