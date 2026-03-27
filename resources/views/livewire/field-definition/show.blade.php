<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Feld-Definitionen', 'href' => route('qm.field-definitions.index')],
            ['label' => $fieldDefinition->title],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            <x-ui-panel title="{{ $fieldDefinition->title }}" subtitle="Feldtyp: {{ $fieldDefinition->fieldType->label }}">
                <div class="p-5 space-y-4">
                    @if($fieldDefinition->description)
                        <p class="text-sm text-[var(--ui-muted)]">{{ $fieldDefinition->description }}</p>
                    @endif

                    @if($fieldDefinition->config)
                        <div>
                            <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Konfiguration</h4>
                            <pre class="text-xs bg-[var(--ui-muted-5)] rounded-lg p-3 overflow-x-auto text-[var(--ui-muted)]">{{ json_encode($fieldDefinition->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    @if($fieldDefinition->validation_rules)
                        <div>
                            <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Validierungsregeln</h4>
                            <pre class="text-xs bg-[var(--ui-muted-5)] rounded-lg p-3 overflow-x-auto text-[var(--ui-muted)]">{{ json_encode($fieldDefinition->validation_rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif
                </div>
            </x-ui-panel>

            {{-- Used in Sections --}}
            @if($fieldDefinition->sectionFields->isNotEmpty())
            <x-ui-panel title="Verwendet in Sektionen" subtitle="{{ $fieldDefinition->sectionFields->count() }} Sektion(en)">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Sektion</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Pflicht</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Verhalten</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Position</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($fieldDefinition->sectionFields as $sf)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.sections.show', $sf->section)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $sf->section->title }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$sf->is_required ? 'danger' : 'secondary'" size="sm">
                                    {{ $sf->is_required ? 'Pflicht' : 'Optional' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $sf->behavior_rule }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $sf->position }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
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
                            <span class="text-xs text-[var(--ui-muted)]">Feldtyp</span>
                            <x-ui-badge variant="secondary" size="sm">{{ $fieldDefinition->fieldType->label }}</x-ui-badge>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">In Sektionen</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $fieldDefinition->sectionFields->count() }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-user', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Erstellt von</span>
                            </div>
                            <span class="text-xs text-[var(--ui-secondary)]">{{ $fieldDefinition->createdByUser?->name ?? '-' }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-calendar', 'w-3.5 h-3.5 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Erstellt</span>
                            </div>
                            <span class="text-xs text-[var(--ui-secondary)]">{{ $fieldDefinition->created_at?->format('d.m.Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
