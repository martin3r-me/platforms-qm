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

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Template Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Status</h3>
                    <x-ui-badge :variant="match($template->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                        {{ ucfirst($template->status) }}
                    </x-ui-badge>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Version</span>
                            <span class="text-xs font-mono font-bold text-[var(--ui-secondary)]">{{ $template->version }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Sektionen</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $template->templateSections->count() }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Instanzen</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $template->instances_count }}</span>
                        </div>
                    </div>
                </div>

                {{-- Settings --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Settings</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                            <span class="text-[11px] text-[var(--ui-muted)]">HACCP</span>
                            <x-ui-badge :variant="$template->getSetting('haccp_enabled') ? 'success' : 'secondary'" size="sm">
                                {{ $template->getSetting('haccp_enabled') ? 'Aktiv' : 'Aus' }}
                            </x-ui-badge>
                        </div>
                        <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                            <span class="text-[11px] text-[var(--ui-muted)]">Workflow</span>
                            <span class="text-[11px] font-mono text-[var(--ui-secondary)]">{{ $template->getSetting('deviation_workflow') }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                            <span class="text-[11px] text-[var(--ui-muted)]">Unterschrift</span>
                            <x-ui-badge :variant="$template->getSetting('require_signature') ? 'success' : 'secondary'" size="sm">
                                {{ $template->getSetting('require_signature') ? 'Ja' : 'Nein' }}
                            </x-ui-badge>
                        </div>
                        <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                            <span class="text-[11px] text-[var(--ui-muted)]">Eskalation</span>
                            <x-ui-badge :variant="$template->getSetting('escalation_enabled') ? 'success' : 'secondary'" size="sm">
                                {{ $template->getSetting('escalation_enabled') ? 'Aktiv' : 'Aus' }}
                            </x-ui-badge>
                        </div>
                    </div>
                </div>

                <div class="space-y-2 text-xs text-[var(--ui-muted)]">
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-user', 'w-3.5 h-3.5')
                        {{ $template->createdByUser?->name ?? 'Unbekannt' }}
                    </div>
                    <div class="d-flex items-center gap-2">
                        @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                        {{ $template->created_at?->format('d.m.Y H:i') }}
                    </div>
                </div>

                @if($template->description)
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Beschreibung</h3>
                    <p class="text-xs text-[var(--ui-muted)] leading-relaxed">{{ $template->description }}</p>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
