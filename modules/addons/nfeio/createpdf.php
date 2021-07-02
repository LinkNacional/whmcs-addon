<?php

$nfe_id = $_GET['nfe_id'];

require_once __DIR__.'/../../../init.php';
use WHMCS\Database\Capsule;

        $row = Capsule::table('nfeio')->where('id', '=', $nfe_id)->get(['invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at', 'id']);
        $nfe = $row[0];
        if ((string) $nfe->status === (string) 'Issued') {
            $nfe_for_invoice = nfeio_pdf_nfe($nfe->nfe_id);
            echo $nfe_for_invoice;
        } else {
            echo 'Not Found';
        }

    exit();

    function nfeio_pdf_nfe($nf)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, 'https://api.nfe.io/v1/companies/'.nfeio_get_setting('company_id').'/serviceinvoices/'.$nf.'/pdf');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/pdf', 'Authorization: '.nfeio_get_setting('api_key')]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        header('Content-type: application/pdf');
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
    function nfeio_get_setting($set = false)
    {
        $setting = [];
        foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'nfeio')->get(['setting', 'value']) as $settings) {
            $setting[$settings->setting] = $settings->value;
        }
        if ($set) {
            return $setting[$set];
        }

        return $setting;
    }
