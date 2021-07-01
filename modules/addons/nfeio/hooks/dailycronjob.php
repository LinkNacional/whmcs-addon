<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;

$params = nfeio_get_setting();
$data = getTodaysDate(false);
$dataAtual = toMySQLDate($data);
if (isset($params['issue_note_after_days']) && (int)$params['issue_note_after_days'] > 0) {
    foreach (Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $dataAtual])->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']) as $invoices) {
        foreach (Capsule::table('gofasnfeio')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoices->id)->get(['id', 'nfe_id', 'status', 'created_at', 'invoice_id', 'service_code', 'services_amount']) as $nfeio) {
            $datepaid = date('Ymd', strtotime($invoices->datepaid));
            $datepaid_to_issue_ = '-' . $params['issue_note_after_days'] . ' days';
            $datepaid_to_issue = date('Ymd', strtotime($datepaid_to_issue_));

            if ((float) $invoices->total > '0.00' and (int) $datepaid_to_issue >= (int) $datepaid) {
                nfeio_log('nfeio', 'dailycronjob', 'emitNFE', '', '');

                emitNFE($invoices,$nfeio);
            }
        }
    }
}
