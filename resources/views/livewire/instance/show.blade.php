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
        <div class="space-y-4">

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

            @if(count($sections) > 0)
            {{-- Sticky Section Tab Bar --}}
            <div class="sticky top-0 z-10 -mx-1 px-1 py-2 bg-[var(--ui-bg)]">
                <div class="overflow-x-auto scrollbar-none">
                    <div class="d-flex items-center gap-1 min-w-max">
                        @foreach($sections as $sIdx => $section)
                        <button wire:click="setSection({{ $sIdx }})"
                            class="px-3 py-2 rounded-lg text-xs transition-all d-flex items-center gap-2 whitespace-nowrap {{ $activeSection === $sIdx ? 'bg-[var(--ui-primary)] text-white shadow-sm' : ($section['answered'] === $section['total'] && $section['total'] > 0 ? 'bg-green-500/10 text-green-700 hover:bg-green-500/20' : 'text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)]') }}">
                            {{-- Section number or check --}}
                            @if($section['answered'] === $section['total'] && $section['total'] > 0 && $activeSection !== $sIdx)
                                @svg('heroicon-s-check-circle', 'w-3.5 h-3.5')
                            @else
                                <span class="w-5 h-5 rounded-full d-flex items-center justify-center text-[10px] font-bold {{ $activeSection === $sIdx ? 'bg-white/20' : 'bg-[var(--ui-muted-5)]' }}">{{ $sIdx + 1 }}</span>
                            @endif
                            <span class="font-medium">{{ \Illuminate\Support\Str::limit($section['title'], 20) }}</span>
                            <span class="font-mono text-[10px] {{ $activeSection === $sIdx ? 'text-white/70' : '' }}">{{ $section['answered'] }}/{{ $section['total'] }}</span>
                        </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Active Section Content --}}
            @php $currentSection = $sections[$activeSection] ?? null; @endphp
            @if($currentSection)
            <x-ui-panel>
                {{-- Section Header --}}
                <div class="px-5 pt-5 pb-3 border-b border-[var(--ui-border)]/30">
                    <div class="d-flex items-center justify-between">
                        <div class="d-flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full d-flex items-center justify-center text-xs font-bold {{ $currentSection['answered'] === $currentSection['total'] && $currentSection['total'] > 0 ? 'bg-green-500 text-white' : 'bg-[var(--ui-primary)]/10 text-[var(--ui-primary)]' }}">
                                @if($currentSection['answered'] === $currentSection['total'] && $currentSection['total'] > 0)
                                    @svg('heroicon-s-check', 'w-4 h-4')
                                @else
                                    {{ $activeSection + 1 }}
                                @endif
                            </span>
                            <div>
                                <h3 class="text-base font-semibold text-[var(--ui-secondary)]">{{ $currentSection['title'] }}</h3>
                                @if($currentSection['description'])
                                <p class="text-xs text-[var(--ui-muted)] mt-0.5">{{ $currentSection['description'] }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="d-flex items-center gap-2">
                            <span class="text-xs text-[var(--ui-muted)]">{{ $currentSection['phase_label'] }}</span>
                            <span class="text-sm font-mono font-bold {{ $currentSection['answered'] === $currentSection['total'] && $currentSection['total'] > 0 ? 'text-green-600' : 'text-[var(--ui-muted)]' }}">
                                {{ $currentSection['answered'] }}/{{ $currentSection['total'] }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Fields / Checklist Items --}}
                <div class="divide-y divide-[var(--ui-border)]/20">
                    @foreach($currentSection['fields'] as $field)
                    <div
                        @if(!in_array($instance->status, ['completed', 'cancelled']))
                        wire:click="toggleField({{ $field['field_definition_id'] }}, {{ $currentSection['section_id'] }})"
                        @endif
                        class="d-flex items-center gap-3 py-3 px-5 transition-colors {{ !in_array($instance->status, ['completed', 'cancelled']) ? 'cursor-pointer hover:bg-[var(--ui-muted-5)]/60' : '' }} {{ $field['is_checked'] ? 'bg-green-500/5' : '' }}"
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

                {{-- Prev/Next Navigation --}}
                <div class="px-5 py-3 border-t border-[var(--ui-border)]/30 d-flex items-center justify-between">
                    @if($activeSection > 0)
                    <button wire:click="prevSection" class="d-flex items-center gap-1.5 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors">
                        @svg('heroicon-s-arrow-left', 'w-3.5 h-3.5')
                        {{ \Illuminate\Support\Str::limit($sections[$activeSection - 1]['title'] ?? '', 25) }}
                    </button>
                    @else
                    <div></div>
                    @endif

                    @if($activeSection < count($sections) - 1)
                    <button wire:click="nextSection" class="d-flex items-center gap-1.5 text-xs text-[var(--ui-primary)] hover:text-[var(--ui-primary-dark)] font-medium transition-colors">
                        {{ \Illuminate\Support\Str::limit($sections[$activeSection + 1]['title'] ?? '', 25) }}
                        @svg('heroicon-s-arrow-right', 'w-3.5 h-3.5')
                    </button>
                    @endif
                </div>
            </x-ui-panel>
            @endif

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
