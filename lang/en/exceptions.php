<?php

return [
    'appointment_conflict' => 'The doctor already has an appointment overlapping :date :start-:end.',
    'invalid_appointment_status' => 'Cannot transition appointment status from :current to :target.',
    'visit_not_editable' => 'The visit cannot be modified because its status is :status.',
    'visit_already_invoiced' => 'The visit already has an active invoice.',
    'invalid_invoice_status' => 'Cannot transition invoice status from :current to :target.',
    'payment_exceeds_balance' => 'The payment amount :amount exceeds the remaining invoice balance of :balance.',
    'insufficient_stock' => 'Warning: Low or insufficient stock for item :item at branch :branch. Requested: :requested, Available: :available.',
    'invalid_purchase_status' => 'Cannot transition purchase status from :current to :target.',
];
