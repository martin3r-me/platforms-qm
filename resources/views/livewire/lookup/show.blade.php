<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Stammdaten', 'href' => route('qm.lookups.index')],
            ['label' => $lookupTable->name],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Entries --}}
            <x-ui-panel title="Eintraege" subtitle="{{ $lookupTable->entries->count() }} Eintrag/Eintraege">
                @if($lookupTable->entries->isNotEmpty())
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Pos.</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Label</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Wert</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($lookupTable->entries as $entry)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $entry->sort_order }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $entry->label }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $entry->value }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$entry->is_active ? 'success' : 'secondary'" size="sm">
                                    {{ $entry->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $entry->description ?? '-' }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Noch keine Eintraege in dieser Tabelle.</div>
                @endif
            </x-ui-panel>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Details" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Status</h3>
                    <x-ui-badge :variant="$lookupTable->is_active ? 'success' : 'secondary'">
                        {{ $lookupTable->is_active ? 'Aktiv' : 'Inaktiv' }}
                    </x-ui-badge>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Info</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Eintraege</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $lookupTable->entries_count }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">In Wizard-Feldern</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $lookupTable->wizard_fields_count }}</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-[var(--ui-muted)]">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-user', 'w-3.5 h-3.5')
                        {{ $lookupTable->createdByUser?->name ?? 'Unbekannt' }}
                    </div>
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        {{ $lookupTable->created_at?->format('d.m.Y H:i') }}
                    </div>
                </div>

                @if($lookupTable->description)
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Beschreibung</h3>
                    <p class="text-xs text-[var(--ui-muted)] leading-relaxed">{{ $lookupTable->description }}</p>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
