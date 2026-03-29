<?php

namespace Platform\Qm\Livewire\Lookup;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmLookupTable;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = Auth::user()?->current_team_id;

        $query = QmLookupTable::forTeam($teamId)
            ->with('createdByUser')
            ->withCount('entries');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $tables = $query->orderBy('sort_order')->orderBy('name')->paginate(20);

        $total = QmLookupTable::forTeam($teamId)->count();

        return view('qm::livewire.lookup.index', [
            'tables' => $tables,
            'total' => $total,
        ])->layout('platform::layouts.app');
    }
}
