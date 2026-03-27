<?php

namespace Platform\Qm\Livewire\FieldType;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmFieldType;

class Index extends Component
{
    public string $search = '';

    public function render()
    {
        $teamId = Auth::user()?->current_team_id;

        $query = QmFieldType::forTeam($teamId)
            ->withCount('fieldDefinitions')
            ->orderBy('is_system', 'desc')
            ->orderBy('key');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('key', 'like', "%{$this->search}%")
                  ->orWhere('label', 'like', "%{$this->search}%");
            });
        }

        $fieldTypes = $query->get();

        $stats = [
            'total' => $fieldTypes->count(),
            'system' => $fieldTypes->where('is_system', true)->count(),
            'custom' => $fieldTypes->where('is_system', false)->count(),
        ];

        return view('qm::livewire.field-type.index', [
            'fieldTypes' => $fieldTypes,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
