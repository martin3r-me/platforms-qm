<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Checklisten', 'href' => route('qm.instances.index')],
            ['label' => $instance->title],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Hero: Instance Info + Progress --}}
            <x-ui-panel>
                <div class="p-5">
                    <div class="d-flex items-start justify-between gap-4 mb-4">
                        <div class="d-flex items-center gap-3 flex-wrap">
                            <x-ui-badge :variant="match($instance->status) {
                                'completed' => 'success',
                                'in_progress' => 'info',
                                'cancelled' => 'danger',
                                default => 'warning',
                            }">
                                {{ match($instance->status) {
                                    'open' => 'Offen',
                                    'in_progress' => 'In Bearbeitung',
                                    'completed' => 'Abgeschlossen',
                                    'cancelled' => 'Abgebrochen',
                                    default => ucfirst($instance->status),
                                } }}
                            </x-ui-badge>
                            @if($instance->template)
                            <a href="{{ route('qm.templates.show', $instance->template) }}" wire:navigate class="text-xs d-flex items-center gap-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors">
                                @svg('heroicon-o-document-duplicate', 'w-3.5 h-3.5') {{ $instance->template->name }}
                            </a>
                            @endif
                        </div>
                        @if($instance->score !== null)
                        <span class="text-2xl font-bold font-mono {{ $instance->score >= 80 ? 'text-green-600' : ($instance->score >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                            {{ number_format($instance->score, 0) }}%
                        </span>
                        @endif
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mb-3">
                        <div class="w-full bg-[var(--ui-muted-5)] rounded-full h-2.5">
                            <div class="h-2.5 rounded-full transition-all {{ ($stats['completion_percent'] ?? 0) >= 80 ? 'bg-green-500' : (($stats['completion_percent'] ?? 0) >= 50 ? 'bg-yellow-500' : 'bg-[var(--ui-primary)]') }}"
                                 style="width: {{ min(100, $stats['completion_percent'] ?? 0) }}%"></div>
                        </div>
                    </div>

                    <div class="d-flex items-center gap-4 flex-wrap text-xs text-[var(--ui-muted)]">
                        <span class="font-medium">{{ $stats['responses_count'] ?? 0 }} / {{ $stats['total_fields'] ?? 0 }} Felder</span>
                        <span>{{ $stats['required_fields'] ?? 0 }} Pflicht</span>
                        @if(($stats['deviations_count'] ?? 0) > 0)
                        <span class="text-red-500 font-medium">{{ $stats['deviations_count'] }} Abweichung(en)</span>
                        @endif
                        <span class="text-[var(--ui-border)]">|</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3 h-3') {{ $instance->createdByUser?->name ?? 'Unbekannt' }}</span>
                        <span>{{ $instance->created_at?->format('d.m.Y') }}</span>
                        @if($instance->due_at)
                        <span class="d-flex items-center gap-1 {{ $instance->due_at->isPast() && !in_array($instance->status, ['completed', 'cancelled']) ? 'text-red-500 font-bold' : '' }}">
                            @svg('heroicon-o-clock', 'w-3 h-3') {{ $instance->due_at->format('d.m.Y H:i') }}
                        </span>
                        @endif
                        @if($instance->completed_at)
                        <span class="d-flex items-center gap-1 text-green-600">@svg('heroicon-o-check-circle', 'w-3 h-3') {{ $instance->completed_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>
                    @if($instance->description)
                    <p class="text-sm text-[var(--ui-muted)] leading-relaxed mt-3">{{ $instance->description }}</p>
                    @endif
                </div>
            </x-ui-panel>

            {{-- Phase Tabs --}}
            @if(count($phases) > 1)
            <div class="d-flex items-center gap-1 flex-wrap">
                @foreach($phases as $phaseKey => $phase)
                <button wire:click="setPhase('{{ $phaseKey }}')"
                    class="px-3 py-1.5 rounded-md text-xs transition-colors d-flex items-center gap-1.5 {{ $activePhase === $phaseKey ? 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)] font-medium' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]' }}">
                    {{ $phaseKey }}
                    <span class="font-mono {{ $phase['answered_fields'] === $phase['total_fields'] && $phase['total_fields'] > 0 ? 'text-green-600' : '' }}">
                        {{ $phase['answered_fields'] }}/{{ $phase['total_fields'] }}
                    </span>
                </button>
                @endforeach
            </div>
            @endif

            {{-- Checklist Sections --}}
            @php $activePhaseData = $phases[$activePhase] ?? reset($phases); @endphp
            @if($activePhaseData)
            <x-ui-panel>
                <div class="divide-y divide-[var(--ui-border)]/40">
                    @foreach($activePhaseData['sections'] as $sIdx => $section)
                    <div class="{{ $sIdx > 0 ? 'pt-1' : '' }}">
                        {{-- Section Header --}}
                        <div class="px-5 pt-4 pb-2 d-flex items-center justify-between">
                            <div class="d-flex items-center gap-2.5">
                                <span class="w-6 h-6 rounded-full d-flex items-center justify-center text-[10px] font-bold {{ $section['answered'] === $section['total'] && $section['total'] > 0 ? 'bg-green-500 text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]' }}">
                                    @if($section['answered'] === $section['total'] && $section['total'] > 0)
                                        @svg('heroicon-s-check', 'w-3.5 h-3.5')
                                    @else
                                        {{ $sIdx + 1 }}
                                    @endif
                                </span>
                                <div>
                                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $section['title'] }}</h3>
                                    @if($section['description'])
                                    <p class="text-[11px] text-[var(--ui-muted)] mt-0.5">{{ $section['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                            <span class="text-xs font-mono {{ $section['answered'] === $section['total'] && $section['total'] > 0 ? 'text-green-600 font-bold' : 'text-[var(--ui-muted)]' }}">
                                {{ $section['answered'] }}/{{ $section['total'] }}
                            </span>
                        </div>

                        {{-- Fields --}}
                        <div class="px-5 pb-4 space-y-0.5">
                            @foreach($section['fields'] as $field)
                            <div
                                @if(!in_array($instance->status, ['completed', 'cancelled']))
                                wire:click="toggleField({{ $field['field_definition_id'] }}, {{ $section['section_id'] }})"
                                @endif
                                class="d-flex items-center gap-3 py-2 px-3 rounded-lg transition-colors {{ !in_array($instance->status, ['completed', 'cancelled']) ? 'cursor-pointer hover:bg-[var(--ui-muted-5)]/80' : '' }} {{ $field['is_checked'] ? 'bg-green-500/5' : '' }}"
                            >
                                {{-- Checkbox --}}
                                <div class="flex-shrink-0 w-5 h-5 rounded border-2 d-flex items-center justify-center transition-all {{ $field['is_checked'] ? 'bg-green-500 border-green-500 shadow-sm' : 'border-[var(--ui-border)]' }}">
                                    @if($field['is_checked'])
                                    @svg('heroicon-s-check', 'w-3.5 h-3.5 text-white')
                                    @endif
                                </div>

                                {{-- Field title --}}
                                <div class="flex-grow-1 min-w-0">
                                    <span class="text-sm {{ $field['is_checked'] ? 'text-[var(--ui-muted)] line-through' : 'text-[var(--ui-secondary)]' }}">
                                        {{ $field['title'] }}
                                    </span>
                                </div>

                                {{-- Required marker --}}
                                @if($field['is_required'] && !$field['is_checked'])
                                <span class="w-2 h-2 rounded-full bg-red-400 flex-shrink-0" title="Pflichtfeld"></span>
                                @endif

                                {{-- Response timestamp --}}
                                @if($field['response']?->responded_at)
                                <span class="text-[10px] text-[var(--ui-muted)] flex-shrink-0">
                                    {{ $field['response']->responded_at->format('d.m. H:i') }}
                                </span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-ui-panel>
            @else
            <x-ui-panel>
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">
                    Kein Snapshot vorhanden. Diese Instanz hat keine Template-Struktur.
                </div>
            </x-ui-panel>
            @endif

            {{-- Complete Button --}}
            @if(!in_array($instance->status, ['completed', 'cancelled']))
            <div class="d-flex justify-end">
                <x-ui-button
                    wire:click="completeInstance"
                    wire:confirm="Checkliste wirklich abschliessen? Dies kann nicht rueckgaengig gemacht werden."
                    variant="{{ $allRequiredAnswered ? 'primary' : 'secondary' }}"
                    :disabled="!$allRequiredAnswered"
                >
                    @svg('heroicon-o-check-circle', 'w-4 h-4 inline mr-1')
                    Checkliste abschliessen
                </x-ui-button>
            </div>
            @endif

            {{-- Deviations --}}
            @if($instance->deviations->isNotEmpty())
            <x-ui-panel title="Abweichungen" subtitle="{{ $instance->deviations->count() }} Abweichung(en)">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Schwere</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($instance->deviations as $deviation)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $deviation->title }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($deviation->severity ?? 'low') { 'critical' => 'danger', 'high' => 'danger', 'medium' => 'warning', default => 'secondary' }" size="sm">
                                    {{ ucfirst($deviation->severity ?? 'low') }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($deviation->status ?? 'open') { 'resolved' => 'success', 'verified' => 'success', default => 'warning' }" size="sm">
                                    {{ ucfirst($deviation->status ?? 'open') }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $deviation->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>
