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

            {{-- Hero: Template Info + CTA --}}
            <x-ui-panel>
                <div class="p-6">
                    <div class="d-flex items-start justify-between gap-6">
                        <div class="flex-1 min-w-0">
                            <div class="d-flex items-center gap-3 mb-3">
                                <x-ui-badge :variant="match($template->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                                    {{ ucfirst($template->status) }}
                                </x-ui-badge>
                                <span class="text-xs font-mono text-[var(--ui-muted)]">v{{ $template->version }}</span>
                                @if($template->getSetting('haccp_enabled'))
                                <x-ui-badge variant="info" size="sm">HACCP</x-ui-badge>
                                @endif
                            </div>
                            @if($template->description)
                            <p class="text-sm text-[var(--ui-muted)] leading-relaxed mb-4">{{ $template->description }}</p>
                            @endif
                            <div class="d-flex items-center gap-4 flex-wrap text-xs text-[var(--ui-muted)]">
                                <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3.5 h-3.5') {{ $template->createdByUser?->name ?? 'Unbekannt' }}</span>
                                <span>{{ $template->created_at?->format('d.m.Y') }}</span>
                                <span class="d-flex items-center gap-1">@svg('heroicon-o-cog-6-tooth', 'w-3.5 h-3.5') {{ $template->getSetting('deviation_workflow') }}-Workflow</span>
                                @if($template->getSetting('require_signature'))
                                <span class="d-flex items-center gap-1">@svg('heroicon-o-pencil', 'w-3.5 h-3.5') Unterschrift erforderlich</span>
                                @endif
                            </div>
                        </div>
                        @if($template->wizardFields->isNotEmpty())
                        <div class="flex-shrink-0">
                            <a href="{{ route('qm.wizard.create', $template) }}" wire:navigate>
                                <x-ui-button variant="primary">
                                    Neue Checkliste erstellen
                                </x-ui-button>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </x-ui-panel>

            {{-- Stats Tiles --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <x-ui-panel>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $template->templateSections->count() }}</div>
                        <div class="text-xs text-[var(--ui-muted)] mt-1">Sektionen</div>
                    </div>
                </x-ui-panel>
                <x-ui-panel>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $totalFields }}</div>
                        <div class="text-xs text-[var(--ui-muted)] mt-1">Aufgaben</div>
                    </div>
                </x-ui-panel>
                <x-ui-panel>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ count($phases) }}</div>
                        <div class="text-xs text-[var(--ui-muted)] mt-1">Phasen</div>
                    </div>
                </x-ui-panel>
                <x-ui-panel>
                    <div class="p-4 text-center">
                        <div class="text-2xl font-bold text-[var(--ui-secondary)]">{{ $template->instances_count }}</div>
                        <div class="text-xs text-[var(--ui-muted)] mt-1">Checklisten</div>
                    </div>
                </x-ui-panel>
            </div>

            {{-- Wizard Info --}}
            @if($template->wizardFields->isNotEmpty())
            <x-ui-panel>
                <div class="p-4 d-flex items-center justify-between">
                    <div class="d-flex items-center gap-3">
                        @svg('heroicon-o-sparkles', 'w-5 h-5 text-[var(--ui-primary)]')
                        <div>
                            <div class="text-sm font-medium text-[var(--ui-secondary)]">Wizard-Konfiguration</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $template->wizardFields->count() }} Feld(er) steuern {{ $template->wizardRules->count() }} Regel(n) zur dynamischen Sektionsaktivierung</div>
                        </div>
                    </div>
                    <a href="{{ route('qm.wizard.show', $template) }}" wire:navigate>
                        <x-ui-button variant="secondary" size="sm">Details anzeigen</x-ui-button>
                    </a>
                </div>
            </x-ui-panel>
            @endif

            {{-- Sections Overview --}}
            @if($template->templateSections->isNotEmpty())

                {{-- Phase Tabs --}}
                @if(count($phases) > 1)
                <div class="d-flex items-center gap-1 flex-wrap">
                    @foreach($phases as $phaseKey => $phase)
                    <button wire:click="setPhase('{{ $phaseKey }}')"
                        class="px-3 py-1.5 rounded-md text-xs transition-colors d-flex items-center gap-1.5 {{ $activePhase === $phaseKey ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                        {{ $phaseKey }}
                        <span class="font-mono">{{ count($phase['sections']) }}</span>
                    </button>
                    @endforeach
                </div>
                @endif

                {{-- Section Cards --}}
                @php $activePhaseData = $phases[$activePhase] ?? reset($phases); @endphp
                <x-ui-panel>
                    <div class="divide-y divide-[var(--ui-border)]/50">
                        @foreach($activePhaseData['sections'] as $ts)
                        <div>
                            {{-- Section Header (clickable) --}}
                            <button
                                wire:click="toggleSection({{ $ts->id }})"
                                class="w-full px-5 py-3 d-flex items-center gap-3 text-left hover:bg-[var(--ui-muted-5)]/50 transition-colors"
                            >
                                {{-- Expand icon --}}
                                <span class="flex-shrink-0 text-[var(--ui-muted)] transition-transform {{ $expandedSection === $ts->id ? 'rotate-90' : '' }}">
                                    @svg('heroicon-s-chevron-right', 'w-4 h-4')
                                </span>

                                {{-- Position --}}
                                <span class="flex-shrink-0 w-7 h-7 rounded-full bg-[var(--ui-muted-5)] d-flex items-center justify-center text-xs font-mono font-medium text-[var(--ui-muted)]">
                                    {{ $ts->position }}
                                </span>

                                {{-- Title + meta --}}
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex items-center gap-2">
                                        <span class="text-sm font-medium text-[var(--ui-secondary)] truncate">{{ $ts->section->title }}</span>
                                        @if($ts->is_required)
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0" title="Pflichtsektion"></span>
                                        @endif
                                    </div>
                                    @if($ts->section->description)
                                    <p class="text-xs text-[var(--ui-muted)] truncate mt-0.5">{{ $ts->section->description }}</p>
                                    @endif
                                </div>

                                {{-- Badges --}}
                                <div class="d-flex items-center gap-2 flex-shrink-0">
                                    @if(($ts->section->category ?? 'standard') === 'addon')
                                    <x-ui-badge variant="info" size="sm">Add-On</x-ui-badge>
                                    @endif
                                    <span class="text-xs text-[var(--ui-muted)] font-mono">{{ $ts->section->sectionFields->count() }} Felder</span>
                                </div>
                            </button>

                            {{-- Expanded: Fields --}}
                            @if($expandedSection === $ts->id && $ts->section->sectionFields->isNotEmpty())
                            <div class="px-5 pb-4 ml-14">
                                <div class="rounded-lg border border-[var(--ui-border)]/50 overflow-hidden">
                                    @foreach($ts->section->sectionFields as $sf)
                                    <div class="d-flex items-center gap-3 px-4 py-2 {{ !$loop->last ? 'border-b border-[var(--ui-border)]/30' : '' }} {{ $loop->even ? 'bg-[var(--ui-muted-5)]/30' : '' }}">
                                        <span class="text-xs text-[var(--ui-muted)] font-mono w-5 text-right flex-shrink-0">{{ $sf->position }}</span>
                                        <span class="text-sm text-[var(--ui-secondary)] flex-grow-1">{{ $sf->fieldDefinition->title }}</span>
                                        <x-ui-badge variant="secondary" size="sm">{{ $sf->fieldDefinition->fieldType->label }}</x-ui-badge>
                                        @if($sf->is_required)
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-400 flex-shrink-0" title="Pflicht"></span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </x-ui-panel>

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
