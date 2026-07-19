<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Counter;
use Illuminate\Database\QueryException;

/**
 * Generates concurrency-safe, human-readable document numbers using the
 * `counters` table. One generator, reused by every module that needs a
 * sequential reference number — patients, visits, invoices, etc. Only the
 * `type` and `prefix` change per caller.
 *
 * Format: {PREFIX}-{BRANCH_CODE}-{YEAR}-{6-digit sequence}
 * Example: PAT-MAIN-2026-000001, VIS-MAIN-2026-000145
 *
 * MUST be called from WITHIN the same DB transaction that persists the
 * record being numbered — if that transaction rolls back, the counter
 * increment rolls back with it, so no number is ever "burned" on a failed
 * save.
 */
class DocumentNumberGenerator
{
    public function generate(Branch $branch, string $type, string $prefix): string
    {
        $year = (int) now()->format('Y');

        // lockForUpdate() takes a row-level lock on the counter row, so once
        // it EXISTS, concurrent requests for the same branch/type/year are
        // serialized by the DB itself. The one edge case lockForUpdate can't
        // protect is the very first counter row for a branch/type/year: two
        // transactions could both find "no row" and both try to INSERT. The
        // unique(branch_id, type, year) constraint stops a duplicate from
        // being created; we just catch that and re-read with the lock.
        try {
            $counter = Counter::query()
                ->lockForUpdate()
                ->firstOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'type' => $type,
                        'year' => $year,
                    ],
                    ['last_number' => 0]
                );
        } catch (QueryException $e) {
            $counter = Counter::query()
                ->where('branch_id', $branch->id)
                ->where('type', $type)
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $counter->increment('last_number');

        $sequence = str_pad((string) $counter->last_number, 6, '0', STR_PAD_LEFT);

        return sprintf('%s-%s-%d-%s', $prefix, $branch->code, $year, $sequence);
    }
}
