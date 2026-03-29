<div
    x-data="{
        init() {
            const savedState = localStorage.getItem('qm.showAllTemplates');
            if (savedState !== null) {
                @this.set('showAllTemplates', savedState === 'true');
            }
        }
    }"
>

    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Quality Management
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('qm.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.templates.index')">
            @svg('heroicon-o-document-duplicate', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Templates</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.instances.index')">
            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Checklisten</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.deviations.index')">
            @svg('heroicon-o-exclamation-triangle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Abweichungen</span>
            @if($openDeviationsCount > 0)
                <x-slot name="trailing">
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-red-500/10 text-red-500 font-semibold">{{ $openDeviationsCount }}</span>
                </x-slot>
            @endif
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Abschnitt: Bausteine --}}
    <x-ui-sidebar-list label="Bausteine">
        <x-ui-sidebar-item :href="route('qm.sections.index')">
            @svg('heroicon-o-rectangle-group', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Sektionen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.field-definitions.index')">
            @svg('heroicon-o-adjustments-horizontal', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Feld-Definitionen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.field-types.index')">
            @svg('heroicon-o-cube', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Feldtypen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('qm.lookups.index')">
            @svg('heroicon-o-table-cells', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Stammdaten</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('qm.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Dashboard">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('qm.templates.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Templates">
                @svg('heroicon-o-document-duplicate', 'w-5 h-5')
            </a>
            <a href="{{ route('qm.instances.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Checklisten">
                @svg('heroicon-o-clipboard-document-list', 'w-5 h-5')
            </a>
            <a href="{{ route('qm.deviations.index') }}" wire:navigate class="relative flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Abweichungen">
                @svg('heroicon-o-exclamation-triangle', 'w-5 h-5')
                @if($openDeviationsCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 text-[9px] flex items-center justify-center rounded-full bg-red-500 text-white font-bold">{{ $openDeviationsCount }}</span>
                @endif
            </a>
        </div>
    </div>
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('qm.sections.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Sektionen">
                @svg('heroicon-o-rectangle-group', 'w-5 h-5')
            </a>
            <a href="{{ route('qm.field-definitions.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Feld-Definitionen">
                @svg('heroicon-o-adjustments-horizontal', 'w-5 h-5')
            </a>
            <a href="{{ route('qm.lookups.index') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]" title="Stammdaten">
                @svg('heroicon-o-table-cells', 'w-5 h-5')
            </a>
        </div>
    </div>

    {{-- Templates-Liste --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            @if($templates->isNotEmpty())
                <x-ui-sidebar-list label="Templates">
                    @foreach($templates as $template)
                        <x-ui-sidebar-item :href="route('qm.templates.show', $template)" :title="$template->name">
                            @svg('heroicon-o-document-duplicate', 'w-4 h-4 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2 flex items-center gap-1.5">
                                <span class="truncate text-sm font-medium">{{ $template->name }}</span>
                                @if($template->wizard_fields_count > 0)
                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)] flex-shrink-0" title="Wizard konfiguriert">W</span>
                                @endif
                            </div>
                            <x-slot name="trailing">
                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[var(--ui-muted-5)] text-[var(--ui-muted)]">
                                    {{ $template->instances_count }}
                                </span>
                            </x-slot>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Toggle: Alle/Meine Templates --}}
            @if($hasMoreTemplates)
                <div class="px-3 py-2">
                    <button
                        type="button"
                        wire:click="toggleShowAllTemplates"
                        x-on:click="localStorage.setItem('qm.showAllTemplates', (!$wire.showAllTemplates).toString())"
                        class="flex items-center gap-2 text-xs text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                    >
                        @if($showAllTemplates)
                            @svg('heroicon-o-eye-slash', 'w-4 h-4')
                            <span>Nur meine Templates</span>
                        @else
                            @svg('heroicon-o-eye', 'w-4 h-4')
                            <span>Alle Templates anzeigen</span>
                        @endif
                    </button>
                </div>
            @endif

            {{-- Letzte Checklisten --}}
            @if($recentInstances->isNotEmpty())
                <x-ui-sidebar-list label="Zuletzt bearbeitet">
                    @foreach($recentInstances as $instance)
                        <x-ui-sidebar-item :href="route('qm.instances.show', $instance)" :title="$instance->title">
                            @svg('heroicon-o-clipboard-document-list', 'w-4 h-4 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium leading-tight">{{ $instance->title }}</div>
                                <div class="text-[10px] text-[var(--ui-muted)] truncate">{{ $instance->template?->name }}</div>
                            </div>
                            <x-slot name="trailing">
                                @php
                                    $variant = match($instance->status) {
                                        'completed' => 'bg-green-500/10 text-green-600',
                                        'in_progress' => 'bg-blue-500/10 text-blue-600',
                                        'cancelled' => 'bg-red-500/10 text-red-500',
                                        default => 'bg-yellow-500/10 text-yellow-600',
                                    };
                                @endphp
                                <span class="text-[9px] px-1.5 py-0.5 rounded {{ $variant }} font-medium">{{ ucfirst($instance->status) }}</span>
                            </x-slot>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @endif

            {{-- Leer-State --}}
            @if($templates->isEmpty() && $recentInstances->isEmpty())
                <div class="px-3 py-4 text-xs text-[var(--ui-muted)]">
                    Noch keine Templates oder Checklisten vorhanden.
                </div>
            @endif
        </div>
    </div>
</div>
