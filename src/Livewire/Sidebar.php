<?php

namespace Platform\Qm\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Models\QmLookupTable;
use Livewire\Attributes\On;

class Sidebar extends Component
{
    public bool $showAllTemplates = false;

    public function mount()
    {
        $this->showAllTemplates = false;
    }

    #[On('updateSidebar')]
    public function updateSidebar()
    {
        // Re-render triggers fresh data
    }

    public function toggleShowAllTemplates()
    {
        $this->showAllTemplates = !$this->showAllTemplates;
    }

    public function render()
    {
        $user = auth()->user();
        $teamId = $user?->currentTeam?->id ?? null;

        if (!$user || !$teamId) {
            return view('qm::livewire.sidebar', [
                'templates' => collect(),
                'recentInstances' => collect(),
                'openDeviationsCount' => 0,
                'hasMoreTemplates' => false,
            ]);
        }

        // Templates
        $myTemplates = QmTemplate::forTeam($teamId)
            ->where('created_by_user_id', $user->id)
            ->withCount(['instances', 'wizardFields'])
            ->orderBy('name')
            ->get();

        $allTemplates = QmTemplate::forTeam($teamId)
            ->withCount(['instances', 'wizardFields'])
            ->orderBy('name')
            ->get();

        $templates = $this->showAllTemplates ? $allTemplates : $myTemplates;
        $hasMoreTemplates = $allTemplates->count() > $myTemplates->count();

        // Recent instances
        $recentInstances = QmInstance::forTeam($teamId)
            ->with('template')
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get();

        // Open deviations count
        $openDeviationsCount = QmDeviation::query()
            ->whereHas('instance', fn ($q) => $q->forTeam($teamId))
            ->open()
            ->count();

        return view('qm::livewire.sidebar', [
            'templates' => $templates,
            'recentInstances' => $recentInstances,
            'openDeviationsCount' => $openDeviationsCount,
            'hasMoreTemplates' => $hasMoreTemplates,
        ]);
    }
}
