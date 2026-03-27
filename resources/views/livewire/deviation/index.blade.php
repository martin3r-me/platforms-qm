<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Abweichungen'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" subtitle="Abweichungen" icon="exclamation-triangle" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Offen" :count="$stats['open']" subtitle="Noch nicht behoben" icon="exclamation-circle" :variant="$stats['open'] > 0 ? 'danger' : 'secondary'" size="lg" />
                <x-ui-dashboard-tile title="Kritisch" :count="$stats['critical']" subtitle="Offene kritische" icon="fire" :variant="$stats['critical'] > 0 ? 'danger' : 'secondary'" size="lg" />
                <x-ui-dashboard-tile title="Verifiziert" :count="$stats['verified']" subtitle="Abgeschlossen" icon="check-badge" variant="secondary" size="lg" />
            </div>

            {{-- Table --}}
            @if($deviations->isNotEmpty())
            <x-ui-panel title="Abweichungen" subtitle="{{ $stats['total'] }} Abweichung(en)">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Checkliste</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Schwere</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Eskalation</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($deviations as $deviation)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.deviations.show', $deviation)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $deviation->title }}</div>
                                @if($deviation->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($deviation->description, 60) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $deviation->instance?->title ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($deviation->severity) {
                                    'critical' => 'danger',
                                    'high' => 'danger',
                                    'medium' => 'warning',
                                    default => 'secondary',
                                }" size="sm">
                                    {{ ucfirst($deviation->severity ?? 'low') }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($deviation->status) {
                                    'resolved' => 'success',
                                    'verified' => 'success',
                                    'acknowledged' => 'info',
                                    default => 'warning',
                                }" size="sm">
                                    {{ match($deviation->status) {
                                        'open' => 'Offen',
                                        'acknowledged' => 'Bestätigt',
                                        'resolved' => 'Behoben',
                                        'verified' => 'Verifiziert',
                                        default => ucfirst($deviation->status),
                                    } }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($deviation->escalation_level)
                                <x-ui-badge variant="danger" size="sm">Level {{ $deviation->escalation_level }}</x-ui-badge>
                                @else
                                <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $deviation->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $deviations->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-exclamation-triangle', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Keine Abweichungen</h3>
                    <p class="text-[var(--ui-muted)]">Abweichungen werden beim Ausfuellen von Checklisten erkannt.</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Suche</h3>
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Abweichung suchen..." size="sm" />
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <div class="space-y-1">
                        <button wire:click="setStatusFilter('')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === '' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Alle</span>
                            <span>{{ $stats['total'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('open')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'open' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Offen</span>
                            <span>{{ $stats['open'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('acknowledged')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'acknowledged' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Bestaetigt</span>
                            <span>{{ $stats['acknowledged'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('resolved')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'resolved' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Behoben</span>
                            <span>{{ $stats['resolved'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('verified')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'verified' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span>Verifiziert</span>
                            <span>{{ $stats['verified'] }}</span>
                        </button>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Schwere</h3>
                    <div class="space-y-1">
                        @foreach(['low' => 'Niedrig', 'medium' => 'Mittel', 'high' => 'Hoch', 'critical' => 'Kritisch'] as $key => $label)
                        <button wire:click="setSeverityFilter('{{ $severityFilter === $key ? '' : $key }}')"
                            class="d-flex items-center w-full p-2 rounded-md text-xs transition-colors {{ $severityFilter === $key ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            {{ $label }}
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
