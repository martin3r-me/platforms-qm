<?php

namespace Platform\Qm\Services;

use Platform\Qm\Models\QmInstance;
use Platform\Qm\Models\QmSectionField;

class QmBehaviorRuleService
{
    /**
     * Evaluate which fields should be visible for an instance.
     * Returns an array of field_definition_ids that should be shown.
     */
    public function evaluateVisibleFields(QmInstance $instance): array
    {
        $snapshot = $instance->snapshot_data ?? [];
        $visibleFieldIds = [];

        foreach ($snapshot['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $field) {
                $rule = $field['behavior_rule'] ?? 'always';
                $config = $field['behavior_config'] ?? [];

                $visible = match ($rule) {
                    'always' => true,
                    'conditional' => $this->evaluateConditional($instance, $config),
                    'random' => $this->evaluateRandom($config),
                    'time_based' => $this->evaluateTimeBased($config),
                    'risk_based' => $this->evaluateRiskBased($instance, $config),
                    default => true,
                };

                if ($visible) {
                    $visibleFieldIds[] = $field['field_definition_id'];
                }
            }
        }

        return $visibleFieldIds;
    }

    /**
     * Conditional: show field if another field's value matches a condition.
     * Config: { field_uuid: "...", operator: ">=", value: 100 }
     */
    protected function evaluateConditional(QmInstance $instance, array $config): bool
    {
        if (empty($config['field_uuid']) || !isset($config['operator']) || !isset($config['value'])) {
            return true;
        }

        $response = $instance->responses()
            ->whereHas('fieldDefinition', function ($q) use ($config) {
                $q->where('uuid', $config['field_uuid']);
            })
            ->first();

        if (!$response || $response->value === null) {
            return false;
        }

        $actualValue = is_array($response->value) ? ($response->value['value'] ?? $response->value) : $response->value;

        return match ($config['operator']) {
            '=', '==' => $actualValue == $config['value'],
            '!=' => $actualValue != $config['value'],
            '>' => $actualValue > $config['value'],
            '>=' => $actualValue >= $config['value'],
            '<' => $actualValue < $config['value'],
            '<=' => $actualValue <= $config['value'],
            'in' => is_array($config['value']) && in_array($actualValue, $config['value']),
            default => true,
        };
    }

    /**
     * Random: show field with a given sampling rate (percentage).
     * Config: { sampling_rate: 25 } → 25% chance
     */
    protected function evaluateRandom(array $config): bool
    {
        $rate = (int) ($config['sampling_rate'] ?? 100);
        return mt_rand(1, 100) <= $rate;
    }

    /**
     * Time-based: show field only on certain days/times.
     * Config: { days: ["mon","wed"], time_from: "08:00", time_to: "17:00" }
     */
    protected function evaluateTimeBased(array $config): bool
    {
        $now = now();

        if (!empty($config['days'])) {
            $today = strtolower($now->format('D'));
            $allowedDays = array_map('strtolower', $config['days']);
            if (!in_array($today, $allowedDays)) {
                return false;
            }
        }

        if (!empty($config['time_from'])) {
            $timeFrom = \Carbon\Carbon::parse($config['time_from']);
            if ($now->format('H:i') < $timeFrom->format('H:i')) {
                return false;
            }
        }

        if (!empty($config['time_to'])) {
            $timeTo = \Carbon\Carbon::parse($config['time_to']);
            if ($now->format('H:i') > $timeTo->format('H:i')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Risk-based: increase field frequency after recent deviations.
     * Config: { base_rate: 10, escalation_rate: 50, lookback_days: 30 }
     */
    protected function evaluateRiskBased(QmInstance $instance, array $config): bool
    {
        $baseRate = (int) ($config['base_rate'] ?? 10);
        $escalationRate = (int) ($config['escalation_rate'] ?? 50);
        $lookbackDays = (int) ($config['lookback_days'] ?? 30);

        // Check if there were deviations in the same template recently
        $hasRecentDeviations = QmInstance::where('team_id', $instance->team_id)
            ->where('qm_template_id', $instance->qm_template_id)
            ->where('created_at', '>=', now()->subDays($lookbackDays))
            ->where('id', '!=', $instance->id)
            ->whereHas('responses', function ($q) {
                $q->where('is_deviation', true);
            })
            ->exists();

        $rate = $hasRecentDeviations ? $escalationRate : $baseRate;

        return mt_rand(1, 100) <= $rate;
    }
}
