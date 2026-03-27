<?php

namespace Platform\Qm\Services;

use Carbon\Carbon;
use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmTemplate;

class QmRecurrenceService
{
    /**
     * Process all recurring instances that are due.
     * Returns the number of new instances created.
     *
     * recurrence_config format:
     * {
     *   "frequency": "daily|weekly|monthly",
     *   "interval": 1,           // every N days/weeks/months
     *   "days": ["mon","wed"],   // for weekly: which days
     *   "day_of_month": 1,       // for monthly: which day
     *   "time": "08:00",         // time of day
     *   "ends_at": "2025-12-31", // optional: end date
     *   "max_occurrences": 100,  // optional: max total instances
     *   "auto_due_hours": 24     // optional: auto-set due_at X hours after creation
     * }
     */
    public function processRecurrences(): int
    {
        $created = 0;

        // Find all instances with recurrence_config that are completed or the "parent" template instances
        $recurringInstances = QmInstance::query()
            ->whereNotNull('recurrence_config')
            ->where('status', 'completed')
            ->whereDoesntHave('childInstances', function ($q) {
                // No child instance created after the parent was completed
                $q->where('created_at', '>=', Carbon::now()->subMinutes(5));
            })
            ->get();

        foreach ($recurringInstances as $instance) {
            $config = $instance->recurrence_config;
            if (!$config || empty($config['frequency'])) {
                continue;
            }

            if (!$this->isDue($instance, $config)) {
                continue;
            }

            // Check end conditions
            if (!empty($config['ends_at']) && Carbon::parse($config['ends_at'])->isPast()) {
                continue;
            }

            if (!empty($config['max_occurrences'])) {
                $totalChildren = QmInstance::where('parent_instance_id', $instance->id)->count();
                // Also count if this instance itself has a parent
                $rootId = $instance->parent_instance_id ?? $instance->id;
                $totalInChain = QmInstance::where('parent_instance_id', $rootId)
                    ->orWhere('id', $rootId)
                    ->count();
                if ($totalInChain >= (int)$config['max_occurrences']) {
                    continue;
                }
            }

            $newInstance = $this->createNextInstance($instance, $config);
            if ($newInstance) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Check if a recurring instance is due for a new occurrence.
     */
    protected function isDue(QmInstance $instance, array $config): bool
    {
        $frequency = $config['frequency'] ?? 'daily';
        $interval = max(1, (int)($config['interval'] ?? 1));
        $now = now();
        $completedAt = $instance->completed_at ?? $instance->created_at;

        if (!$completedAt) {
            return false;
        }

        // Check time of day if specified
        if (!empty($config['time'])) {
            $targetTime = Carbon::parse($config['time']);
            if ($now->format('H:i') < $targetTime->format('H:i')) {
                return false;
            }
        }

        return match ($frequency) {
            'daily' => $completedAt->diffInDays($now) >= $interval,
            'weekly' => $this->isWeeklyDue($instance, $config, $now, $completedAt, $interval),
            'monthly' => $this->isMonthlyDue($config, $now, $completedAt, $interval),
            default => false,
        };
    }

    protected function isWeeklyDue(QmInstance $instance, array $config, Carbon $now, Carbon $completedAt, int $interval): bool
    {
        if ($completedAt->diffInWeeks($now) < $interval) {
            return false;
        }

        if (!empty($config['days'])) {
            $today = strtolower($now->format('D'));
            $allowedDays = array_map('strtolower', $config['days']);
            return in_array($today, $allowedDays);
        }

        return true;
    }

    protected function isMonthlyDue(array $config, Carbon $now, Carbon $completedAt, int $interval): bool
    {
        if ($completedAt->diffInMonths($now) < $interval) {
            return false;
        }

        if (!empty($config['day_of_month'])) {
            return $now->day === (int)$config['day_of_month'];
        }

        return true;
    }

    /**
     * Create the next instance in the recurrence chain.
     */
    protected function createNextInstance(QmInstance $parentInstance, array $config): ?QmInstance
    {
        $template = $parentInstance->template;
        if (!$template || $template->status !== 'active') {
            return null;
        }

        $instanceService = new QmInstanceService();

        $dueAt = null;
        if (!empty($config['auto_due_hours'])) {
            $dueAt = now()->addHours((int)$config['auto_due_hours']);
        }

        $newInstance = $instanceService->createFromTemplate($template, [
            'team_id' => $parentInstance->team_id,
            'title' => $template->name . ' - ' . now()->format('d.m.Y H:i'),
            'parent_instance_id' => $parentInstance->id,
            'recurrence_config' => $config,
            'due_at' => $dueAt,
            'created_by_user_id' => $parentInstance->created_by_user_id,
        ]);

        return $newInstance;
    }

    /**
     * Set up recurrence on an instance.
     */
    public function setRecurrence(QmInstance $instance, array $config): QmInstance
    {
        $instance->update(['recurrence_config' => $config]);
        return $instance->fresh();
    }

    /**
     * Remove recurrence from an instance.
     */
    public function removeRecurrence(QmInstance $instance): QmInstance
    {
        $instance->update(['recurrence_config' => null]);
        return $instance->fresh();
    }

    /**
     * Get all recurring templates/instances for a team.
     */
    public function getRecurringForTeam(int $teamId): array
    {
        return QmInstance::forTeam($teamId)
            ->whereNotNull('recurrence_config')
            ->with(['template:id,name'])
            ->get()
            ->map(function (QmInstance $i) {
                return [
                    'id' => $i->id,
                    'uuid' => $i->uuid,
                    'title' => $i->title,
                    'template' => $i->template?->name,
                    'status' => $i->status,
                    'recurrence_config' => $i->recurrence_config,
                    'children_count' => $i->childInstances()->count(),
                    'created_at' => $i->created_at?->toISOString(),
                ];
            })
            ->toArray();
    }
}
