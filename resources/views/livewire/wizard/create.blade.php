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
                @foreach(['Wizard-Felder', 'Vorschau', 'Erstellt'] as $i => $label)
                    <div class="d-flex items-center gap-2">
                        <span class="w-6 h-6 rounded-full d-flex items-center justify-center text-xs font-bold {{ $step === $i + 1 ? 'bg-[var(--ui-primary)] text-white' : ($step > $i + 1 ? 'bg-green-500 text-white' : 'bg-[var(--ui-muted-5)] text-[var(--ui-muted)]') }}">
                            @if($step > $i + 1)
                                @svg('heroicon-s-check', 'w-3.5 h-3.5')
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="{{ $step === $i + 1 ? 'font-medium text-[var(--ui-secondary)]' : '' }}">{{ $label }}</span>
                    </div>
                    @if($i < 2)
                    <div class="w-8 h-px bg-[var(--ui-border)]"></div>
                    @endif
                @endforeach
            </div>

            {{-- Step 1: Wizard Fields --}}
            @if($step === 1)
            <x-ui-panel title="Checkliste konfigurieren" subtitle="Beantworte die Fragen, um die passenden Sektionen zu aktivieren.">
                <div class="p-5 space-y-5">
                    @foreach($config['fields'] as $field)
                    <div>
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">
                            {{ $field['label'] }}
                            @if($field['is_required'])
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        @if($field['description'])
                        <p class="text-xs text-[var(--ui-muted)] mb-2">{{ $field['description'] }}</p>
                        @endif

                        @if($field['input_type'] === 'single_select' && !empty($field['lookup_table']['entries']))
                            <x-ui-input-select
                                name="answers_{{ $field['technical_name'] }}"
                                wire:model.live="answers.{{ $field['technical_name'] }}"
                                :options="collect($field['lookup_table']['entries'])->map(fn($e) => ['value' => $e['value'], 'label' => $e['label']])->toArray()"
                                :nullable="true"
                                nullLabel="-- Bitte waehlen --"
                            />
                        @elseif($field['input_type'] === 'multi_select' && !empty($field['lookup_table']['entries']))
                            <div class="space-y-2">
                                @foreach($field['lookup_table']['entries'] as $entry)
                                <label class="d-flex items-center gap-2 cursor-pointer text-sm text-[var(--ui-secondary)]">
                                    <input type="checkbox"
                                        wire:model.live="answers.{{ $field['technical_name'] }}"
                                        value="{{ $entry['value'] }}"
                                        class="rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]">
                                    {{ $entry['label'] }}
                                </label>
                                @endforeach
                            </div>
                        @elseif($field['input_type'] === 'boolean')
                            <label class="d-flex items-center gap-2 cursor-pointer text-sm text-[var(--ui-secondary)]">
                                <input type="checkbox"
                                    wire:model.live="answers.{{ $field['technical_name'] }}"
                                    class="rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]">
                                Ja
                            </label>
                        @elseif($field['input_type'] === 'number')
                            <x-ui-input-text type="number" wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @elseif($field['input_type'] === 'date')
                            <x-ui-input-text type="date" wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @else
                            <x-ui-input-text wire:model.live="answers.{{ $field['technical_name'] }}" />
                        @endif

                        @error('answers.' . $field['technical_name'])
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach

                    {{-- Live Counter --}}
                    @if($evaluation)
                    <div class="p-3 rounded-lg bg-[var(--ui-muted-5)] d-flex items-center gap-2 text-sm">
                        @svg('heroicon-o-rectangle-group', 'w-4 h-4 text-[var(--ui-muted)]')
                        <span class="text-[var(--ui-secondary)]">
                            <span class="font-bold">{{ $evaluation['active_count'] }}</span> von {{ $evaluation['total_sections'] }} Sektionen werden aktiviert
                        </span>
                    </div>
                    @endif
                </div>

                <div class="px-5 pb-5 d-flex justify-end">
                    <x-ui-button wire:click="goToPreview" variant="primary">
                        Weiter zur Vorschau
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
                        <h4 class="text-sm font-medium text-[var(--ui-secondary)] mb-2">Deine Angaben</h4>
                        <div class="rounded-lg bg-[var(--ui-muted-5)] p-3 space-y-1">
                            @foreach($config['fields'] as $field)
                            @if(!empty($answers[$field['technical_name']] ?? null))
                            <div class="d-flex items-center gap-2 text-sm">
                                <span class="text-[var(--ui-muted)]">{{ $field['label'] }}:</span>
                                <span class="font-medium text-[var(--ui-secondary)]">
                                    @php $val = $answers[$field['technical_name']]; @endphp
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

                    {{-- Matched Rules --}}
                    @if(!empty($evaluation['matched_rules']))
                    <div>
                        <h4 class="text-sm font-medium text-[var(--ui-secondary)] mb-2">Angewendete Regeln</h4>
                        <div class="d-flex items-center gap-2 flex-wrap">
                            @foreach($evaluation['matched_rules'] as $rule)
                            <x-ui-badge variant="info" size="sm">{{ $rule['name'] }}</x-ui-badge>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Active Sections by Phase --}}
                    <div>
                        <h4 class="text-sm font-medium text-[var(--ui-secondary)] mb-2">
                            Aktive Sektionen ({{ $evaluation['active_count'] ?? 0 }})
                        </h4>
                        <div class="space-y-3">
                            @foreach($phaseGroups as $phase => $sections)
                            <div>
                                <div class="text-xs font-medium text-[var(--ui-muted)] uppercase tracking-wide mb-1">{{ $phase }}</div>
                                <div class="space-y-1">
                                    @foreach($sections as $section)
                                    <div class="d-flex items-center gap-2 text-sm text-[var(--ui-secondary)] py-1 px-2 rounded bg-[var(--ui-muted-5)]">
                                        @svg('heroicon-o-check-circle', 'w-4 h-4 text-green-500 flex-shrink-0')
                                        {{ $section['section_title'] }}
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
                        <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1.5">Titel der Checkliste</label>
                        <x-ui-input-text wire:model="instanceTitle" />
                    </div>
                </div>

                <div class="px-5 pb-5 d-flex items-center justify-between">
                    <x-ui-button wire:click="goBack" variant="secondary">
                        Zurueck
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
                <div class="p-8 text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-green-100 d-flex items-center justify-center mx-auto">
                        @svg('heroicon-o-check-circle', 'w-10 h-10 text-green-500')
                    </div>
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)]">Checkliste erstellt</h3>
                    <p class="text-sm text-[var(--ui-muted)]">
                        Die Checkliste <span class="font-medium text-[var(--ui-secondary)]">{{ $createdInstance->title }}</span> wurde erfolgreich erstellt.
                    </p>
                    <div class="d-flex items-center justify-center gap-3 pt-2">
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
