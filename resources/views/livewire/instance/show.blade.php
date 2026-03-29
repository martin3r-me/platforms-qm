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
            {{-- Instance Info --}}
            <x-ui-panel>
                <div class="p-4">
                    <div class="d-flex items-center gap-3 flex-wrap text-xs text-[var(--ui-muted)]">
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
                        <a href="{{ route('qm.templates.show', $instance->template) }}" wire:navigate class="d-flex items-center gap-1 hover:text-[var(--ui-secondary)] transition-colors">
                            @svg('heroicon-o-document-duplicate', 'w-3.5 h-3.5') {{ $instance->template->name }}
                        </a>
                        @endif
                        @if($instance->score !== null)
                        <span class="font-mono font-bold {{ $instance->score >= 80 ? 'text-green-600' : ($instance->score >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                            {{ number_format($instance->score, 0) }}%
                        </span>
                        @endif
                        @if($instance->due_at)
                        <span class="d-flex items-center gap-1 {{ $instance->due_at->isPast() && !in_array($instance->status, ['completed', 'cancelled']) ? 'text-red-500 font-bold' : '' }}">
                            @svg('heroicon-o-clock', 'w-3.5 h-3.5') {{ $instance->due_at->format('d.m.Y H:i') }}
                        </span>
                        @endif
                        <span class="text-[var(--ui-border)]">|</span>
                        <span class="d-flex items-center gap-1">@svg('heroicon-o-user', 'w-3.5 h-3.5') {{ $instance->createdByUser?->name ?? 'Unbekannt' }}</span>
                        <span>{{ $instance->created_at?->format('d.m.Y H:i') }}</span>
                        @if($instance->completed_at)
                        <span class="d-flex items-center gap-1 text-green-600">@svg('heroicon-o-check-circle', 'w-3.5 h-3.5') {{ $instance->completed_at->format('d.m.Y H:i') }}</span>
                        @endif
                    </div>
                    @if($instance->description)
                    <p class="text-sm text-[var(--ui-muted)] leading-relaxed mt-2">{{ $instance->description }}</p>
                    @endif
                </div>
            </x-ui-panel>

            {{-- Completion Bar --}}
            <x-ui-panel>
                <div class="p-5">
                    <div class="d-flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-[var(--ui-secondary)]">Fortschritt</span>
                        <span class="text-sm font-bold {{ ($stats['completion_percent'] ?? 0) >= 80 ? 'text-green-600' : (($stats['completion_percent'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-[var(--ui-muted)]') }}">
                            {{ $stats['completion_percent'] ?? 0 }}%
                        </span>
                    </div>
                    <div class="w-full bg-[var(--ui-muted-5)] rounded-full h-2">
                        <div class="h-2 rounded-full transition-all {{ ($stats['completion_percent'] ?? 0) >= 80 ? 'bg-green-500' : (($stats['completion_percent'] ?? 0) >= 50 ? 'bg-yellow-500' : 'bg-[var(--ui-muted)]') }}"
                             style="width: {{ min(100, $stats['completion_percent'] ?? 0) }}%"></div>
                    </div>
                    <div class="d-flex items-center gap-4 mt-3 text-xs text-[var(--ui-muted)]">
                        <span>{{ $stats['responses_count'] ?? 0 }} / {{ $stats['total_fields'] ?? 0 }} Felder beantwortet</span>
                        <span>{{ $stats['required_fields'] ?? 0 }} Pflichtfelder</span>
                        @if(($stats['deviations_count'] ?? 0) > 0)
                        <span class="text-red-500 font-medium">{{ $stats['deviations_count'] }} Abweichung(en)</span>
                        @endif
                    </div>
                </div>
            </x-ui-panel>

            {{-- Sections with Responses --}}
            @forelse($sections as $section)
            <x-ui-panel
                title="{{ $section['title'] }}"
                subtitle="{{ count($section['fields']) }} Feld(er)"
            >
                @if($section['description'])
                    <div class="px-5 pt-3 text-xs text-[var(--ui-muted)]">{{ $section['description'] }}</div>
                @endif

                @if(!empty($section['fields']))
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Feld</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Typ</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Antwort</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Beantwortet</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($section['fields'] as $field)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $field['title'] }}</div>
                                @if($field['is_required'])
                                <span class="text-[10px] text-red-500">Pflicht</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge variant="secondary" size="sm">{{ $field['field_type'] }}</x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($field['response'])
                                    @php $val = $field['response']->value; @endphp
                                    <span class="text-sm text-[var(--ui-secondary)]">
                                        @if(is_array($val))
                                            {{ json_encode($val) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </span>
                                    @if($field['response']->notes)
                                    <div class="text-[10px] text-[var(--ui-muted)] mt-0.5">{{ $field['response']->notes }}</div>
                                    @endif
                                @else
                                    <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($field['response'])
                                    @if($field['response']->is_deviation)
                                        <x-ui-badge variant="danger" size="sm">Abweichung</x-ui-badge>
                                    @else
                                        <x-ui-badge variant="success" size="sm">OK</x-ui-badge>
                                    @endif
                                @else
                                    <x-ui-badge variant="secondary" size="sm">Offen</x-ui-badge>
                                @endif
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                @if($field['response'])
                                <div class="text-xs text-[var(--ui-muted)]">
                                    {{ $field['response']->respondedByUser?->name ?? '-' }}
                                </div>
                                <div class="text-[10px] text-[var(--ui-muted)]">
                                    {{ $field['response']->responded_at?->format('d.m.Y H:i') }}
                                </div>
                                @else
                                <span class="text-xs text-[var(--ui-muted)]">-</span>
                                @endif
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @endif
            </x-ui-panel>
            @empty
            <x-ui-panel>
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">
                    Kein Snapshot vorhanden. Diese Instanz hat keine Template-Struktur.
                </div>
            </x-ui-panel>
            @endforelse

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
