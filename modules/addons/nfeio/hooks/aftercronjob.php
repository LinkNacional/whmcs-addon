<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

$params = nfeio_get_setting();
$currentDate = date('Y-m-d H:i:s');

if (Capsule::table('tbladdonmodules')->where('setting','=','last_cron')->count() == 0) {
    Capsule::table('tbladdonmodules')->insert(['module' => 'nfeio', 'setting' => 'last_cron', 'value' => $currentDate]);
} else {
    Capsule::table('tbladdonmodules')->where('setting','=','last_cron')->update(['value' => $currentDate]);
}

if (!isset($params['issue_note_after_days']) || $params['issue_note_after_days'] <= 0) {
    foreach (Capsule::table('nfeio')->orderBy('id', 'desc')->where('status', '=', 'Waiting')->get(['id', 'invoice_id', 'services_amount']) as $waiting) {
        nfeio_log('nfeio', 'aftercronjob - checktablenfeio', '', $waiting,'', '');

        $data = getTodaysDate(false);
        $currentDate = toMySQLDate($data);

        if ($params['issue_note_default_cond'] !== 'Manualmente') {
            $getQuery = Capsule::table('tblinvoices')->whereBetween('date', [$params['initial_date'], $currentDate])->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
            nfeio_log('nfeio', 'aftercronjob - getQuery', ['date' => [$params['initial_date'], $currentDate], 'where' => 'id=' . $waiting->invoice_id], $getQuery,'', '');
        } else {
            $getQuery = Capsule::table('tblinvoices')->where('id', '=', $waiting->invoice_id)->get(['id', 'userid', 'total']);
            nfeio_log('nfeio', 'aftercronjob - getQuery', 'id=' . $waiting->invoice_id, $getQuery,'', '');
        }

        foreach ($getQuery as $invoices) {
            emitNFE($invoices,$waiting);
        }
    }
}
