<?php

if (!defined('WHMCS')) {
    exit();
}
$params = nfeio_get_setting();
$issueInvoiceCondition = nfeio_get_client_issue_nfe_cond($vars['invoiceid']);

// Uma fatura é paga
if ($issueInvoiceCondition === 'quando a fatura é paga') {
    $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

    if ((float) $invoice['total'] > 0.00 and $invoice['status'] != 'Draft') {
        $nfe_for_invoice = nfeio_get_local_nfe($vars['invoiceid'], ['id']);

        if (!$nfe_for_invoice['id']) {
            $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

            foreach ($invoice['items']['item'] as $value) {
                $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
            }

            $queue = nfeio_queue_nfe($vars['invoiceid'], true);
            if ($queue != 'success') {
                if ($vars['source'] === 'adminarea') {
                    header('Location: ' . nfeio_get_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                    exit;
                }
            } else {
                nfeio_log('nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'OK', '');
            }
        }
    }
} elseif ($issueInvoiceCondition === 'quando a fatura é gerada') {
    return;
} else {
    if (stripos($params['issue_note_default_cond'], 'Paga') && $vars['status'] != 'Draft' && (!$params['issue_note_after_days'] || 0 == $params['issue_note_after_days'] || stripos(strtolower($issueNfeUser),'paga'))) {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $vars['invoiceid']], false);

        if ((float) $invoice['total'] > 0.00 and $invoice['status'] != 'Draft') {
            $nfe_for_invoice = nfeio_get_local_nfe($vars['invoiceid'], ['id']);

            if (!$nfe_for_invoice['id']) {
                $client = localAPI('GetClientsDetails', ['clientid' => $invoice['userid'], 'stats' => false], false);

                foreach ($invoice['items']['item'] as $value) {
                    $line_items[] = $value['description']; //substr( $value['description'],  0, 100);
                }

                $queue = nfeio_queue_nfe($vars['invoiceid'], true);
                if ($queue != 'success') {
                    nfeio_log('nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'ERROR', '');
                    if ($vars['source'] === 'adminarea') {
                        header('Location: ' . nfeio_get_whmcs_admin_url() . 'invoices.php?action=edit&id=' . $vars['invoiceid'] . '&gnfe_error=Erro ao criar nota fiscal: ' . $queue);
                        exit;
                    }
                } else {
                    nfeio_log('nfeio', 'invoicepaid', $vars['invoiceid'], $queue, 'OK', '');
                }
            }
        }
    }
}