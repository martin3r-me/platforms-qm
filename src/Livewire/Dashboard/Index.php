<?php

namespace Platform\Qm\Livewire\Dashboard;

use Livewire\Component;
use Platform\Qm\Models\QmTemplate;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmDeviation;
use Platform\Qm\Models\QmLookupTable;

class Index extends Component
{
    public function render()
    {
        $teamId = auth()->user()?->current_team_id;

        $stats = [
            'templates_total' => QmTemplate::forTeam($teamId)->count(),
            'templates_active' => QmTemplate::forTeam($teamId)->byStatus('active')->count(),
            'instances_total' => QmInstance::forTeam($teamId)->count(),
            'instances_open' => QmInstance::forTeam($teamId)->byStatus('open')->count(),
            'instances_completed' => QmInstance::forTeam($teamId)->byStatus('completed')->count(),
            'deviations_open' => QmDeviation::query()
                ->whereHas('instance', fn ($q) => $q->forTeam($teamId))
                ->open()
                ->count(),
            'lookup_tables' => QmLookupTable::forTeam($teamId)->count(),
        ];

        $recentInstances = QmInstance::forTeam($teamId)
            ->with(['template', 'createdByUser'])
            ->latest()
            ->limit(5)
            ->get();

        return view('qm::livewire.dashboard.index', [
            'stats' => $stats,
            'recentInstances' => $recentInstances,
        ])->layout('platform::layouts.app');
    }
}
