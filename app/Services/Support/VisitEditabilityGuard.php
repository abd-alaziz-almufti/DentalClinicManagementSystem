<?php

namespace App\Services\Support;

use App\Exceptions\VisitNotEditableException;
use App\Models\Visit;

/**
 * Shared business rule: a Visit's clinical detail (services performed,
 * dental chart entries) can only be added/changed/removed while the visit
 * is still open/in_progress. Once completed, it's immutable — used by
 * every service that mutates visit-scoped clinical records, so the rule
 * lives in exactly one place.
 */
class VisitEditabilityGuard
{
    private const EDITABLE_STATUSES = ['open', 'in_progress'];

    /**
     * @throws VisitNotEditableException
     */
    public static function assertEditable(Visit $visit): void
    {
        if (! in_array($visit->status, self::EDITABLE_STATUSES, true)) {
            throw VisitNotEditableException::forStatus($visit->status);
        }

        if ($visit->has_active_invoice) {
            throw VisitNotEditableException::forActiveInvoice();
        }
    }
}
