<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

$issueNoteAfterDays = nfeio_get_setting('issue_note_after_days');
$initialDate = nfeio_get_setting('initial_date');

$data = getTodaysDate(false);
$currentDate = toMySQLDate($data);

if (isset($issueNoteAfterDays) && (int)$issueNoteAfterDays > 0) {
    foreach (Capsule::table('tblinvoices')->whereBetween('date', [$initialDate, $currentDate])->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']) as $invoices) {
        foreach (Capsule::table('nfeio')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoices->id)->get(['id', 'nfe_id', 'status', 'created_at', 'invoice_id', 'service_code', 'services_amount']) as $nfeio) {
            $datepaid = date('Ymd', strtotime($invoices->datepaid));
            $datepaid_to_issue_ = '-' . $issueNoteAfterDays . ' days';
            $datepaid_to_issue = date('Ymd', strtotime($datepaid_to_issue_));

            if ((float) $invoices->total > '0.00' and (int) $datepaid_to_issue >= (int) $datepaid) {
                nfeio_log('nfeio', 'dailycronjob', 'nfeio_issue_note_to_nfe', '', '');

                nfeio_issue_note_to_nfe($invoices,$nfeio);
            }
        }
    }
}
