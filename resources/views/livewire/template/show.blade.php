<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Templates', 'href' => route('qm.templates.index')],
            ['label' => $template->name],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Template Info --}}
            <x-ui-panel>
                <div class="p-4 space-y-4">
                    <div class="d-flex items-start justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            @if($template->description)
                            <p class="text-sm text-[var(--ui-muted)] leading-relaxed">{{ $template->description }}</p>
                            @endif
                        </div>
                        <div class="d-flex items-center gap-2 flex-shrink-0">
                            <x-ui-badge :variant="match($template->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                                {{ ucfirst($template->status) }}
                            </x-ui-badge>
                            <span class="text-xs font-mono text-[var(--ui-muted)]">v{{ $template->version }}</span>
                        </div>
                    </div>
                    <div class="d-flex items-center gap-4 flex-wrap text-xs text-[var(--ui-muted)]">
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-rectangle-group', 'w-3.5 h-3.5') {{ $template->templateSections->count() }} Sektionen</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-clipboard-document-list', 'w-3.5 h-3.5') {{ $template->instances_count }} Instanzen</span>
                        @if($template->getSetting('haccp_enabled'))
                        <x-ui-badge variant="info" size="sm">HACCP</x-ui-badge>
                        @endif
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-cog-6-tooth', 'w-3.5 h-3.5') {{ $template->getSetting('deviation_workflow') }}-Workflow</span>
                        @if($template->getSetting('require_signature'))
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-pencil', 'w-3.5 h-3.5') Unterschrift</span>
                        @endif
                        <span class="text-[var(--ui-border)]">|</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3.5 h-3.5') {{ $template->createdByUser?->name ?? 'Unbekannt' }}</span>
                        <span>{{ $template->created_at?->format('d.m.Y') }}</span>
                    </div>
                </div>
            </x-ui-panel>

            {{-- Wizard Configuration Link --}}
            @if($template->wizardFields->isNotEmpty())
            <x-ui-panel>
                <div class="p-4 d-flex items-center justify-between">
                    <div class="d-flex items-center gap-3">
                        @svg('heroicon-o-sparkles', 'w-5 h-5 text-[var(--ui-muted)]')
                        <div>
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">Wizard konfiguriert</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $template->wizardFields->count() }} Feld(er), {{ $template->wizardRules->count() }} Regel(n)</div>
                        </div>
                    </div>
                    <a href="{{ route('qm.wizard.show', $template) }}" wire:navigate>
                        <x-ui-button variant="secondary" size="sm">Wizard anzeigen</x-ui-button>
                    </a>
                </div>
            </x-ui-panel>
            @endif

            {{-- Sections with Fields --}}
            @if($template->templateSections->isNotEmpty())
                @foreach($template->templateSections as $ts)
                <x-ui-panel
                    title="{{ $ts->section->title }}"
                    subtitle="{{ $ts->section->sectionFields->count() }} Feld(er) &middot; Position {{ $ts->position }} &middot; {{ $ts->is_required ? 'Pflicht' : 'Optional' }}{{ $ts->phase_label ? ' &middot; Phase: ' . $ts->phase_label : '' }}"
                >
                    <div class="px-5 pt-3 d-flex items-center gap-2">
                        @if(($ts->section->category ?? 'standard') === 'addon')
                        <x-ui-badge variant="info" size="sm">Add-On</x-ui-badge>
                        @endif
                        @if($ts->phase_label)
                        <x-ui-badge variant="secondary" size="sm">{{ $ts->phase_label }}</x-ui-badge>
                        @endif
                    </div>
                    @if($ts->section->description)
                        <div class="px-5 pt-2 text-xs text-[var(--ui-muted)]">{{ $ts->section->description }}</div>
                    @endif

                    @if($ts->section->sectionFields->isNotEmpty())
                    <x-ui-table compact="true">
                        <x-ui-table-header>
                            <x-ui-table-header-cell compact="true">Pos.</x-ui-table-header-cell>
                            <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                            <x-ui-table-header-cell compact="true">Feldtyp</x-ui-table-header-cell>
                            <x-ui-table-header-cell compact="true">Pflicht</x-ui-table-header-cell>
                            <x-ui-table-header-cell compact="true">Verhalten</x-ui-table-header-cell>
                        </x-ui-table-header>
                        <x-ui-table-body>
                            @foreach($ts->section->sectionFields as $sf)
                            <x-ui-table-row compact="true">
                                <x-ui-table-cell compact="true">
                                    <span class="text-xs text-[var(--ui-muted)]">{{ $sf->position }}</span>
                                </x-ui-table-cell>
                                <x-ui-table-cell compact="true">
                                    <span class="font-medium text-[var(--ui-secondary)]">{{ $sf->fieldDefinition->title }}</span>
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
                                </x-ui-table-cell>
                            </x-ui-table-row>
                            @endforeach
                        </x-ui-table-body>
                    </x-ui-table>
                    @else
                    <div class="p-4 text-center text-[var(--ui-muted)] text-sm">Keine Felder in dieser Sektion.</div>
                    @endif
                </x-ui-panel>
                @endforeach
            @else
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-rectangle-group', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Sektionen</h3>
                    <p class="text-[var(--ui-muted)]">Fuege Sektionen per AI-Assistent hinzu.</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
