<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Templates', 'href' => route('qm.templates.index')],
            ['label' => $template->name, 'href' => route('qm.templates.show', $template)],
            ['label' => 'Neue Checkliste'],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6 max-w-3xl mx-auto">

            {{-- Step Indicator --}}
            <div class="d-flex items-center justify-center gap-2 text-xs text-[var(--ui-muted)]">
                @foreach(['Konfiguration', 'Vorschau', 'Erstellt'] as $i => $label)
                    <div class="d-flex items-center gap-2">
                        <span class="w-7 h-7 rounded-full d-flex items-center justify-center text-xs font-bold {{ $step === $i + 1 ? 'bg-[var(--ui-primary)] text-white' : ($step > $i + 1 ? 'bg-green-500 text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                            @if($step > $i + 1)
                                @svg('heroicon-s-check', 'w-3.5 h-3.5')
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="{{ $step === $i + 1 ? 'font-medium text-[var(--ui-secondary)]' : '' }}">{{ $label }}</span>
                    </div>
                    @if($i < 2)
                    <div class="w-12 h-px {{ $step > $i + 1 ? 'bg-green-500' : 'bg-[var(--ui-border)]' }}"></div>
                    @endif
                @endforeach
            </div>

            {{-- Step 1: Wizard Fields --}}
            @if($step === 1)
            <x-ui-panel title="Checkliste konfigurieren" subtitle="{{ $template->name }}">
                <div class="p-5 space-y-6">
                    @foreach($config['fields'] as $field)
                    <div class="pb-5 {{ !$loop->last ? 'border-b border-[var(--ui-border)]/30' : '' }}">
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                            {{ $field['label'] }}
                            @if($field['is_required'])
                            <span class="text-red-500 ml-0.5">*</span>
                            @endif
                        </label>
                        @if($field['description'])
                        <p class="text-xs text-[var(--ui-muted)] mb-2.5">{{ $field['description'] }}</p>
                        @endif

                        @if($field['input_type'] === 'single_select' && !empty($field['lookup_table']['entries']))
                            <x-ui-input-select
                                name="answers_{{ $field['technical_name'] }}"
                                wire:model.live="answers.{{ $field['technical_name'] }}"
                                :options="collect($field['lookup_table']['entries'])->map(fn($e) => ['value' => $e['value'], 'label' => $e['label']])->toArray()"
                                :nullable="true"
                                nullLabel="-- Bitte waehlen --"
                                displayMode="dropdown"
                            />
                        @elseif($field['input_type'] === 'multi_select' && !empty($field['lookup_table']['entries']))
                            <div class="d-flex items-center gap-2 flex-wrap">
                                @foreach($field['lookup_table']['entries'] as $entry)
                                @php $isSelected = in_array($entry['value'], $answers[$field['technical_name']] ?? []); @endphp
                                <button
                                    type="button"
                                    wire:click="toggleMultiSelect('{{ $field['technical_name'] }}', '{{ $entry['value'] }}')"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all border-2 {{ $isSelected ? 'bg-[var(--ui-primary)] text-white border-[var(--ui-primary)] shadow-sm' : 'bg-white/50 text-[var(--ui-secondary)] border-[var(--ui-border)]/50 hover:border-[var(--ui-primary)]/50 hover:bg-[var(--ui-muted-5)]' }}"
                                >
                                    @if($isSelected)
                                    @svg('heroicon-s-check', 'w-4 h-4 inline mr-1')
                                    @endif
                                    {{ $entry['label'] }}
                                </button>
                                @endforeach
                            </div>
                        @elseif($field['input_type'] === 'boolean')
                            <label class="d-flex items-center gap-2.5 cursor-pointer text-sm text-[var(--ui-secondary)] py-2 px-3 rounded-lg hover:bg-[var(--ui-muted-5)] transition-colors w-fit">
                                <input type="checkbox"
                                    wire:model.live="answers.{{ $field['technical_name'] }}"
                                    class="rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)] w-4 h-4">
                                Ja
                            </label>
                        @elseif($field['input_type'] === 'number')
                            <x-ui-input-text name="answers_{{ $field['technical_name'] }}" type="number" wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @elseif($field['input_type'] === 'date')
                            <x-ui-input-text name="answers_{{ $field['technical_name'] }}" type="date" wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @else
                            <x-ui-input-text name="answers_{{ $field['technical_name'] }}" wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @endif

                        @error('answers.' . $field['technical_name'])
                        <p class="text-xs text-red-500 mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach

                    {{-- Live Counter --}}
                    @if($evaluation)
                    <div class="p-4 rounded-lg bg-[var(--ui-primary)]/5 border border-[var(--ui-primary)]/20 d-flex items-center gap-3">
                        @svg('heroicon-o-rectangle-group', 'w-5 h-5 text-[var(--ui-primary)] flex-shrink-0')
                        <div>
                            <span class="text-sm font-medium text-[var(--ui-secondary)]">
                                <span class="text-[var(--ui-primary)] font-bold">{{ $evaluation['active_count'] }}</span> von {{ $evaluation['total_sections'] }} Sektionen aktiv
                            </span>
                            @if(!empty($evaluation['matched_rules']))
                            <div class="d-flex items-center gap-1.5 mt-1 flex-wrap">
                                @foreach($evaluation['matched_rules'] as $rule)
                                <x-ui-badge variant="info" size="sm">{{ $rule['name'] }}</x-ui-badge>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>

                <div class="px-5 pb-5 d-flex items-center justify-between border-t border-[var(--ui-border)]/30 pt-4">
                    <a href="{{ route('qm.templates.show', $template) }}" wire:navigate>
                        <x-ui-button variant="secondary">Abbrechen</x-ui-button>
                    </a>
                    <x-ui-button wire:click="goToPreview" variant="primary">
                        Weiter zur Vorschau @svg('heroicon-s-arrow-right', 'w-4 h-4 inline ml-1')
                    </x-ui-button>
                </div>
            </x-ui-panel>
            @endif

            {{-- Step 2: Preview --}}
            @if($step === 2)
            <x-ui-panel title="Vorschau" subtitle="Pruefe die Konfiguration und erstelle die Checkliste.">
                <div class="p-5 space-y-5">
                    {{-- Wizard Answers Summary --}}
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wider mb-2">Deine Angaben</h4>
                        <div class="rounded-lg border border-[var(--ui-border)]/50 divide-y divide-[var(--ui-border)]/30">
                            @foreach($config['fields'] as $field)
                            @php $val = $answers[$field['technical_name']] ?? null; @endphp
                            @if(!empty($val))
                            <div class="d-flex items-center justify-between px-4 py-2.5">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $field['label'] }}</span>
                                <span class="text-sm font-medium text-[var(--ui-secondary)]">
                                    @if(is_array($val))
                                        {{ implode(', ', $val) }}
                                    @elseif($field['input_type'] === 'boolean')
                                        {{ $val ? 'Ja' : 'Nein' }}
                                    @else
                                        {{ $val }}
                                    @endif
                                </span>
                            </div>
                            @endif
                            @endforeach
                        </div>
                    </div>

                    {{-- Active Sections by Phase --}}
                    <div>
                        <h4 class="text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wider mb-2">
                            Aktive Sektionen ({{ $evaluation['active_count'] ?? 0 }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($phaseGroups as $phase => $sections)
                            <div>
                                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wide mb-1.5">{{ $phase }}</div>
                                <div class="rounded-lg border border-[var(--ui-border)]/50 divide-y divide-[var(--ui-border)]/30">
                                    @foreach($sections as $section)
                                    <div class="d-flex items-center gap-2 px-4 py-2 text-sm text-[var(--ui-secondary)]">
                                        @svg('heroicon-o-check-circle', 'w-4 h-4 text-green-500 flex-shrink-0')
                                        <span class="flex-grow-1">{{ $section['section_title'] }}</span>
                                        @if($section['section_category'] === 'addon')
                                        <x-ui-badge variant="info" size="sm">Add-On</x-ui-badge>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Instance Title --}}
                    <div>
                        <label class="block text-xs font-semibold text-[var(--ui-muted)] uppercase tracking-wider mb-2">Titel der Checkliste</label>
                        <x-ui-input-text name="instanceTitle" wire:model="instanceTitle" />
                    </div>
                </div>

                <div class="px-5 pb-5 d-flex items-center justify-between border-t border-[var(--ui-border)]/30 pt-4">
                    <x-ui-button wire:click="goBack" variant="secondary">
                        @svg('heroicon-s-arrow-left', 'w-4 h-4 inline mr-1') Zurueck
                    </x-ui-button>
                    <x-ui-button wire:click="createInstance" variant="primary">
                        Checkliste erstellen
                    </x-ui-button>
                </div>
            </x-ui-panel>
            @endif

            {{-- Step 3: Created --}}
            @if($step === 3 && $createdInstance)
            <x-ui-panel>
                <div class="p-10 text-center space-y-5">
                    <div class="w-20 h-20 rounded-full bg-green-500/10 d-flex items-center justify-center mx-auto">
                        @svg('heroicon-o-check-circle', 'w-12 h-12 text-green-500')
                    </div>
                    <div>
                        <h3 class="text-xl font-semibold text-[var(--ui-secondary)]">Checkliste erstellt</h3>
                        <p class="text-sm text-[var(--ui-muted)] mt-2">
                            <span class="font-medium text-[var(--ui-secondary)]">{{ $createdInstance->title }}</span> ist bereit zum Ausfuellen.
                        </p>
                    </div>
                    <div class="d-flex items-center justify-center gap-3 pt-3">
                        <a href="{{ route('qm.instances.show', $createdInstance) }}" wire:navigate>
                            <x-ui-button variant="primary">Zur Checkliste</x-ui-button>
                        </a>
                        <a href="{{ route('qm.instances.index') }}" wire:navigate>
                            <x-ui-button variant="secondary">Alle Checklisten</x-ui-button>
                        </a>
                    </div>
                </div>
            </x-ui-panel>
            @endif

        </div>
    </x-ui-page-container>
</x-ui-page>
