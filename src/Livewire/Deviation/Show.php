<?php

namespace Platform\Qm\Livewire\Deviation;

use Livewire\Component;
use Platform\Qm\Models\QmDeviation;

class Show extends Component
{
    public QmDeviation $deviation;

    public function mount(QmDeviation $deviation): void
    {
        $this->deviation = $deviation->load([
            'instance:id,uuid,title,status',
            'response.fieldDefinition:id,title',
            'createdByUser:id,name',
            'resolvedByUser:id,name',
            'acknowledgedByUser:id,name',
            'verifiedByUser:id,name',
        ]);
    }

    public function render()
    {
        return view('qm::livewire.deviation.show')
            ->layout('platform::layouts.app');
    }
}
