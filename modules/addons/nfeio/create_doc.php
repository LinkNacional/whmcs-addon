<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/functions.php';

use WHMCS\Database\Capsule;

isset($_SESSION['uid']) or exit('Usuário não autenticado.');

$invoiceId = $_GET['invoice_id'];
$fileType = $_GET['type'];
$userId = $_SESSION['uid'];

nfeio_user_owns_invoice($userId, $invoice_id) or exit('Fatura não pertence ao atual usuário.');

// Gets nfe id by the invoice id.
$nfeId = Capsule::table('nfeio')
    ->where('invoice_id', '=', $invoice_id)
    ->get(['nfe_id'])[0]->nfe_id;

$document = nfeio_get_doc($nfeId, $fileType);
header('Content-Type: application/' . $type);
echo $document;

function nfeio_get_doc($nfeId, $type) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices/' . $nfeId . '/' . $type,
        CURLOPT_HTTPHEADER => [
            'Content-type: application/' . $type,
            'Authorization: ' . nfeio_get_setting('api_key')
        ],
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
}
