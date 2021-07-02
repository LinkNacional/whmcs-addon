<?php

defined('WHMCS') or exit;

add_hook('InvoiceCreation', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/dailycronjob.php';
    require_once __DIR__ . '/hooks/invoicecreation.php'; // Comentado, consigo criar e publicar uma fatura
});

 add_hook('InvoicePaid', 1, function ($vars) {
     require_once __DIR__ . '/functions.php';
     require_once __DIR__ . '/hooks/invoicepaid.php';
 });

add_hook('AdminInvoicesControlsOutput', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/admininvoicescontrolsoutput.php';
});

add_hook('InvoiceCancelled', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/invoicecancelled.php';
});

add_hook('DailyCronJob', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/dailycronjob.php';
});

add_hook('AfterCronJob', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/sendNFE.php';
    require_once __DIR__ . '/hooks/aftercronjob.php';
});

add_hook('ProductDelete', 1, function ($vars) {
    require_once __DIR__ . '/functions.php';
    require_once __DIR__ . '/hooks/productdelete.php';
});

add_hook('AdminClientProfileTabFields', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
    return require_once __DIR__ . '/hooks/customclientissueinvoice.php';
});

add_hook('AdminClientProfileTabFieldsSave', 1, function($vars) {
    require_once __DIR__ . '/functions.php';
    nfeio_save_client_issue_nfe_cond($vars['userid'], $_REQUEST['issue_note_cond']);
});
