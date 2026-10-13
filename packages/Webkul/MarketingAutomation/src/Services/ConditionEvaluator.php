<?php

namespace Webkul\MarketingAutomation\Services;

class ConditionEvaluator
{
    /**
     * @param  array<int, array<string, mixed>>|null  $conditions
     * @param  array<string, mixed>  $attributes
     */
    public function matches(string $type, ?array $conditions, array $attributes): bool
    {
        $rows = array_values(array_filter($conditions ?? [], function ($condition): bool {
            return is_array($condition) && filled($condition['attribute'] ?? null);
        }));

        if ($rows === []) {
            return true;
        }

        $results = array_map(function (array $condition) use ($attributes): bool {
            return $this->compare(
                $attributes[$condition['attribute']] ?? null,
                (string) ($condition['operator'] ?? 'eq'),
                $condition['value'] ?? null,
            );
        }, $rows);

        if ($type === 'or') {
            return in_array(true, $results, true);
        }

        return ! in_array(false, $results, true);
    }

    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'eq' => (string) $actual === (string) $expected,
            'neq' => (string) $actual !== (string) $expected,
            'gt' => (float) $actual > (float) $expected,
            'gte' => (float) $actual >= (float) $expected,
            'lt' => (float) $actual < (float) $expected,
            'lte' => (float) $actual <= (float) $expected,
            'contains' => str_contains(strtolower((string) $actual), strtolower((string) $expected)),
            default => false,
        };
    }
}
