<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Templates'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" subtitle="Templates" icon="document-duplicate" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Entwurf" :count="$stats['draft']" subtitle="Draft" icon="pencil-square" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Aktiv" :count="$stats['active']" subtitle="In Verwendung" icon="check-circle" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Archiviert" :count="$stats['archived']" subtitle="Abgeschlossen" icon="archive-box" variant="secondary" size="lg" />
            </div>

            {{-- Table --}}
            @if($templates->isNotEmpty())
            <x-ui-panel title="Templates" subtitle="{{ $stats['total'] }} Template(s) in diesem Team">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Version</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Sektionen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Instanzen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">HACCP</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Aktualisiert</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($templates as $template)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.templates.show', $template)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $template->name }}</div>
                                @if($template->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($template->description, 60) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($template->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                                    {{ ucfirst($template->status) }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $template->version }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $template->template_sections_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $template->instances_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($template->getSetting('haccp_enabled'))
                                    <x-ui-badge variant="info" size="sm">HACCP</x-ui-badge>
                                @else
                                    <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $template->updated_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $templates->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-document-duplicate', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Templates</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle ein Template per AI-Assistent. Es braucht Feldtypen &rarr; Feld-Definitionen &rarr; Sektionen &rarr; Template.</p>
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
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Template suchen..." size="sm" />
                </div>

                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Status</h3>
                    <div class="space-y-1">
                        <button wire:click="setStatusFilter('')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === '' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-document-duplicate', 'w-3.5 h-3.5')
                                Alle
                            </span>
                            <span>{{ $stats['total'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('draft')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'draft' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-pencil-square', 'w-3.5 h-3.5')
                                Entwurf
                            </span>
                            <span>{{ $stats['draft'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('active')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'active' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                                Aktiv
                            </span>
                            <span>{{ $stats['active'] }}</span>
                        </button>
                        <button wire:click="setStatusFilter('archived')"
                            class="d-flex items-center justify-between w-full p-2 rounded-md text-xs transition-colors {{ $statusFilter === 'archived' ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                            <span class="d-flex items-center gap-2">
                                @svg('heroicon-o-archive-box', 'w-3.5 h-3.5')
                                Archiviert
                            </span>
                            <span>{{ $stats['archived'] }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
