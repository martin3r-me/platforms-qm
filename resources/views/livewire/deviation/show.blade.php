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
            {{-- Deviation Info --}}
            <x-ui-panel>
                <div class="p-4">
                    <div class="d-flex items-center gap-3 flex-wrap text-xs text-[var(--ui-muted)]">
                        <x-ui-badge :variant="match($deviation->severity ?? 'low') {
                            'critical' => 'danger', 'high' => 'danger', 'medium' => 'warning', default => 'secondary',
                        }">
                            {{ ucfirst($deviation->severity ?? 'low') }}
                        </x-ui-badge>
                        <x-ui-badge :variant="match($deviation->status ?? 'open') {
                            'resolved' => 'success', 'verified' => 'success', 'acknowledged' => 'info', default => 'warning',
                        }">
                            {{ match($deviation->status ?? 'open') {
                                'open' => 'Offen', 'acknowledged' => 'Bestaetigt', 'resolved' => 'Behoben', 'verified' => 'Verifiziert', default => ucfirst($deviation->status),
                            } }}
                        </x-ui-badge>
                        @if($deviation->escalation_level)
                        <x-ui-badge variant="danger" size="sm">Eskalation Level {{ $deviation->escalation_level }}</x-ui-badge>
                        @endif
                        @if($deviation->instance)
                        <a href="{{ route('qm.instances.show', $deviation->instance) }}" wire:navigate class="d-flex items-center gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                            @svg('heroicon-o-clipboard-document-list', 'w-3.5 h-3.5') {{ $deviation->instance->title }}
                        </a>
                        @endif
                        @if($deviation->response?->fieldDefinition)
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-adjustments-horizontal', 'w-3.5 h-3.5') {{ $deviation->response->fieldDefinition->title }}</span>
                        @endif
                        <span class="text-[var(--ui-border)]">|</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3.5 h-3.5') {{ $deviation->createdByUser?->name ?? 'Unbekannt' }}</span>
                        <span>{{ $deviation->created_at?->format('d.m.Y H:i') }}</span>
                    </div>
                </div>
            </x-ui-panel>

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
</x-ui-page>
