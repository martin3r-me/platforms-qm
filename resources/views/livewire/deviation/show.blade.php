<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Abweichungen', 'href' => route('qm.deviations.index')],
            ['label' => $deviation->title],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Workflow Timeline --}}
            <x-ui-panel title="Workflow" subtitle="{{ $deviation->workflow_type === 'full' ? 'HACCP (vollstaendig)' : 'Einfach' }}">
                <div class="p-5">
                    <div class="d-flex items-center gap-3">
                        @php
                            $steps = $deviation->workflow_type === 'full'
                                ? ['open' => 'Offen', 'acknowledged' => 'Bestaetigt', 'resolved' => 'Behoben', 'verified' => 'Verifiziert']
                                : ['open' => 'Offen', 'resolved' => 'Behoben'];
                            $statusOrder = array_keys($steps);
                            $currentIndex = array_search($deviation->status, $statusOrder);
                        @endphp
                        @foreach($steps as $key => $label)
                            @php
                                $stepIndex = array_search($key, $statusOrder);
                                $isActive = $key === $deviation->status;
                                $isDone = $stepIndex < $currentIndex;
                            @endphp
                            <div class="d-flex items-center gap-2">
                                <div class="w-8 h-8 rounded-full d-flex items-center justify-center text-xs font-bold
                                    {{ $isDone ? 'bg-green-500 text-white' : ($isActive ? 'bg-[var(--ui-primary)] text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                                    @if($isDone)
                                        @svg('heroicon-s-check', 'w-4 h-4')
                                    @else
                                        {{ $stepIndex + 1 }}
                                    @endif
                                </div>
                                <span class="text-xs {{ $isActive ? 'font-bold text-[var(--ui-secondary)]' : 'text-[var(--ui-muted)]' }}">{{ $label }}</span>
                            </div>
                            @if(!$loop->last)
                            <div class="flex-grow-1 h-px {{ $isDone ? 'bg-green-500' : 'bg-[var(--ui-border)]' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </x-ui-panel>

            {{-- Details --}}
            <x-ui-panel title="Details">
                <div class="p-5 space-y-4">
                    @if($deviation->description)
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Beschreibung</h4>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $deviation->description }}</p>
                    </div>
                    @endif

                    @if($deviation->corrective_action)
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Sofortmassnahme</h4>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $deviation->corrective_action }}</p>
                    </div>
                    @endif

                    @if($deviation->root_cause)
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Ursachenanalyse</h4>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $deviation->root_cause }}</p>
                    </div>
                    @endif

                    @if($deviation->preventive_action)
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Praeventivmassnahme</h4>
                        <p class="text-sm text-[var(--ui-muted)]">{{ $deviation->preventive_action }}</p>
                    </div>
                    @endif

                    @if(!$deviation->description && !$deviation->corrective_action && !$deviation->root_cause && !$deviation->preventive_action)
                    <p class="text-sm text-[var(--ui-muted)]">Noch keine Details erfasst. Nutze den AI-Assistenten um die Abweichung zu bearbeiten.</p>
                    @endif
                </div>
            </x-ui-panel>
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Schwere</h3>
                    <x-ui-badge :variant="match($deviation->severity ?? 'low') {
                        'critical' => 'danger',
                        'high' => 'danger',
                        'medium' => 'warning',
                        default => 'secondary',
                    }">
                        {{ ucfirst($deviation->severity ?? 'low') }}
                    </x-ui-badge>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        @if($deviation->instance)
                        <a href="{{ route('qm.instances.show', $deviation->instance) }}" class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted-5)]/80 transition-colors" wire:navigate>
                            <span class="text-xs text-[var(--ui-muted)]">Checkliste</span>
                            <span class="text-xs font-medium text-[var(--ui-secondary)]">{{ $deviation->instance->title }}</span>
                        </a>
                        @endif

                        @if($deviation->response?->fieldDefinition)
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Feld</span>
                            <span class="text-xs text-[var(--ui-secondary)]">{{ $deviation->response->fieldDefinition->title }}</span>
                        </div>
                        @endif

                        @if($deviation->escalation_level)
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <span class="text-xs text-[var(--ui-muted)]">Eskalation</span>
                            <x-ui-badge variant="danger" size="sm">Level {{ $deviation->escalation_level }}</x-ui-badge>
                        </div>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Zeitverlauf</h3>
                    <div class="space-y-2 text-xs">
                        <div class="d-flex items-center gap-2 text-[var(--ui-muted)]">
                            @svg('heroicon-o-plus-circle', 'w-3.5 h-3.5')
                            <span>Erstellt: {{ $deviation->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="d-flex items-center gap-2 text-[var(--ui-muted)]">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            <span>{{ $deviation->createdByUser?->name ?? 'Unbekannt' }}</span>
                        </div>

                        @if($deviation->acknowledged_at)
                        <div class="d-flex items-center gap-2 text-blue-500 mt-2">
                            @svg('heroicon-o-eye', 'w-3.5 h-3.5')
                            <span>Bestaetigt: {{ $deviation->acknowledged_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="d-flex items-center gap-2 text-[var(--ui-muted)]">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            <span>{{ $deviation->acknowledgedByUser?->name }}</span>
                        </div>
                        @endif

                        @if($deviation->escalated_at)
                        <div class="d-flex items-center gap-2 text-red-500 mt-2">
                            @svg('heroicon-o-arrow-trending-up', 'w-3.5 h-3.5')
                            <span>Eskaliert: {{ $deviation->escalated_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @endif

                        @if($deviation->resolved_at)
                        <div class="d-flex items-center gap-2 text-green-500 mt-2">
                            @svg('heroicon-o-check-circle', 'w-3.5 h-3.5')
                            <span>Behoben: {{ $deviation->resolved_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="d-flex items-center gap-2 text-[var(--ui-muted)]">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            <span>{{ $deviation->resolvedByUser?->name }}</span>
                        </div>
                        @endif

                        @if($deviation->verified_at)
                        <div class="d-flex items-center gap-2 text-green-600 mt-2">
                            @svg('heroicon-o-check-badge', 'w-3.5 h-3.5')
                            <span>Verifiziert: {{ $deviation->verified_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="d-flex items-center gap-2 text-[var(--ui-muted)]">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            <span>{{ $deviation->verifiedByUser?->name }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
