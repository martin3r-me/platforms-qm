<?php

namespace Platform\Qm\Livewire\FieldDefinition;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmFieldDefinition;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $fieldTypeFilter = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFieldTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = Auth::user()?->current_team_id;

        $query = QmFieldDefinition::forTeam($teamId)
            ->with(['fieldType', 'createdByUser'])
            ->withCount('sectionFields');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->fieldTypeFilter) {
            $query->where('qm_field_type_id', $this->fieldTypeFilter);
        }

        $definitions = $query->orderBy('created_at', 'desc')->paginate(20);

        $total = QmFieldDefinition::forTeam($teamId)->count();

        return view('qm::livewire.field-definition.index', [
            'definitions' => $definitions,
            'total' => $total,
        ])->layout('platform::layouts.app');
    }
}
