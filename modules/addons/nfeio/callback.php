<?php

require_once __DIR__ . '/../../../init.php';
use WHMCS\Database\Capsule;

$post = json_decode(file_get_contents('php://input'), true);
if ($post) {
    require_once __DIR__ . '/functions.php';
    $params = nfeio_get_setting();

    //verificar o ambiente
    if ($params['development_env'] == 'on' && $post['environment'] == 'Production') {
        return '';
    } elseif ($params['development_env'] == '' && $post['environment'] == 'Development') {
        return '';
    }
    //fim verificar o ambiente

    //verificar se a nfe existe na tabela
    if (Capsule::table('nfeio')->where('nfe_id', '=', $post['id'])->count() == 0 ) {
        return '';
    }
    //fim verificar se a nfe existe na tabela

    $params = [];
    foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'nfeio')->get(['setting', 'value']) as $settings) {
        $params[$settings->setting] = $settings->value;
    }
    foreach (Capsule::table('nfeio')->where('nfe_id', '=', $post['id'])->
    get(['id', 'invoice_id', 'user_id', 'nfe_id', 'status', 'services_amount', 'environment', 'flow_status', 'pdf', 'created_at', 'updated_at']) as $key => $value) {
        $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
    }
    $nfe = $nfe_for_invoice['0'];

    if ((string) $nfe['nfe_id'] === (string) $post['id'] and $nfe['status'] !== (string) $post['status']) {
        $new_nfe = [
            'invoice_id' => $nfe['invoice_id'],
            'user_id' => $nfe['user_id'],
            'nfe_id' => $nfe['nfe_id'],
            'status' => $post['status'],
            'services_amount' => $nfe['services_amount'],
            'environment' => $nfe['environment'],
            'flow_status' => $post['flowStatus'],
            'pdf' => $nfe['pdf'],
            'created_at' => $nfe['created_at'],
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $save_nfe = Capsule::table('nfeio')->where('nfe_id', '=', $post['id'])->update($new_nfe);
        } catch (\Exception $e) {
            $e->getMessage();
        }
    }
    $invoice_id = Capsule::table('nfeio')->where('nfe_id', '=', $post['id'])->get(['invoice_id'])[0];

    if ($post['status'] == 'Error') {
        nfeio_log('nfeio', 'callback', '', $post, 'ERROR', '');
    } else {
        nfeio_log('nfeio', 'callback', '', $post, 'OK', '');
    }
}
