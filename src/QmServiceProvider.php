<?php

namespace Platform\Qm;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class QmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Relation::morphMap([
            'qm_instance' => \Platform\Qm\Models\QmInstance::class,
            'qm_instance_response' => \Platform\Qm\Models\QmInstanceResponse::class,
            'qm_deviation' => \Platform\Qm\Models\QmDeviation::class,
        ]);

        // Step 1: Load config
        $this->mergeConfigFrom(__DIR__ . '/../config/qm.php', 'qm');

        // Step 2: Register module
        if (
            config()->has('qm.routing') &&
            config()->has('qm.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key' => 'qm',
                'title' => 'QM',
                'group' => 'planning',
                'routing' => config('qm.routing'),
                'guard' => config('qm.guard'),
                'navigation' => config('qm.navigation'),
                'sidebar' => config('qm.sidebar'),
            ]);
        }

        // Step 3: Routes (if module registered)
        if (PlatformCore::getModule('qm')) {
            ModuleRouter::group('qm', function () {
                $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
            });
        }

        // Step 4: Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Step 5: Publish config
        $this->publishes([
            __DIR__ . '/../config/qm.php' => config_path('qm.php'),
        ], 'config');

        // Step 6: Views & Livewire
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'qm');
        $this->registerLivewireComponents();

        // Step 7: Tools
        $this->registerTools();

        // Step 8: Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Platform\Qm\Console\ProcessRecurrencesCommand::class,
                \Platform\Qm\Console\ProcessEscalationsCommand::class,
                \Platform\Qm\Console\SeedFieldTypesCommand::class,
            ]);
        }

        // Step 9: Schedule
        $this->app->booted(function () {
            if ($this->app->bound(Schedule::class)) {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('qm:process-recurrences')->hourly();
                $schedule->command('qm:process-escalations')->everyFifteenMinutes();
            }
        });
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);

            // Overview
            $registry->register(new \Platform\Qm\Tools\QmOverviewTool());

            // Field Types
            $registry->register(new \Platform\Qm\Tools\ListFieldTypesTool());
            $registry->register(new \Platform\Qm\Tools\GetFieldTypeTool());
            $registry->register(new \Platform\Qm\Tools\CreateFieldTypeTool());

            // Field Definitions
            $registry->register(new \Platform\Qm\Tools\ListFieldDefinitionsTool());
            $registry->register(new \Platform\Qm\Tools\GetFieldDefinitionTool());
            $registry->register(new \Platform\Qm\Tools\CreateFieldDefinitionTool());
            $registry->register(new \Platform\Qm\Tools\UpdateFieldDefinitionTool());
            $registry->register(new \Platform\Qm\Tools\DeleteFieldDefinitionTool());

            // Sections
            $registry->register(new \Platform\Qm\Tools\ListSectionsTool());
            $registry->register(new \Platform\Qm\Tools\GetSectionTool());
            $registry->register(new \Platform\Qm\Tools\CreateSectionTool());
            $registry->register(new \Platform\Qm\Tools\UpdateSectionTool());
            $registry->register(new \Platform\Qm\Tools\DeleteSectionTool());
            $registry->register(new \Platform\Qm\Tools\ManageSectionFieldsTool());

            // Templates
            $registry->register(new \Platform\Qm\Tools\ListTemplatesTool());
            $registry->register(new \Platform\Qm\Tools\GetTemplateTool());
            $registry->register(new \Platform\Qm\Tools\CreateTemplateTool());
            $registry->register(new \Platform\Qm\Tools\UpdateTemplateTool());
            $registry->register(new \Platform\Qm\Tools\DeleteTemplateTool());
            $registry->register(new \Platform\Qm\Tools\ManageTemplateSectionsTool());
            $registry->register(new \Platform\Qm\Tools\DuplicateTemplateTool());

            // Instances
            $registry->register(new \Platform\Qm\Tools\ListInstancesTool());
            $registry->register(new \Platform\Qm\Tools\GetInstanceTool());
            $registry->register(new \Platform\Qm\Tools\CreateInstanceTool());
            $registry->register(new \Platform\Qm\Tools\UpdateInstanceTool());
            $registry->register(new \Platform\Qm\Tools\SubmitResponsesTool());
            $registry->register(new \Platform\Qm\Tools\CompleteInstanceTool());
            $registry->register(new \Platform\Qm\Tools\PublicLinkTool());

            // Deviations
            $registry->register(new \Platform\Qm\Tools\ListDeviationsTool());
            $registry->register(new \Platform\Qm\Tools\GetDeviationTool());
            $registry->register(new \Platform\Qm\Tools\UpdateDeviationTool());
            $registry->register(new \Platform\Qm\Tools\EscalateDeviationTool());
            $registry->register(new \Platform\Qm\Tools\VerifyDeviationTool());

            // Lookup Tables
            $registry->register(new \Platform\Qm\Tools\ListLookupTablesTool());
            $registry->register(new \Platform\Qm\Tools\GetLookupTableTool());
            $registry->register(new \Platform\Qm\Tools\CreateLookupTableTool());
            $registry->register(new \Platform\Qm\Tools\UpdateLookupTableTool());
            $registry->register(new \Platform\Qm\Tools\DeleteLookupTableTool());
            $registry->register(new \Platform\Qm\Tools\ManageLookupEntriesTool());

            // Wizard
            $registry->register(new \Platform\Qm\Tools\GetWizardConfigTool());
            $registry->register(new \Platform\Qm\Tools\ManageWizardFieldsTool());
            $registry->register(new \Platform\Qm\Tools\ManageWizardRulesTool());
            $registry->register(new \Platform\Qm\Tools\ManageWizardRuleSectionsTool());
            $registry->register(new \Platform\Qm\Tools\EvaluateWizardTool());
            $registry->register(new \Platform\Qm\Tools\CreateInstanceFromWizardTool());

            // Analytics
            $registry->register(new \Platform\Qm\Tools\QmStatsTool());
            $registry->register(new \Platform\Qm\Tools\QmExportTool());

            // Schedule
            $registry->register(new \Platform\Qm\Tools\ScheduleTool());
        } catch (\Throwable $e) {
            \Log::warning('QM: Tool-Registrierung fehlgeschlagen', ['error' => $e->getMessage()]);
        }
    }

    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Qm\\Livewire';
        $prefix = 'qm';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }
}
