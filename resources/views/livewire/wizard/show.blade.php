<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Templates', 'href' => route('qm.templates.index')],
            ['label' => $template->name, 'href' => route('qm.templates.show', $template)],
            ['label' => 'Wizard'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Wizard Fields --}}
            <x-ui-panel title="Wizard-Felder" subtitle="{{ count($config['fields']) }} Feld(er) - werden beim Erstellen einer Checkliste abgefragt">
                @if(!empty($config['fields']))
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Pos.</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Label</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Tech. Name</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Pflicht</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Datenquelle</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($config['fields'] as $field)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $field['sort_order'] }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $field['label'] }}</div>
                                @if($field['description'])
                                <div class="text-xs text-[var(--ui-muted)] mt-0.5">{{ $field['description'] }}</div>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs font-mono text-[var(--ui-muted)]">{{ $field['technical_name'] }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $field['input_type'] }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$field['is_required'] ? 'danger' : 'secondary'" size="sm">
                                    {{ $field['is_required'] ? 'Pflicht' : 'Optional' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if(isset($field['lookup_table']))
                                <span class="text-xs text-[var(--ui-secondary)]">{{ $field['lookup_table']['name'] }} ({{ count($field['lookup_table']['entries']) }})</span>
                                @else
                                <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Noch keine Wizard-Felder konfiguriert.</div>
                @endif
            </x-ui-panel>

            {{-- Wizard Rules --}}
            <x-ui-panel title="Aktivierungsregeln" subtitle="{{ count($config['rules']) }} Regel(n) - bestimmen welche Sections aktiviert werden">
                @if(!empty($config['rules']))
                @foreach($config['rules'] as $rule)
                <div class="p-4 border-b border-[var(--ui-border)]/40 last:border-b-0">
                    <div class="d-flex items-center justify-between mb-2">
                        <div class="d-flex items-center gap-2">
                            <span class="font-medium text-sm text-[var(--ui-secondary)]">{{ $rule['name'] }}</span>
                            <x-ui-badge :variant="$rule['is_active'] ? 'success' : 'secondary'" size="sm">
                                {{ $rule['is_active'] ? 'Aktiv' : 'Inaktiv' }}
                            </x-ui-badge>
                        </div>
                    </div>
                    <div class="text-xs text-[var(--ui-muted)] mb-2">
                        <span class="font-mono">{{ $rule['condition_field'] }}</span>
                        <span class="mx-1">{{ $rule['condition_operator'] }}</span>
                        <span class="font-mono">{{ json_encode($rule['condition_value']) }}</span>
                    </div>
                    @if(!empty($rule['sections']))
                    <div class="d-flex flex-wrap gap-1.5 mt-2">
                        @foreach($rule['sections'] as $rs)
                        <x-ui-badge :variant="$rs['effect'] === 'show' ? 'success' : 'danger'" size="sm">
                            {{ $rs['effect'] === 'show' ? '+' : '-' }} {{ $rs['section_title'] }}
                        </x-ui-badge>
                        @endforeach
                    </div>
                    @else
                    <div class="text-xs text-[var(--ui-muted)] italic">Keine Sections zugewiesen</div>
                    @endif
                </div>
                @endforeach
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Noch keine Regeln konfiguriert.</div>
                @endif
            </x-ui-panel>

            {{-- Sections Matrix --}}
            <x-ui-panel title="Template-Sections" subtitle="Uebersicht aller Sections dieses Templates">
                @if(!empty($config['template_sections']))
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Pos.</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Section</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Kategorie</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Pflicht</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Phase</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($config['template_sections'] as $ts)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $ts['position'] }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $ts['section_title'] }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$ts['section_category'] === 'addon' ? 'info' : 'secondary'" size="sm">
                                    {{ $ts['section_category'] === 'addon' ? 'Add-On' : 'Standard' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="$ts['is_required'] ? 'danger' : 'secondary'" size="sm">
                                    {{ $ts['is_required'] ? 'Pflicht' : 'Optional' }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-xs text-[var(--ui-muted)]">{{ $ts['phase_label'] ?? '-' }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Keine Sections im Template.</div>
                @endif
            </x-ui-panel>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Wizard Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Template</h3>
                    <a href="{{ route('qm.templates.show', $template) }}" class="text-sm font-medium text-[var(--ui-secondary)] hover:underline" wire:navigate>
                        {{ $template->name }}
                    </a>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Statistik</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Wizard-Felder</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ count($config['fields']) }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Regeln</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ count($config['rules']) }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Sections</span>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ count($config['template_sections']) }}</span>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Konzept</h3>
                    <div class="space-y-2">
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <p class="text-[11px] text-[var(--ui-muted)]">Der Wizard fragt beim Erstellen einer Checkliste die konfigurierten Felder ab. Basierend auf den Antworten werden per Aktivierungsregeln nur die relevanten Sections in die Instanz uebernommen.</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
