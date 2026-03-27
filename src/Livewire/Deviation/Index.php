<?php

namespace Platform\Qm\Livewire\Deviation;

use Livewire\Component;
use Livewire\WithPagination;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Services\QmDeviationService;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $severityFilter = '';

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $status;
        $this->resetPage();
    }

    public function setSeverityFilter(string $severity): void
    {
        $this->severityFilter = $severity;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $teamId = auth()->user()?->current_team_id;

        $query = QmDeviation::query()
            ->with(['instance:id,title', 'createdByUser:id,name'])
            ->whereHas('instance', fn($q) => $q->where('team_id', $teamId));

        if ($this->statusFilter !== '') {
            $query->byStatus($this->statusFilter);
        }

        if ($this->severityFilter !== '') {
            $query->bySeverity($this->severityFilter);
        }

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $deviations = $query->latest()->paginate(20);

        $service = new QmDeviationService();
        $stats = $service->getTeamStats($teamId);

        return view('qm::livewire.deviation.index', [
            'deviations' => $deviations,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
