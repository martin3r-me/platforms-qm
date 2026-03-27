<?php

namespace Platform\Qm\Livewire\Template;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    public function render()
    {
        $teamId = Auth::user()?->current_team_id;

        $query = QmTemplate::forTeam($teamId)
            ->with('createdByUser')
            ->withCount(['templateSections', 'instances']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        $templates = $query->orderBy('updated_at', 'desc')->paginate(15);

        $stats = [
            'total' => QmTemplate::forTeam($teamId)->count(),
            'draft' => QmTemplate::forTeam($teamId)->byStatus('draft')->count(),
            'active' => QmTemplate::forTeam($teamId)->byStatus('active')->count(),
            'archived' => QmTemplate::forTeam($teamId)->byStatus('archived')->count(),
        ];

        return view('qm::livewire.template.index', [
            'templates' => $templates,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
