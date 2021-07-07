<?php

use WHMCS\Database\Capsule;

/**
 * Insert the clientId and his condition of sending invoice in the table mod_nfeio_custom_configs.
 *
 * @param string $clientId
 * @param string $newCond
 *
 * @return void|array
 */
function nfeio_save_client_issue_nfe_cond($clientId, $newCond) {
    try {
        $previousClientCond = Capsule::table('mod_nfeio_custom_configs')
            ->where('client_id', '=', $clientId)
            ->where('key', '=', 'issue_nfe_cond')
            ->get(['value'])[0]->value;

        // Verify if there was a change in the issue condition to make any modification in the database.
        if ($newCond !== $previousClientCond) {
            if ($previousClientCond == null) {
                Capsule::table('mod_nfeio_custom_configs')->insert([
                    'key' => 'issue_nfe_cond',
                    'client_id' => $clientId,
                    'value' => $newCond
                ]);
            } else {
                Capsule::table('mod_nfeio_custom_configs')
                    ->where('client_id', '=', $clientId)
                    ->where('key', '=', 'issue_nfe_cond')
                    ->update(['value' => $newCond]);
            }
        }
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Returns a <select> HTML which is used only by the AdminClientProfileTabFields hook
 * in the file hooks.php.
 *
 * @param string $clientId
 * @return array
 */
function nfeio_generate_invoice_cond_field($clientId) {
    if (nfeio_get_setting('issue_note_default_cond') !== 'Manualmente') {
        $conditions = Capsule::table('tbladdonmodules')->where('module', '=', 'nfeio')->where('setting', '=', 'issue_note_conditions')->get(['value'])[0]->value;
        $conditions = explode(',', $conditions);

        $previousClientCond = Capsule::table('mod_nfeio_custom_configs')
            ->where('client_id', '=', $clientId)
            ->where('key', '=', 'issue_nfe_cond')
            ->get(['value'])[0]->value;

        $select = '<select name="issue_note_cond" class="form-control select-inline">';

        // Sets the previous issue condition in the first index of array $conditions.
        // in order to the previous condition be showed in the client profile.
        if ($previousClientCond != null) {
            $previousCondKey = array_search($previousClientCond, $conditions);
            unset($conditions[$previousCondKey]);
            $select .= '<option value="' . $previousClientCond . '">' . $previousClientCond . '</option>';
        } else {
            $defaultCond = 'Seguir configuração do módulo NFE.io';
            $defaultCondKey = array_search($defaultCond, $conditions);
            unset($conditions[$defaultCondKey]);
            $select .= '<option value="Seguir configuração do módulo NFE.io">Seguir configuração do módulo NFE.io</option>';
        }

        foreach ($conditions as $cond) {
            $select .= '<option value="' . $cond . '">' . $cond . '</option>';
        }
        $select .= '</select>';

        return ['Emitir nota fiscal quando' => $select];
    }
}

/**
 * Returns the custom field displayed at the products/services tab of the client admin page.
 *
 * @param array $vars
 */
function nfeio_generate_client_service_descr_field($vars) {
    $userId = $_REQUEST['userid'];
    $serviceId = $vars['id'];

    // Create the key that identify this custom field in the mod_nfeio_custom_configs table.
    $key = 'service_custom_desc_' . $serviceId;

    $previousDescription = Capsule::table('mod_nfeio_custom_configs')
        ->where('client_id', '=', $userId)
        ->where('key', '=', $key)
        ->get(['value'])[0]->value;

    // Checks if there is a value for the current service.
    $value = $previousDescription !== null ? $previousDescription : '';

    return [
        'Descrição na nota fiscal' =>
        '<input type="text" name="custom_description" value="' . $value . '" size="25" class="form-control input-200">'
    ];
}

/**
 * Saves the description in the database.
 *
 * @param array $vars
 */
function nfeio_save_client_service_descr($vars) {
    $clientId = $vars['userid'];
    $serviceId = $vars['id'];
    $description = trim($vars['custom_description']);

    $tblKey = 'service_custom_desc_' . $serviceId;

    $previousDescriptionExists = Capsule::table('mod_nfeio_custom_configs')->where('client_id', '=', $clientId)->where('key', '=', $tblKey)->count() == 0;

    if ($description !== '') {
        // Checks if a description already exists.
        if ($previousDescriptionExists) {
            Capsule::table('mod_nfeio_custom_configs')->insert([
                'client_id' => $clientId,
                'key' => $tblKey,
                'value' => $description
            ]);
        } else {
            Capsule::table('mod_nfeio_custom_configs')
                ->where('client_id', '=', $clientId)
                ->where('key', '=', $tblKey)
                ->update(['value' => $description]);
        }
    } else {
        // If the description exists, and the $description is empty, it means the user wants to delete the description.
        if (!$previousDescriptionExists) {
            Capsule::table('mod_nfeio_custom_configs')
                ->where('client_id', '=', $clientId)
                ->where('key', '=', $tblKey)
                ->delete();
        }
    }
}
