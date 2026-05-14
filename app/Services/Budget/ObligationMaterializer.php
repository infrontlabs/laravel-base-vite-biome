<?php

namespace App\Services\Budget;

use App\Models\ObligationInstance;
use App\Models\ScheduledObligation;
use Carbon\CarbonImmutable;

class ObligationMaterializer
{
    public const HORIZON_DAYS = 90;

    /**
     * Idempotent: for each active obligation, generate any missing instances
     * between `last_materialized_through` (or anchor_date) and today + HORIZON_DAYS.
     */
    public function run(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::today();
        $horizon = $today->addDays(self::HORIZON_DAYS);
        $created = 0;

        $obligations = ScheduledObligation::query()
            ->where('is_active', true)
            ->get();

        foreach ($obligations as $obligation) {
            $start = $obligation->last_materialized_through
                ? CarbonImmutable::parse($obligation->last_materialized_through)->addDay()
                : CarbonImmutable::parse($obligation->anchor_date);

            if ($start->gt($horizon)) {
                continue;
            }

            $occurrences = $obligation->occurrencesBetween($start, $horizon);
            if ($occurrences->isEmpty()) {
                $obligation->forceFill(['last_materialized_through' => $horizon])->save();

                continue;
            }

            foreach ($occurrences as $date) {
                ObligationInstance::firstOrCreate(
                    [
                        'scheduled_obligation_id' => $obligation->id,
                        'due_date' => $date->toDateString(),
                    ],
                    [
                        'expected_amount_cents' => $obligation->amount_cents,
                        'status' => 'expected',
                    ],
                );
                $created++;
            }

            $obligation->forceFill(['last_materialized_through' => $horizon])->save();
        }

        return $created;
    }
}
