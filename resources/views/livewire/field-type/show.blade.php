<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Feldtypen', 'href' => route('qm.field-types.index')],
            ['label' => $fieldType->label],
        ]" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            {{-- Info --}}
            <x-ui-panel title="{{ $fieldType->label }}" subtitle="Feldtyp: {{ $fieldType->key }}">
                <div class="px-5 pt-3 d-flex items-center gap-3 text-xs text-[var(--ui-muted)]">
                    <x-ui-badge :variant="$fieldType->is_system ? 'info' : 'warning'" size="sm">
                        {{ $fieldType->is_system ? 'System' : 'Custom' }}
                    </x-ui-badge>
                    <span class="font-mono font-bold text-[var(--ui-secondary)]">{{ $fieldType->key }}</span>
                    <span>{{ $fieldType->field_definitions_count }} Definitionen</span>
                </div>
                <div class="p-5 space-y-4">
                    @if($fieldType->description)
                        <p class="text-sm text-[var(--ui-muted)]">{{ $fieldType->description }}</p>
                    @endif

                    @if($fieldType->default_config)
                        <div>
                            <h4 class="text-xs font-semibold text-[var(--ui-secondary)] uppercase mb-2">Standard-Konfiguration</h4>
                            <pre class="text-xs bg-[var(--ui-muted-5)] rounded-lg p-3 overflow-x-auto text-[var(--ui-muted)]">{{ json_encode($fieldType->default_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif
                </div>
            </x-ui-panel>

            {{-- Field Definitions using this type --}}
            <x-ui-panel title="Feld-Definitionen" subtitle="Felder die diesen Typ verwenden">
                @if($fieldDefinitions->isNotEmpty())
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Beschreibung</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($fieldDefinitions as $fd)
                        <x-ui-table-row compact="true" clickable="true" :href="route('qm.field-definitions.show', $fd)" wire:navigate>
                            <x-ui-table-cell compact="true">
                                <span class="font-medium text-[var(--ui-secondary)]">{{ $fd->title }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ Str::limit($fd->description, 60) }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $fd->createdByUser?->name ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $fd->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
                @else
                <div class="p-8 text-center text-[var(--ui-muted)] text-sm">Noch keine Feld-Definitionen fuer diesen Typ.</div>
                @endif
            </x-ui-panel>
        </div>
    </x-ui-page-container>
</x-ui-page>
