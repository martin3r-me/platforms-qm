<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Sektionen', 'href' => route('qm.sections.index')],
            ['label' => $section->title],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Fields --}}
            <x-ui-panel title="Felder" subtitle="{{ $section->sectionFields->count() }} Feld(er) in dieser Sektion">
                @if($section->sectionFields->isNotEmpty())
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Pos.</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Feldtyp</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Pflicht</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Verhalten</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($section->sectionFields as $sf)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.field-definitions.show', $sf->fieldDefinition)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $sf->position }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $sf->fieldDefinition->title }}</div>
                                @if($sf->fieldDefinition->description)
                                <div class="text-xs text-[var(--ui-muted)] truncate max-w-xs mt-0.5">{{ Str::limit($sf->fieldDefinition->description, 50) }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $sf->fieldDefinition->fieldType->label }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$sf->is_required ? 'danger' : 'secondary'" size="sm">
                                    {{ $sf->is_required ? 'Pflicht' : 'Optional' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $sf->behavior_rule }}</span>
                                @if($sf->behavior_rule !== 'always' && $sf->behavior_config)
                                <div class="text-[10px] text-[var(--ui-muted)] mt-0.5">{{ json_encode($sf->behavior_config) }}</div>
                                @endif
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Noch keine Felder in dieser Sektion.</div>
                @endif
            </x-ui-panel>

            {{-- Used in Templates --}}
            @if($section->templates->isNotEmpty())
            <x-ui-panel title="Verwendet in Templates" subtitle="{{ $section->templates->count() }} Template(s)">
                <div class="space-y-2 p-4">
                    @foreach($section->templates as $template)
                    <a href="{{ route('qm.templates.show', $template) }}" class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg hover:bg-[var(--ui-muted-5)]/80 transition-colors" wire:navigate>
                        <div>
                            <div class="font-medium text-sm text-[var(--ui-secondary)]">{{ $template->name }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $template->status }}</div>
                        </div>
                        <x-ui-badge :variant="match($template->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }" size="sm">
                            {{ ucfirst($template->status) }}
                        </x-ui-badge>
                    </a>
                    @endforeach
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Details" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Info</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Felder</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $section->sectionFields->count() }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">In Templates</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $section->templates->count() }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-user', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Erstellt von</span>
                            </div>
                            <span class="text-xs text-[var(--ui-secondary)]">{{ $section->createdByUser?->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-calendar', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            </div>
                            <span class="text-xs text-[var(--ui-secondary)]">{{ $section->created_at?->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>

                @if($section->description)
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Beschreibung</h3>
                    <p class="text-xs text-[var(--ui-muted)] leading-relaxed">{{ $section->description }}</p>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
