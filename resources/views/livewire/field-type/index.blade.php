<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Feldtypen'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-ui-dashboard-tile title="Gesamt" :count="$stats['total']" subtitle="Feldtypen" icon="cube" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="System" :count="$stats['system']" subtitle="Vordefiniert" icon="shield-check" variant="secondary" size="lg" />
                <x-ui-dashboard-tile title="Custom" :count="$stats['custom']" subtitle="Team-spezifisch" icon="plus-circle" variant="secondary" size="lg" />
            </div>

            {{-- Table --}}
            <x-ui-panel title="Feldtypen" subtitle="{{ $stats['total'] }} Typ(en) verfuegbar">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Key</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Label</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Definitionen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($fieldTypes as $type)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.field-types.show', $type)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <span class="font-mono text-xs font-medium text-[var(--ui-secondary)]">{{ $type->key }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $type->label }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$type->is_system ? 'info' : 'warning'" size="sm">
                                    {{ $type->is_system ? 'System' : 'Custom' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $type->field_definitions_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)] truncate max-w-xs">{{ Str::limit($type->description, 50) }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
            </x-ui-panel>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Suche</h3>
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Feldtyp suchen..." size="sm" />
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
