<x-ui-page>
    <x-slot name="navbar">
        <div class="d-flex align-items-center gap-2">
            <x-heroicon-o-clipboard-document-check class="w-5 h-5" />
            <span class="font-semibold">Quality Management</span>
        </div>
    </x-slot>

    <x-slot name="actionbar">
        {{-- Platzhalter fuer Aktionen --}}
    </x-slot>

    <x-slot name="main">
        <div class="space-y-6">
            {{-- Stats Grid --}}
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-[var(--ui-muted-5)] rounded-lg p-4">
                    <div class="text-sm text-muted">Templates (Aktiv)</div>
                    <div class="text-2xl font-bold">{{ $stats['templates_active'] }}</div>
                    <div class="text-xs text-muted">von {{ $stats['templates_total'] }} gesamt</div>
                </div>
                <div class="bg-[var(--ui-muted-5)] rounded-lg p-4">
                    <div class="text-sm text-muted">Offene Checklisten</div>
                    <div class="text-2xl font-bold">{{ $stats['instances_open'] }}</div>
                    <div class="text-xs text-muted">{{ $stats['instances_completed'] }} abgeschlossen</div>
                </div>
                <div class="bg-[var(--ui-muted-5)] rounded-lg p-4">
                    <div class="text-sm text-muted">Offene Abweichungen</div>
                    <div class="text-2xl font-bold {{ $stats['deviations_open'] > 0 ? 'text-red-500' : '' }}">{{ $stats['deviations_open'] }}</div>
                </div>
            </div>

            {{-- Recent Instances --}}
            <div>
                <h3 class="text-lg font-semibold mb-3">Letzte Checklisten</h3>
                @if($recentInstances->isEmpty())
                    <div class="bg-[var(--ui-muted-5)] rounded-lg p-6 text-center text-muted">
                        Noch keine Checklisten vorhanden. Erstelle zuerst ein Template.
                    </div>
                @else
                    <div class="space-y-2">
                        @foreach($recentInstances as $instance)
                            <div class="bg-[var(--ui-muted-5)] rounded-lg p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="font-medium">{{ $instance->title }}</div>
                                    <div class="text-xs text-muted">
                                        {{ $instance->template?->name ?? 'Ad-hoc' }}
                                        &middot; {{ $instance->createdByUser?->name }}
                                        &middot; {{ $instance->created_at?->diffForHumans() }}
                                    </div>
                                </div>
                                <x-ui-badge :color="match($instance->status) {
                                    'completed' => 'green',
                                    'in_progress' => 'blue',
                                    'cancelled' => 'red',
                                    default => 'gray',
                                }">{{ $instance->status }}</x-ui-badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <x-slot name="sidebar">
        <div class="space-y-4">
            <div class="text-sm font-semibold text-muted uppercase">Navigation</div>
            <div class="space-y-1">
                <a href="{{ route('qm.dashboard') }}" class="d-flex align-items-center gap-2 p-2 rounded-lg bg-[var(--ui-muted-5)]">
                    <x-heroicon-o-home class="w-4 h-4" />
                    <span>Dashboard</span>
                </a>
            </div>
        </div>
    </x-slot>
</x-ui-page>
