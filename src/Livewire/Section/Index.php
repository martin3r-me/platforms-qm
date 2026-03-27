<?php

namespace Platform\Qm\Livewire\Section;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmSection;

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

        $query = QmSection::forTeam($teamId)
            ->with('createdByUser')
            ->withCount(['sectionFields', 'templates']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('title', 'like', "%{$this->search}%")
                  ->orWhere('description', 'like', "%{$this->search}%");
            });
        }

        $sections = $query->orderBy('created_at', 'desc')->paginate(20);

        $total = QmSection::forTeam($teamId)->count();

        return view('qm::livewire.section.index', [
            'sections' => $sections,
            'total' => $total,
        ])->layout('platform::layouts.app');
    }
}
