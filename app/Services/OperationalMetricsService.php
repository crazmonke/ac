<?php

namespace App\Services;

use App\Models\OperationalMetric;

class OperationalMetricsService
{
    public function log(
        string $eventName,
        ?int $userId = null,
        ?int $complexId = null,
        ?int $buildingId = null,
        array $payload = []
    ): void {
        try {
            OperationalMetric::query()->create([
                'event_name' => $eventName,
                'user_id' => $userId,
                'complex_id' => $complexId,
                'building_id' => $buildingId,
                'payload' => $payload ?: null,
            ]);
        } catch (\Throwable) {
            // Metrics logging must not block product flow.
        }
    }
}
