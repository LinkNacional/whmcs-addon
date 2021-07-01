<?php

use WHMCS\Database\Capsule;

try {
    $delete = Capsule::table('tblproductcode')->where('product_id', '=', $vars['pid'])->delete();
    nfeio_log('nfeio', 'productdelete', 'product_id=' . $vars['pid'], $delete, 'OK', '');
} catch (Exception $e) {
    nfeio_log('nfeio', 'productdelete', 'product_id=' . $vars['pid'], $e->getMessage(), 'ERROR', '');
}
