<?php

use WHMCS\Database\Capsule;

if (
    nfeio_get_setting('issue_note_default_cond') !== 'Manualmente'
    && Capsule::schema()->hasTable('mod_nfeio_custom_configs')
) {
    return ['Emitir nota fiscal quando' => nfeio_show_issue_invoice_conds($vars['userid'])];
}
