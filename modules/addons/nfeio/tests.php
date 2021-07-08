<?php

require_once __DIR__ . '/functions.php';

use WHMCS\Database\Capsule;

$clientId = 2;
$serviceId = 1054;
// $invoiceId = 710;
$invoiceId = 815;

// Capsule::table('tblinvoiceitems')
//     ->where('invoiceid', '=', $invoiceId)
//     ->where('key', '=', $key)
//     ->get(['value'])[0]->value;

// echo '<pre>';
// print_r(localApi('GetInvoice', ['invoiceid' => $invoiceId]));
// echo '</pre><hr>';

// try {
//     $pdo = Capsule::connection()->getPdo();
//     $pdo->beginTransaction();

//     // tblhosting.id
//     $statement = $pdo->prepare(
//         'SELECT tblinvoiceitems.relid FROM tblhosting INNER JOIN tblinvoiceitems ON tblinvoiceitems.relid = tblhosting.id
//         WHERE tblinvoiceitems.invoiceid = :invoiceId
//     ');

//     $statement->execute([':invoiceId' => $invoiceId]);
//     $result = $statement->fetchAll();

//     echo '<pre>';
//     print_r('TRY: ');
//     echo '</pre><hr>';
//     echo '<pre>';
//     print_r($result);
//     echo '</pre><hr>';
//     $pdo->commit();
// } catch (\Exception $e) {
//     nfeio_log('nfeio', 'nfeio_create_tables: tblproductcode', '', $e->getMessage(), '');
//     echo '<pre>';
//     print_r('CATCH: ');
//     echo '</pre><hr>';
//     echo '<pre>';
//     print_r($e->getMessage());
//     echo '</pre><hr>';
//     $pdo->rollBack();
// }

// echo '<pre>';
// print_r(localApi('GetInvoice', ['invoiceid' => $invoiceId])['items']);
// echo '</pre><hr>';

// $invoiceItemsRelidStdClass = Capsule::table('tblinvoiceitems')->where('invoiceid', '=', $invoiceId)->get(['relid']);
//
// $invoiceItemsRelid = array();
// for ($index=0; $index < count($invoiceItemsRelidStdClass); $index++) {
// $invoiceItemsRelid[] = $invoiceItemsRelidStdClass[$index]->relid;
// }
//

$clientId = 2;
$invoiceId = 1001;

$invoice = localApi('GetInvoice', ['invoiceid' => $invoiceId]);

foreach ($invoice['items']['item'] as $item) {
	$key = 'service_custom_desc_' . $item['relid'];

	$customDescrip = Capsule::table('mod_nfeio_custom_configs')
		->where('client_id', '=', $clientId)
		->where('key', '=', $key)
		->get(['value'])[0]->value;

	if ($item['type'] === 'Hosting' && !empty($customDescrip)) {
		$line_items[] = $item['description'] . ' | ' . $customDescrip;
	} else {
		$line_items[] = $item['description'];
	}
}

// echo '<pre>';
// print_r($invoice);
// echo '</pre><hr>';

// echo '<pre>';
// print_r($line_items);
// echo '</pre><hr>';

// ----------------------------------------------------------------
require_once __DIR__ . '/issue_nfe.php';
$initialDate = nfeio_get_setting('initial_date');

$data = getTodaysDate(false);
$currentDate = toMySQLDate($data);

$invoices = Capsule::table('tblinvoices')->where('id', '=', $invoiceId)->where('status', '=', 'Paid')->get(['id', 'userid', 'datepaid', 'total']);
$nfeio = Capsule::table('nfeio')->where('status', '=', 'Waiting')->where('invoice_id', '=', $invoiceId)->get(['id', 'nfe_id', 'status', 'created_at', 'invoice_id', 'service_code', 'services_amount']);
nfeio_issue_note_to_nfe($invoices[0], $nfeio[0]);
