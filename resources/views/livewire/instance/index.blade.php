<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Checklisten'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" subtitle="Checklisten" icon="clipboard-document-list" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Offen" :count="$stats['open']" subtitle="Noch nicht begonnen" icon="document-text" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="In Bearbeitung" :count="$stats['in_progress']" subtitle="Wird ausgefuellt" icon="pencil-square" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Abgeschlossen" :count="$stats['completed']" subtitle="Fertig" icon="check-circle" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Ueberfaellig" :count="$stats['overdue']" subtitle="Deadline ueberschritten" icon="exclamation-triangle" :variant="$stats['overdue'] > 0 ? 'danger' : 'secondary'" size="lg" />
            </div>

            {{-- Table --}}
            @if($instances->isNotEmpty())
            <x-ui-panel title="Checklisten" subtitle="{{ $stats['total'] }} Checkliste(n) in diesem Team">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Template</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Score</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Antworten</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Abweichungen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($instances as $instance)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.instances.show', $instance)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $instance->title }}</div>
                                @if($instance->due_at)
                                <div class="text-xs {{ $instance->due_at->isPast() && !in_array($instance->status, ['completed', 'cancelled']) ? 'text-red-500' : 'text-[var(--ui-muted)]' }} mt-0.5">
                                    Faellig: {{ $instance->due_at->format('d.m.Y H:i') }}
                                </div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->template?->name ?? 'Ad-hoc' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($instance->status) {
                                    'completed' => 'success',
                                    'in_progress' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                }">
                                    {{ match($instance->status) {
                                        'open' => 'Offen',
                                        'in_progress' => 'In Bearbeitung',
                                        'completed' => 'Abgeschlossen',
                                        'cancelled' => 'Abgebrochen',
                                        default => ucfirst($instance->status),
                                    } }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($instance->score !== null)
                                <span class="text-xs font-mono {{ $instance->score >= 80 ? 'text-green-600' : ($instance->score >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                                    {{ number_format($instance->score, 0) }}%
                                </span>
                                @else
                                <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->responses_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($instance->deviations_count > 0)
                                <x-ui-badge variant="danger" size="sm">{{ $instance->deviations_count }}</x-ui-badge>
                                @else
                                <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $instances->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-clipboard-document-list', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Checklisten</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle eine Checkliste per AI-Assistent aus einem aktiven Template.</p>
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
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Checkliste suchen..." size="sm" />
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <div class="space-y-1">
                        <button wire:click="setStatusFilter('')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === '' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-clipboard-document-list', 'w-3.5 h-3.5')
                                Alle
                            </span>
                            <span>{{ $stats['total'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('open')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'open' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-document-text', 'w-3.5 h-3.5')
                                Offen
                            </span>
                            <span>{{ $stats['open'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('in_progress')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'in_progress' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5')
                                In Bearbeitung
                            </span>
                            <span>{{ $stats['in_progress'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('completed')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'completed' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                                Abgeschlossen
                            </span>
                            <span>{{ $stats['completed'] }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
