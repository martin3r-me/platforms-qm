<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Sektionen'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            @if($sections->isNotEmpty())
            <x-ui-panel title="Sektionen" subtitle="{{ $total }} Sektion(en) in diesem Team">
                <div class="px-4 pt-3 pb-2">
                    <x-ui-input-text wire:model.live.debounce.300ms="search" placeholder="Sektion suchen..." size="sm" />
                </div>
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Kategorie</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Felder</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">In Templates</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($sections as $section)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.sections.show', $section)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $section->title }}</div>
                                @if($section->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($section->description, 60) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="($section->category ?? 'standard') === 'addon' ? 'info' : 'secondary'" size="sm">
                                    {{ ($section->category ?? 'standard') === 'addon' ? 'Add-On' : 'Standard' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $section->section_fields_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $section->templates_count }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $section->createdByUser?->name ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $section->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                <div class="p-4">{{ $sections->links() }}</div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-rectangle-group', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Sektionen</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle Sektionen per AI-Assistent um Felder logisch zu gruppieren.</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
