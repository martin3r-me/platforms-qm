<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'QM', 'href' => route('qm.dashboard'), 'icon' => 'clipboard-document-check'],
            ['label' => 'Dashboard'],
        ]" />
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-6">

            {{-- Stats --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <x-ui-dashboard-tile
                    title="Templates"
                    :count="$stats['templates_active']"
                    subtitle="Aktiv ({{ $stats['templates_total'] }} gesamt)"
                    icon="document-duplicate"
                    variant="secondary"
                    size="lg"
                />
                <x-ui-dashboard-tile
                    title="Checklisten"
                    :count="$stats['instances_total']"
                    subtitle="Gesamt"
                    icon="clipboard-document-list"
                    variant="secondary"
                    size="lg"
                />
                <x-ui-dashboard-tile
                    title="Offen"
                    :count="$stats['instances_open']"
                    subtitle="{{ $stats['instances_completed'] }} abgeschlossen"
                    icon="clock"
                    variant="secondary"
                    size="lg"
                />
                <x-ui-dashboard-tile
                    title="Abweichungen"
                    :count="$stats['deviations_open']"
                    subtitle="Offen"
                    icon="exclamation-triangle"
                    :variant="$stats['deviations_open'] > 0 ? 'danger' : 'secondary'"
                    size="lg"
                />
            </div>

            {{-- Recent Instances --}}
            @if($recentInstances->isNotEmpty())
            <x-ui-panel title="Letzte Checklisten" subtitle="{{ $stats['instances_total'] }} Checkliste(n) in diesem Team">
                <x-ui-table compact="true">
                    <x-ui-table-header>
                        <x-ui-table-header-cell compact="true">Titel</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Template</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Status</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt von</x-ui-table-header-cell>
                        <x-ui-table-header-cell compact="true">Erstellt</x-ui-table-header-cell>
                    </x-ui-table-header>
                    <x-ui-table-body>
                        @foreach($recentInstances as $instance)
                        <x-ui-table-row compact="true">
                            <x-ui-table-cell compact="true">
                                <div class="font-medium text-[var(--ui-secondary)]">{{ $instance->title }}</div>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->template?->name ?? 'Ad-hoc' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <x-ui-badge :variant="match($instance->status) {
                                    'completed' => 'success',
                                    'in_progress' => 'info',
                                    'cancelled' => 'danger',
                                    default => 'warning',
                                }">
                                    {{ ucfirst($instance->status) }}
                                </x-ui-badge>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->createdByUser?->name ?? '-' }}</span>
                            </x-ui-table-cell>
                            <x-ui-table-cell compact="true">
                                <span class="text-sm text-[var(--ui-muted)]">{{ $instance->created_at?->diffForHumans() }}</span>
                            </x-ui-table-cell>
                        </x-ui-table-row>
                        @endforeach
                    </x-ui-table-body>
                </x-ui-table>
            </x-ui-panel>
            @else
            {{-- Empty State --}}
            <x-ui-panel>
                <div class="p-12 text-center">
                    @svg('heroicon-o-clipboard-document-check', 'w-16 h-16 text-[var(--ui-muted)] mx-auto mb-4')
                    <h3 class="text-lg font-semibold text-[var(--ui-secondary)] mb-2">Noch keine Checklisten</h3>
                    <p class="text-[var(--ui-muted)]">Erstelle zuerst ein Template per AI-Assistent, dann koennen Checklisten ausgefuellt werden.</p>
                </div>
            </x-ui-panel>
            @endif
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Uebersicht" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Statistiken --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-2">
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-document-duplicate', 'w-4 h-4 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Templates</span>
                            </div>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $stats['templates_total'] }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Checklisten</span>
                            </div>
                            <span class="text-sm font-bold text-[var(--ui-secondary)]">{{ $stats['instances_total'] }}</span>
                        </div>
                        <div class="d-flex items-center justify-between p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/40">
                            <div class="d-flex items-center gap-2">
                                @svg('heroicon-o-exclamation-triangle', 'w-4 h-4 text-[var(--ui-muted)]')
                                <span class="text-xs text-[var(--ui-muted)]">Abweichungen</span>
                            </div>
                            <span class="text-sm font-bold {{ $stats['deviations_open'] > 0 ? 'text-red-500' : 'text-[var(--ui-secondary)]' }}">{{ $stats['deviations_open'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Navigation --}}
                <div>
                    <h3 class="text-[10px] font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-3">Navigation</h3>
                    <div class="space-y-1">
                        <a href="{{ route('qm.field-types.index') }}" class="d-flex items-center gap-2 p-2 rounded-md text-xs text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)] transition-colors" wire:navigate>
                            @svg('heroicon-o-cube', 'w-3.5 h-3.5')
                            Feldtypen
                        </a>
                        <a href="{{ route('qm.field-definitions.index') }}" class="d-flex items-center gap-2 p-2 rounded-md text-xs text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)] transition-colors" wire:navigate>
                            @svg('heroicon-o-adjustments-horizontal', 'w-3.5 h-3.5')
                            Feld-Definitionen
                        </a>
                        <a href="{{ route('qm.sections.index') }}" class="d-flex items-center gap-2 p-2 rounded-md text-xs text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)] transition-colors" wire:navigate>
                            @svg('heroicon-o-rectangle-group', 'w-3.5 h-3.5')
                            Sektionen
                        </a>
                        <a href="{{ route('qm.templates.index') }}" class="d-flex items-center gap-2 p-2 rounded-md text-xs text-[var(--ui-muted)] hover:bg-[var(--ui-muted-5)] hover:text-[var(--ui-secondary)] transition-colors" wire:navigate>
                            @svg('heroicon-o-document-duplicate', 'w-3.5 h-3.5')
                            Templates
                        </a>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar (Activity) --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Hierarchie" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Aufbau</h3>
                    <div class="space-y-3">
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1">1. Feldtypen</div>
                            <p class="text-[11px] text-[var(--ui-muted)]">Basis-Typen (Text, Zahl, Temperatur, Foto, Unterschrift ...). 17 System-Typen + eigene.</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1">2. Feld-Definitionen</div>
                            <p class="text-[11px] text-[var(--ui-muted)]">Konfigurierte Felder mit Titel, Beschreibung, Validierung. z.B. "Kerntemperatur Fleisch" (Typ: Temperatur, Min: 63°C).</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1">3. Sektionen</div>
                            <p class="text-[11px] text-[var(--ui-muted)]">Logische Gruppen von Feldern (z.B. "Temperaturkontrolle", "Hygiene") mit Verhaltensregeln.</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1">4. Templates</div>
                            <p class="text-[11px] text-[var(--ui-muted)]">Blueprint-Checklisten aus Sektionen. HACCP, Eskalation, Signatur-Pflicht konfigurierbar.</p>
                        </div>
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1">5. Checklisten</div>
                            <p class="text-[11px] text-[var(--ui-muted)]">Ausgefuellte Instanzen eines Templates. Abweichungen werden erkannt und nachverfolgt.</p>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
