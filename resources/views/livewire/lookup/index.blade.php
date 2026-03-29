<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Stammdaten'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            @if($tables->isNotEmpty())
            <x-ui-panel title="Stammdaten (Lookup-Tabellen)" subtitle="{{ $total }} Tabelle(n) in diesem Team">
                <div class="px-4 pt-3 pb-2">
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Tabelle suchen..." size="sm" />
                </div>
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Name</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Eintraege</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($tables as $table)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.lookups.show', $table)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $table->name }}</div>
                                @if($table->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($table->description, 60) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $table->entries_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$table->is_active ? 'success' : 'secondary'" size="sm">
                                    {{ $table->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $table->createdByUser?->name ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $table->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $tables->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-table-cells', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Stammdaten</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle Lookup-Tabellen per AI-Assistent (z.B. Event-Typen, Locations, Maschinentypen).</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
