<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Feld-Definitionen'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            @if($definitions->isNotEmpty())
            <x-ui-panel title="Feld-Definitionen" subtitle="{{ $total }} Definition(en) in diesem Team">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Feldtyp</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">In Sektionen</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($definitions as $fd)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.field-definitions.show', $fd)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $fd->title }}</div>
                                @if($fd->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($fd->description, 60) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $fd->fieldType->label }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $fd->section_fields_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $fd->createdByUser?->name ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $fd->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $definitions->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-adjustments-horizontal', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Feld-Definitionen</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle Feld-Definitionen per AI-Assistent.</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Filter" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Suche</h3>
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Feld suchen..." size="sm" />
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
