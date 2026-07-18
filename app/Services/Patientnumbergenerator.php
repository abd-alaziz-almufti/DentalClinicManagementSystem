<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Counter;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Generates concurrency-safe, human-readable document numbers using the
 * `counters` table. Reused by any module that needs a sequential reference
 * number (patients now; invoices, visits, appointments later) — same
 * pattern, different `type` key.
 *
 * Format: {PREFIX}-{BRANCH_CODE}-{YEAR}-{6-digit sequence}
 * Example: PAT-MAIN-2026-000001
 */
class PatientNumberGenerator
{
    private const TYPE = 'patient';
    private const PREFIX = 'PAT';

    /**
     * Reserve and return the next patient number for a branch.
     *
     * Must be called from WITHIN the same DB transaction that persists the
     * Patient record — if that transaction rolls back, the counter
     * increment rolls back with it, so no number is ever "burned" on a
     * failed save.
     */
    public function generate(Branch $branch): string
    {
        $year = (int) now()->format('Y');

        // lockForUpdate() takes a row-level lock on the counter row, so once
        // it EXISTS, two concurrent requests for the same branch/type/year
        // are serialized by the DB itself. The one edge case lockForUpdate
        // can't protect is the very first counter row for a branch/year:
        // two transactions could both find "no row" and both try to INSERT.
        // The unique(branch_id, type, year) constraint stops a duplicate
        // from being created; we just need to catch that and re-read.
        try {
            $counter = Counter::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'type' => self::TYPE,
                        'year' => $year,
                    ],
                    ['last_number' => 0]
                );
        } catch (QueryException $e) {
            // Someone else won the race and inserted it first — just fetch
            // it with the lock now that the row exists.
            $counter = Counter::query()
                ->where('branch_id', $branch->id)
                ->where('type', self::TYPE)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $counter->increment('last_number');

        $sequence = str_pad((string) $counter->last_number, 6, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%d-%s', self::PREFIX, $branch->code, $year, $sequence);
    }
}
