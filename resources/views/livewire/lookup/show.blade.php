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
            {{-- Info --}}
            <x-ui-panel>
                <div class="p-4">
                    @if($lookupTable->description)
                    <p class="text-sm text-[var(--ui-muted)] leading-relaxed mb-3">{{ $lookupTable->description }}</p>
                    @endif
                    <div class="d-flex items-center gap-3 flex-wrap text-xs text-[var(--ui-muted)]">
                        <x-ui-badge :variant="$lookupTable->is_active ? 'success' : 'secondary'">
                            {{ $lookupTable->is_active ? 'Aktiv' : 'Inaktiv' }}
                        </x-ui-badge>
                        <span>{{ $lookupTable->entries_count }} Eintraege</span>
                        <span>{{ $lookupTable->wizard_fields_count }} Wizard-Felder</span>
                        <span class="text-[var(--ui-border)]">|</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3.5 h-3.5') {{ $lookupTable->createdByUser?->name ?? 'Unbekannt' }}</span>
                        <span>{{ $lookupTable->created_at?->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </x-ui-panel>

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
</x-ui-page>
