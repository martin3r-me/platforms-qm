<?php

namespace Platform\Qm\Livewire\Instance;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Qm\Models\QmInstance;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = auth()->user()?->current_team_id;

        $query = QmInstance::query()
            ->with(['template:id,name', 'createdByUser:id,name'])
            ->withCount(['responses', 'deviations'])
            ->forTeam($teamId);

        if ($this->statusFilter !== '') {
            $query->byStatus($this->statusFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $instances = $query->latest()->paginate(20);

        $stats = [
            'total' => QmInstance::forTeam($teamId)->count(),
            'open' => QmInstance::forTeam($teamId)->byStatus('open')->count(),
            'in_progress' => QmInstance::forTeam($teamId)->byStatus('in_progress')->count(),
            'completed' => QmInstance::forTeam($teamId)->byStatus('completed')->count(),
            'overdue' => QmInstance::forTeam($teamId)->overdue()->count(),
        ];

        return view('qm::livewire.instance.index', [
            'instances' => $instances,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
