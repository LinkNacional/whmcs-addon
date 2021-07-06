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

echo '<pre>';
print_r(localApi('GetInvoice', ['invoiceid' => $invoiceId])['items']);
echo '</pre><hr>';

// $invoiceItemsRelidStdClass = Capsule::table('tblinvoiceitems')->where('invoiceid', '=', $invoiceId)->get(['relid']);
//
// $invoiceItemsRelid = array();
// for ($index=0; $index < count($invoiceItemsRelidStdClass); $index++) {
    // $invoiceItemsRelid[] = $invoiceItemsRelidStdClass[$index]->relid;
// }
//

$clientId = 2;
$serviceId = 1054;
$invoiceId = 815;

$invoice = localApi('GetInvoice', ['invoiceid' => $invoiceId]);

echo '<pre>';
print_r($invoice);
echo '</pre><hr>';

foreach ($invoice['items']['item'] as $value) {
    $key = 'service_custom_desc_' . $value['relid'];

    $line_items[] = Capsule::table('mod_nfeio_custom_configs')
                    ->where('client_id', '=', $clientId)
                    ->where('key', '=', $key)
                    ->get(['value'])[0]->value . '|' . $value['description'];
}

echo '<pre>';
print_r($line_items);
echo '</pre><hr>';
