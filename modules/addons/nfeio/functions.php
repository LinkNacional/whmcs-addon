<?php

if (!defined('WHMCS')) {
    exit();
}
use WHMCS\Database\Capsule;



if (!function_exists('nfeio_get_setting')) {
    /**
     * Get the module's data from the table tbladdonmodules.
     *
     * @param string|boolean $set
     * @return array|string
     */
    function nfeio_get_setting($getOne = false) {
        try {
            if ($getOne === false) {
                $setting = [];

                foreach (Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->get(['setting', 'value']) as $settings) {
                    $setting[$settings->setting] = $settings->value;
                }

                return $setting;
            }

            return Capsule::table('tbladdonmodules')
                ->where('module', '=', 'gofasnfeio')
                ->where('setting', '=', $getOne)
                ->get(['value'])[0]->value;

        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_log')) {
    /**
     * Checks if the module log is active, if so, insert the params in a log.
     *
     * @param string $module The name of the module
     * @param string $action The name of the action being performed
     * @param string|array $requestString The input parameters for the API call
     * @param string|array $responseData The response data from the API call
     * @param string|array $processedData The resulting data after any post processing (eg. json decode, xml decode, etc...)
     * @param array $replaceVars An array of strings for replacement
     */
    function nfeio_log($module, $action, $requestString, $responseData, $processedData, $replaceVars = []) {
        if (nfeio_get_setting('debug') === 'on') {
            nfeio_log($module, $action, $requestString, $responseData, $processedData, $replaceVars);
        }
    }
}

if (!function_exists('nfeio_get_customer')) {
    /**
     * Returns the client data as customer data based on his id.
     *
     * @param string $user_id
     * @param array $client
     *
     * @return array
     */
    function nfeio_get_customer($user_id, $client) {
        try {
            // Get the custom fields ids.
            $CPF_id = nfeio_get_setting('cpf_field');
            $CNPJ_id = nfeio_get_setting('cnpj_field');
            $insc_municipal_id = nfeio_get_setting('municipal_inscri');

            $insc_customfield_value = 'NF';
            // Inscrição municipal
            if ($insc_municipal_id != 0) {
                $insc_customfield_value = Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', '=', $insc_municipal_id)
                    ->where('relid', '=', $user_id)
                    ->get(['value'])[0]->value;
            }
            // CPF
            if ($CPF_id != 0) {
                $cpf_customfield_value = Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', '=', $CPF_id)
                    ->where('relid', '=', $user_id)
                    ->get(['value'])[0]->value;
                $cpf_customfield_value = preg_replace('/[^0-9]/', '', $cpf_customfield_value);
            }
            // CNPJ
            if ($CNPJ_id != 0) {
                $cnpj_customfield_value = Capsule::table('tblcustomfieldsvalues')
                    ->where('fieldid', '=', $CNPJ_id)
                    ->where('relid', '=', $user_id)
                    ->get(['value'])[0]->value;
                $cnpj_customfield_value = preg_replace('/[^0-9]/', '', $cnpj_customfield_value);
            }

            nfeio_log('nfeio', 'nfeio_get_customer-cpf', $cpf_customfield_value, '', '', '');
            nfeio_log('nfeio', 'nfeio_get_customer-cnpj', $cnpj_customfield_value, '', '', '');
            nfeio_log('nfeio', 'nfeio_get_customer-municipal', $insc_customfield_value, '', '', '');

            // Cliente possui CPF e CNPJ

            $cpfCustomFiledValueLength = strlen($cpf_customfield_value);

            // CPF com 1 nº a menos, adiciona 0 antes do documento
            if ($cpfCustomFiledValueLength === 10) {
                $cpf = '0' . $cpf_customfield_value;
            }
            // CPF com 11 dígitos
            elseif ($cpfCustomFiledValueLength === 11) {
                $cpf = $cpf_customfield_value;
            }
            // CNPJ no campo de CPF com um dígito a menos
            elseif ($cpfCustomFiledValueLength === 13) {
                $cpf = false;
                $cnpj = '0' . $cpf_customfield_value;
            }
            // CNPJ no campo de CPF
            elseif ($cpfCustomFiledValueLength === 14) {
                $cpf = false;
                $cnpj = $cpf_customfield_value;
            }
            // cadastro não possui CPF
            elseif (!$cpf_customfield_value || $cpfCustomFiledValueLength !== 10 || $cpfCustomFiledValueLength !== 11 || $cpfCustomFiledValueLength != 13 || $cpfCustomFiledValueLength !== 14) {
                $cpf = false;
            }

            $cnpjCustomFieldValueLength = strlen($cnpj_customfield_value);

            // CNPJ com 1 nº a menos, adiciona 0 antes do documento
            if ($cnpjCustomFieldValueLength === 13) {
                $cnpj = '0' . $cnpj_customfield_value;
            }
            // CNPJ com nº de dígitos correto
            elseif ($cnpjCustomFieldValueLength === 14) {
                $cnpj = $cnpj_customfield_value;
            }
            // Cliente não possui CNPJ
            elseif (!$cnpj_customfield_value and $cnpjCustomFieldValueLength !== 14 and $cnpjCustomFieldValueLength !== 13 and $cpfCustomFiledValueLength !== 13 and $cpfCustomFiledValueLength !== 14) {
                $cnpj = false;
            }
            if (($cpf and $cnpj) or (!$cpf and $cnpj)) {
                $custumer['doc_type'] = 2;
                $custumer['document'] = $cnpj;
                if ($client['companyname']) {
                    $custumer['name'] = $client['companyname'];
                } elseif (!$client['companyname']) {
                    $custumer['name'] = $client['firstname'] . ' ' . $client['lastname'];
                }
            } elseif ($cpf and !$cnpj) {
                $custumer['doc_type'] = 1;
                $custumer['document'] = $cpf;
                $custumer['name'] = $client['firstname'] . ' ' . $client['lastname'];
            }
            if ($insc_customfield_value != 'NF') {
                $custumer['municipal_inscri'] = $insc_customfield_value;
            }
            if (!$cpf and !$cnpj) {
                $error = 'CPF e/ou CNPJ ausente.';
            }
            if (!$error) {
                return $custumer;
            }
            if ($error) {
                return $custumer['error'] = $error;
            }
        } catch (Exception $e) {
            nfeio_log('nfeio', 'nfeio_get_customer', '', $e->getMessage(), '');
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_get_custom_fields_dropdown')) {
    /**
     * Returns a array containing the fields of the tblcustomfields
     * and the keys of the array are the rows id of that table.
     *
     * @return array
     */
    function nfeio_get_custom_fields_dropdown() {
        try {
            $customfields_array = [];

            foreach (Capsule::table('tblcustomfields')->where('type', '=', 'client')->get(['fieldname', 'id']) as $customfield) {
                $customfields_array[] = $customfield;
            }

            $customfields = json_decode(json_encode($customfields_array), true);

            if (!$customfields) {
                $dropFieldArray = ['0' => 'database error'];
            } elseif (count($customfields) >= 1) {
                $dropFieldArray = ['0' => 'selecione um campo'];

                foreach ($customfields as $key => $value) {
                    $dropFieldArray[$value['id']] = $value['fieldname'];
                }
            } else {
                $dropFieldArray = ['0' => 'nothing to show'];
            }

            return $dropFieldArray;
        } catch (Exception $e) {
            nfeio_log('nfeio', 'nfeio_get_custom_fields_dropdown', '', $e->getMessage(), '');
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_country_code')) {
    /**
     * Returns a value of the array $array if its key matches with the param $country.
     *
     * @param string
     * @return string
     */
    function nfeio_country_code($country) {
        $array = ['BD' => 'BGD', 'BE' => 'BEL', 'BF' => 'BFA', 'BG' => 'BGR', 'BA' => 'BIH', 'BB' => 'BRB',
        'WF' => 'WLF', 'BL' => 'BLM', 'BM' => 'BMU', 'BN' => 'BRN', 'BO' => 'BOL', 'BH' => 'BHR', 'BI' => 'BDI',
        'BJ' => 'BEN', 'BT' => 'BTN', 'JM' => 'JAM', 'BV' => 'BVT', 'BW' => 'BWA', 'WS' => 'WSM', 'BQ' => 'BES',
        'BR' => 'BRA', 'BS' => 'BHS', 'JE' => 'JEY', 'BY' => 'BLR', 'BZ' => 'BLZ', 'RU' => 'RUS', 'RW' => 'RWA',
        'RS' => 'SRB', 'TL' => 'TLS', 'RE' => 'REU', 'TM' => 'TKM', 'TJ' => 'TJK', 'RO' => 'ROU', 'TK' => 'TKL',
        'GW' => 'GNB', 'GU' => 'GUM', 'GT' => 'GTM', 'GS' => 'SGS', 'GR' => 'GRC', 'GQ' => 'GNQ', 'GP' => 'GLP',
        'JP' => 'JPN', 'GY' => 'GUY', 'GG' => 'GGY', 'GF' => 'GUF', 'GE' => 'GEO', 'GD' => 'GRD', 'GB' => 'GBR',
        'GA' => 'GAB', 'SV' => 'SLV', 'GN' => 'GIN', 'GM' => 'GMB', 'GL' => 'GRL', 'GI' => 'GIB', 'GH' => 'GHA',
        'OM' => 'OMN', 'TN' => 'TUN', 'JO' => 'JOR', 'HR' => 'HRV', 'HT' => 'HTI', 'HU' => 'HUN', 'HK' => 'HKG',
        'HN' => 'HND', 'HM' => 'HMD', 'VE' => 'VEN', 'PR' => 'PRI', 'PS' => 'PSE', 'PW' => 'PLW', 'PT' => 'PRT',
        'SJ' => 'SJM', 'PY' => 'PRY', 'IQ' => 'IRQ', 'PA' => 'PAN', 'PF' => 'PYF', 'PG' => 'PNG', 'PE' => 'PER',
        'PK' => 'PAK', 'PH' => 'PHL', 'PN' => 'PCN', 'PL' => 'POL', 'PM' => 'SPM', 'ZM' => 'ZMB', 'EH' => 'ESH',
        'EE' => 'EST', 'EG' => 'EGY', 'ZA' => 'ZAF', 'EC' => 'ECU', 'IT' => 'ITA', 'VN' => 'VNM', 'SB' => 'SLB',
        'ET' => 'ETH', 'SO' => 'SOM', 'ZW' => 'ZWE', 'SA' => 'SAU', 'ES' => 'ESP', 'ER' => 'ERI', 'ME' => 'MNE',
        'MD' => 'MDA', 'MG' => 'MDG', 'MF' => 'MAF', 'MA' => 'MAR', 'MC' => 'MCO', 'UZ' => 'UZB', 'MM' => 'MMR',
        'ML' => 'MLI', 'MO' => 'MAC', 'MN' => 'MNG', 'MH' => 'MHL', 'MK' => 'MKD', 'MU' => 'MUS', 'MT' => 'MLT',
        'MW' => 'MWI', 'MV' => 'MDV', 'MQ' => 'MTQ', 'MP' => 'MNP', 'MS' => 'MSR', 'MR' => 'MRT', 'IM' => 'IMN',
        'UG' => 'UGA', 'TZ' => 'TZA', 'MY' => 'MYS', 'MX' => 'MEX', 'IL' => 'ISR', 'FR' => 'FRA', 'IO' => 'IOT',
        'SH' => 'SHN', 'FI' => 'FIN', 'FJ' => 'FJI', 'FK' => 'FLK', 'FM' => 'FSM', 'FO' => 'FRO', 'NI' => 'NIC',
        'NL' => 'NLD', 'NO' => 'NOR', 'NA' => 'NAM', 'VU' => 'VUT', 'NC' => 'NCL', 'NE' => 'NER', 'NF' => 'NFK',
        'NG' => 'NGA', 'NZ' => 'NZL', 'NP' => 'NPL', 'NR' => 'NRU', 'NU' => 'NIU', 'CK' => 'COK', 'XK' => 'XKX',
        'CI' => 'CIV', 'CH' => 'CHE', 'CO' => 'COL', 'CN' => 'CHN', 'CM' => 'CMR', 'CL' => 'CHL', 'CC' => 'CCK',
        'CA' => 'CAN', 'CG' => 'COG', 'CF' => 'CAF', 'CD' => 'COD', 'CZ' => 'CZE', 'CY' => 'CYP', 'CX' => 'CXR',
        'CR' => 'CRI', 'CW' => 'CUW', 'CV' => 'CPV', 'CU' => 'CUB', 'SZ' => 'SWZ', 'SY' => 'SYR', 'SX' => 'SXM',
        'KG' => 'KGZ', 'KE' => 'KEN', 'SS' => 'SSD', 'SR' => 'SUR', 'KI' => 'KIR', 'KH' => 'KHM', 'KN' => 'KNA',
        'KM' => 'COM', 'ST' => 'STP', 'SK' => 'SVK', 'KR' => 'KOR', 'SI' => 'SVN', 'KP' => 'PRK', 'KW' => 'KWT',
        'SN' => 'SEN', 'SM' => 'SMR', 'SL' => 'SLE', 'SC' => 'SYC', 'KZ' => 'KAZ', 'KY' => 'CYM', 'SG' => 'SGP',
        'SE' => 'SWE', 'SD' => 'SDN', 'DO' => 'DOM', 'DM' => 'DMA', 'DJ' => 'DJI', 'DK' => 'DNK', 'VG' => 'VGB',
        'DE' => 'DEU', 'YE' => 'YEM', 'DZ' => 'DZA', 'US' => 'USA', 'UY' => 'URY', 'YT' => 'MYT', 'UM' => 'UMI',
        'LB' => 'LBN', 'LC' => 'LCA', 'LA' => 'LAO', 'TV' => 'TUV', 'TW' => 'TWN', 'TT' => 'TTO', 'TR' => 'TUR',
        'LK' => 'LKA', 'LI' => 'LIE', 'LV' => 'LVA', 'TO' => 'TON', 'LT' => 'LTU', 'LU' => 'LUX', 'LR' => 'LBR',
        'LS' => 'LSO', 'TH' => 'THA', 'TF' => 'ATF', 'TG' => 'TGO', 'TD' => 'TCD', 'TC' => 'TCA', 'LY' => 'LBY',
        'VA' => 'VAT', 'VC' => 'VCT', 'AE' => 'ARE', 'AD' => 'AND', 'AG' => 'ATG', 'AF' => 'AFG', 'AI' => 'AIA',
        'VI' => 'VIR', 'IS' => 'ISL', 'IR' => 'IRN', 'AM' => 'ARM', 'AL' => 'ALB', 'AO' => 'AGO', 'AQ' => 'ATA',
        'AS' => 'ASM', 'AR' => 'ARG', 'AU' => 'AUS', 'AT' => 'AUT', 'AW' => 'ABW', 'IN' => 'IND', 'AX' => 'ALA',
        'AZ' => 'AZE', 'IE' => 'IRL', 'ID' => 'IDN', 'UA' => 'UKR', 'QA' => 'QAT', 'MZ' => 'MOZ'];

        return $array[$country];
    }
}

if (!function_exists('nfeio_get_city_postal_code')) {
    /**
     * Returns the postal code of a zip code.
     *
     * @param string $zip
     * @return string|array
     */
    function nfeio_get_city_postal_code($zip) {
        try {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://open.nfe.io/v1/cities/' . $zip . '/postalcode',
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            $city = json_decode($response, true);

            if ($city['message'] || $err) {
                nfeio_log('nfeio', 'nfeio_get_city_postal_code', $zip, $city['message'], 'ERROR', '');
                return 'ERROR';
            } else {
                return $city['city']['code'];
            }
        } catch (Exception $e) {
            nfeio_log('nfeio', 'nfeio_get_city_postal_code', '', $e->getMessage(), '');
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_queue_nfe')) {
    /**
     * Updates the NFe services_amount or saves the NFe in the database.
     *
     * @param string $invoice_id
     * @param bool $create_all
     *
     * @return string
     */
    function nfeio_queue_nfe($invoice_id, $create_all = false) {
        try {
            $invoice = localAPI('GetInvoice', ['invoiceid' => $invoice_id], false);
            $itens = nfeio_get_product_invoice($invoice_id);

            foreach ($itens as $item) {
                $data = [
                    'invoice_id' => $invoice_id,
                    'user_id' => $invoice['userid'],
                    'nfe_id' => 'waiting',
                    'status' => 'Waiting',
                    'services_amount' => $item['amount'],
                    'environment' => 'waiting',
                    'flow_status' => 'waiting',
                    'pdf' => 'waiting',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => 'waiting',
                    'rpsSerialNumber' => 'waiting',
                    'service_code' => $item['code_service'],
                ];
                $nfe_for_invoice = nfeio_get_local_nfe($invoice_id, ['status']);

                if (!$nfe_for_invoice['status'] || $create_all) {
                    $create_all = true;
                    try {
                        $service_code_row = Capsule::table('gofasnfeio')->where('service_code', '=', $item['code_service'])->where('invoice_id', '=', $invoice_id)->where('status', '=', 'waiting')->get(['id', 'services_amount']);

                        if (count($service_code_row) == 1) {
                            $mountDB = floatval($service_code_row[0]->services_amount);
                            $mount_item = floatval($item['amount']);
                            $mount = $mountDB + $mount_item;

                            Capsule::table('gofasnfeio')->where('id', '=', $service_code_row[0]->id)->update(['services_amount' => $mount]);
                        } else {
                            Capsule::table('gofasnfeio')->insert($data);
                        }
                    } catch (\Exception $e) {
                        return $e->getMessage();
                    }
                }
            }
            return 'success';
        } catch (Exception $e) {
            nfeio_log('nfeio', 'nfeio_queue_nfe', '', $e->getMessage(), '');
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_issue_nfe')) {
    /**
     * Sends a NFe to the NFe.io.
     *
     * @param array $postfields
     * @return mixed
     */
    function nfeio_issue_nfe($postfields) {
        try {
            $webhook_url = nfeio_get_whmcs_url() . 'modules/addons/gofasnfeio/callback.php';

            $nfeio_webhook_id = Capsule::table('tblconfiguration')->where('setting', '=', 'nfeio_webhook_id')->get(['value'])[0]->value;

            if ($nfeio_webhook_id) {
                $check_webhook = nfeio_check_webhook($nfeio_webhook_id);
                $error = '';
                if ($check_webhook['message']) {
                    nfeio_log('nfeio', 'nfeio_issue_nfe - check_webhook', $nfeio_webhook_id, $check_webhook['message'], 'ERROR', '');
                }
            }

            if ($nfeio_webhook_id and (string) $check_webhook['hooks']['url'] !== (string) $webhook_url) {
                $create_webhook = nfeio_create_webhook($webhook_url);

                if ($create_webhook['message']) {
                    nfeio_log('nfeio', 'nfeio_issue_nfe - nfeio_create_webhook', $webhook_url, $create_webhook['message'], 'ERROR', '');
                }

                if ($create_webhook['hooks']['id']) {
                    try {
                        Capsule::table('tblconfiguration')->where('setting', 'nfeio_webhook_id')->update(['value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                    } catch (Exception $e) {
                        nfeio_log('nfeio', 'nfeio_issue_nfe - Capsule::table(tblconfiguration) update', '', $e->getMessage(), 'ERROR', '');
                    }
                }

                $delete_webhook = nfeio_delete_webhook($nfeio_webhook_id);

                if ($delete_webhook['message']) {
                    nfeio_log('nfeio', 'nfeio_issue_nfe - nfeio_delete_webhook', $nfeio_webhook_id, $delete_webhook, 'ERROR', '');
                }
            }
            if (!$nfeio_webhook_id) {
                $create_webhook = nfeio_create_webhook($webhook_url);

                if ($create_webhook['message']) {
                    nfeio_log('nfeio', 'nfeio_issue_nfe - nfeio_create_webhook', $webhook_url, $create_webhook, 'ERROR', '');
                }

                if ($create_webhook['hooks']['id']) {
                    try {
                        Capsule::table('tblconfiguration')->insert(['setting' => 'nfeio_webhook_id', 'value' => $create_webhook['hooks']['id'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
                    } catch (Exception $e) {
                        nfeio_log('nfeio', 'nfeio_issue_nfe - Capsule::table(tblconfiguration) insert', '', $e->getMessage(), 'ERROR', '');
                    }
                }
            }

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/json',
                    'Accept: application/json',
                    'Authorization: ' . nfeio_get_setting('api_key')
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($postfields),
                CURLOPT_RETURNTRANSFER => true
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $info = curl_getinfo($curl);

            curl_close($curl);

            nfeio_log('nfeio', 'nfeio_issue_nfe - curl_init', $error, $info, '', '');
            nfeio_log('nfeio', 'nfeio_issue_nfe - CURLOPT_POSTFIELDS', json_encode($postfields), '', '', '');

            return $err
                ? (object) ['message' => $err, 'info' => $info]
                : json_decode(json_encode(json_decode($response)));
        } catch (Exception $e) {
            nfeio_log('nfeio', 'nfeio_get_custom_fields_dropdown', '', $e->getMessage(), '');
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_get_company_info')) {
    /**
     * Returns the current company data fetched from NFe.io.
     *
     * @return array
     */
    function nfeio_get_company_info() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id'),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ]
        ]);

        $response = json_decode(curl_exec($curl), true);
        $response = $response['companies'];

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        return $httpCode === 200
            ? $response
            : array('error' =>
                'Http code: ' . $httpCode . '| ' .
                'Resposta: ' . $response . '| ' .
                'Consulte: https://nfe.io/docs/desenvolvedores/rest-api/nota-fiscal-de-servico-v1/#/Companies/Companies_Get');
    }
}

if (!function_exists('nfeio_transfer_rps_number_handling')) {
    /**
     * Transfer the RPS number handling to the NFe.io.
     *
     * @param array $nfeioCompany
     * @param int $rpsNumber
     */
    function nfeio_transfer_rps_number_handling($nfeioCompany, $rpsNumber) {
        $nfeioCompany['rpsNumber'] = $rpsNumber + 1;
        $requestBody = json_encode($nfeioCompany);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id'),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $requestBody,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ]
        ]);

        $response = json_decode(curl_exec($curl), true);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode !== 200) {
            $response =
                'Http code: ' . $httpCode . ' | ' .
                'Resposta: ' . $response . ' | ' .
                'Consulte: https://nfe.io/docs/desenvolvedores/rest-api/nota-fiscal-de-servico-v1/#/Companies/Companies_Put';

            nfeio_log('nfeio', 'nfeio_transfer_rps_number_handling', $requestBody, $response, '', '');
        } else {
            $nfe_rps = intval(nfeio_get_last_nfe()['rpsNumber']);
            $whmcs_rps = intval(nfeio_get_setting('rps_number'));

            // Verify if the NFe's RPS is greater than or equals to the RPS located in WHMCS.
            if ($nfe_rps >= $whmcs_rps) {
                Capsule::table('tbladdonmodules')->where('module', 'gofasnfeio')->where('setting', 'rps_number')->update(['value' => 'RPS administrado pela NFe.']);
            } else {
                nfeio_log('nfeio', 'nfeio_transfer_rps_number_handling', $requestBody, 'Erro ao tentar passar tratativa de RPS para NFe. ' . $response, '', '');
            }
        }
    }
}

if (!function_exists('nfeio_test_connection')) {
    /**
     * Returns the HTTP code of an request sent to NFe.io in order to test
     * if the connection is available.
     *
     * @return int $httpCode
     */
    function nfeio_test_connection() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_exec($curl);

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        nfeio_log('nfeio', 'nfeio_issue_nfe - curl_init', curl_error($curl), $httpCode, '', '');
        curl_close($curl);

        return $httpCode;
    }
}

if (!function_exists('nfeio_get_last_nfe')) {
    /**
     * Returns the JSON of the latest issued NFe.
     *
     * @return array
     */
    function nfeio_get_last_nfe() {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices?pageCount=1&pageIndex=1',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true)['serviceInvoices']['0'];
    }
}

if (!function_exists('nfeio_delete_nfe')) {
    /**
     * Sends a DELETE request to the NFe.io in order to delete de NFe.
     *
     * @param string|int $nf
     * @return mixed
     */
    function nfeio_delete_nfe($nf) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices/' . $nf,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}

if (!function_exists('nfeio_email_nfe')) {
    /**
     * Sends a request to the NFe.io in order to the NFe be sent to its borrower.
     *
     * @return mixed;
     */
    function nfeio_email_nfe($nf) {
        if ('on' == nfeio_get_setting('email_nfe_config')) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices/' . $nf . '/sendemail',
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/json',
                    'Accept: application/json',
                    'Authorization: ' . nfeio_get_setting('api_key')
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CUSTOMREQUEST => 'PUT'
            ]);

            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response);
        }
    }
}

if (!function_exists('nfeio_pdf_nfe')) {
    /**
     * Sends a request to the NFe.io in order to a PDF of the NFe be generated.
     *
     * @return mixed
     */
    function nfeio_pdf_nfe($nf) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices/' . $nf . '/pdf',
            CURLOPT_HTTPHEADER => [
                'Content-type: application/pdf',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 30
        ]);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }
}

if (!function_exists('nfeio_xml_nfe')) {
    /**
     * Sends a request to the NFe.io in orderto a XML of the NFe be generated.
     *
     * @return mixed
     */
    function nfeio_xml_nfe($nf) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/companies/' . nfeio_get_setting('company_id') . '/serviceinvoices/' . $nf . '/xml',
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}

if (!function_exists('nfeio_get_whmcs_url')) {
    /**
     * Returns the URL of WHMCS.
     *
     * @return string
     */
    function nfeio_get_whmcs_url() {
        return Capsule::table('tblconfiguration')
                ->where('setting', '=', 'SystemURL')
                ->get(['value'])[0]['value'];
    }
}

if (!function_exists('nfeio_get_whmcs_admin_url')) {
    /**
     * Returns the admin URL of WHMCS.
     *
     * @return string
     */
    function nfeio_get_whmcs_admin_url() {
        return Capsule::table('tblconfiguration')
            ->where('setting', '=', 'nfeioWhmcsAdminUrl')
            ->get(['value'])[0]['value'];
    }
}

if (!function_exists('nfeio_update_nfe')) {
    /**
     * Updates the data of a NFe inside the WHMCS.
     *
     * @param object $nfe
     * @param string $user_id
     * @param string $invoice_id
     * @param string $pdf
     * @param string $created_at
     * @param string $updated_at
     * @param string|bool $id_gofasnfeio = false
     */
    function nfeio_update_nfe($nfe, $user_id, $invoice_id, $pdf, $created_at, $updated_at, $id_gofasnfeio = false) {
        $data = [
            'invoice_id' => $invoice_id,
            'user_id' => $user_id,
            'nfe_id' => $nfe->id,
            'status' => $nfe->status,
            'services_amount' => $nfe->servicesAmount,
            'environment' => $nfe->environment,
            'flow_status' => $nfe->flowStatus,
            'pdf' => $pdf,
            'created_at' => $created_at,
            'updated_at' => $updated_at,
            'rpsSerialNumber' => $nfe->rpsSerialNumber,
            'rpsNumber' => $nfe->rpsNumber,
        ];

        try {
            if (!$id_gofasnfeio) {
                $id = $invoice_id;
                $camp = 'invoice_id';
            } else {
                $id = $id_gofasnfeio;
                $camp = 'id';
            }
            Capsule::table('gofasnfeio')->where($camp, '=', $id)->update($data);

            return 'success';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}

if (!function_exists('nfeio_get_local_nfe')) {
    /**
    * Returns the data of a NFe from the local WHMCS database according with an invoice id
    .
    * @param string $invoice_id
    * @param string $values

    * @return string
    */
    function nfeio_get_local_nfe($invoice_id, $values) {
        foreach (Capsule::table('gofasnfeio')->where('invoice_id', '=', $invoice_id)->orderBy('id', 'desc')->get($values) as $key => $value) {
            $nfe_for_invoice[$key] = json_decode(json_encode($value), true);
        }
        return $nfe_for_invoice['0'];
    }
}

if (!function_exists('nfeio_check_webhook')) {
    /**
     * Checks the connection to the NFe webhook.
     *
     * @param string $id
     * @return array
     */
    function nfeio_check_webhook($id) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/hooks/' . $id,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => true
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('nfeio_create_webhook')) {
    /**
     * Creates a webhook that points to the $url.
     *
     * @param string $url
     * @return array
     */
    function nfeio_create_webhook($url) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/hooks',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: aplication/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode([
                'url' => $url,
                'contentType' => 'application/json',
                'secret' => (string)time(),
                'events' => ['issue', 'cancel', 'WaitingCalculateTaxes'],
                'status' => 'Active',
            ]),
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('nfeio_delete_webhook')) {
    /**
     * Deletes a webhook that own the id $id.
     *
     * @param string $id
     * @return array
     */
    function nfeio_delete_webhook($id) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.nfe.io/v1/hooks/' . $id,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/json',
                'Accept: application/json',
                'Authorization: ' . nfeio_get_setting('api_key')
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

if (!function_exists('nfeio_get_product_invoice')) {
    /**
     * Returns all products that match with the id of the invoice.
     *
     * @param string $invoice_id
     * @return array $products_details
     */
    function nfeio_get_product_invoice($invoice_id) {
        $query = 'SELECT tblinvoiceitems.invoiceid ,tblinvoiceitems.type ,tblinvoiceitems.relid, tblinvoiceitems.description,tblinvoiceitems.amount
        FROM tblinvoiceitems
        WHERE tblinvoiceitems.invoiceid = :INVOICEID';

        $pdo = Capsule::connection()->getPdo();
        $pdo->beginTransaction();
        $statement = $pdo->prepare($query);
        $statement->execute([':INVOICEID' => $invoice_id]);
        $row = $statement->fetchAll();
        $pdo->commit();

        $tax_check = nfeio_get_setting('apply_tax');
        foreach ($row as $item) {
            $hosting_id = $item['relid'];

            if ($item['type'] == 'Hosting') {
                $query = 'SELECT tblhosting.billingcycle ,tblhosting.id,tblproductcode.code_service ,tblhosting.packageid,tblhosting.id
                FROM tblhosting
                LEFT JOIN tblproducts ON tblproducts.id = tblhosting.packageid
                LEFT JOIN tblproductcode ON tblhosting.packageid = tblproductcode.product_id
                WHERE tblhosting.id = :HOSTING';

                if ($tax_check === 'Não') {
                    $query .= ' AND tblproducts.tax = 1';
                } else {
                    Capsule::table('tblproducts')->update(['apply_tax' => 1]);
                }

                $pdo->beginTransaction();
                $statement = $pdo->prepare($query);
                $statement->execute([':HOSTING' => $hosting_id]);
                $product = $statement->fetchAll();
                $pdo->commit();

                if ($product) {
                    $product_array['id_product'] = $product[0]['packageid'];
                    $product_array['code_service'] = $product[0]['code_service'];
                    $product_array['amount'] = $item['amount'];
                    $products_details[] = $product_array;
                }
            } else {
                $product_array['id_product'] = $item['packageid'];
                $product_array['code_service'] = null;
                $product_array['amount'] = $item['amount'];
                $products_details[] = $product_array;
            }
        }

        return $products_details;
    }
}

if (!function_exists('nfeio_download_log')) {
    /**
     * Generates and prompts to download the module log.
     *
     * @return void
     */
    function nfeio_download_log() {
        $days = 5;

        $moduleConfigs = [];
        foreach (Capsule::table('tbladdonmodules')->where('module','=','gofasnfeio')->get(['setting', 'value']) as $row) {
            $moduleConfigs[$row->setting] = $row->value;
        }

        $lastCron = Capsule::table('tbladdonmodules')->where('setting', '=' ,'last_cron')->get(['value'])[0];

        $results = localAPI('WhmcsDetails');
        $v = $results['whmcs']['version'];
        $actual_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

        $text = '-|date' . PHP_EOL . '-|action' . PHP_EOL . '-|request' . PHP_EOL . '-|response' . PHP_EOL . '-|status' . PHP_EOL;
        $text .= 'version =' . $v . PHP_EOL . 'date emission =' . date('Y-m-d H:i:s') . PHP_EOL . 'url =' . $actual_link . PHP_EOL . 'conf_module = ' . json_encode($moduleConfigs) . PHP_EOL . 'last_cron = ' . $lastCron->value . PHP_EOL;

        $dataAtual = toMySQLDate(getTodaysDate(false)) . ' 23:59:59';
        $dataAnterior = date('Y-m-d',mktime (0, 0, 0, date('m'), date('d') - $days,  date('Y'))) . ' 23:59:59';

        foreach (Capsule::table('tblmodulelog')->where('module','=','nfeio')->orderBy('date')->whereBetween('date', [$dataAnterior, $dataAtual])->get(['date', 'action', 'request', 'response', 'arrdata']) as $log) {
            $text .= PHP_EOL . '==========================================================================================================================================' . PHP_EOL;
            $text .= '-|date = ' . $log->date . PHP_EOL . '-|action = ' . $log->action . PHP_EOL . '-|request = ' . ($log->request) . PHP_EOL . '-|response = ' . ($log->response) . PHP_EOL . '-|status = ' . ($log->arrdata);
        }
        $text .= PHP_EOL . '====================================================================FIM DO ARQUIVO======================================================================' . PHP_EOL;

        header('Content-type: text/plain');
        header('Content-Disposition: attachment; filename="default-filename.txt"');
        print $text;
        exit();
    }
}

if (!function_exists('nfeio_update_nfe_status')) {
    /**
     * Updates a NFe status according to $invoice_id param.
     *
     * @param string $invoice_id
     * @param string $status
     *
     * @return mixed
     */
    function nfeio_update_nfe_status($invoice_id, $status) {
        try {
            return Capsule::table('gofasnfeio')->where('invoice_id','=',$invoice_id)->update(['status' => $status]);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}

// ------------------------------------------------- NOVAS FUNÇÕES
if (!function_exists('nfeio_get_client_issue_nfe_cond')) {
    /**
     * Returns the custom condition of NFe issue of an user.
     *
     * @param string $invoiceId
     * @return string|array
     */
    function nfeio_get_client_issue_nfe_cond($invoiceId) {
        try {
            $clientInvoiceId = localAPI('GetInvoice', ['invoiceid' => $invoiceId])['userid'];

            $clientCond = Capsule::table('mod_nfeio_custom_configs')
                ->where('client_id', '=', $clientInvoiceId)
                ->where('key', '=', 'issue_nfe_cond')
                ->get(['value'])[0]->value;
            $clientCond = strtolower($clientCond);

            if ($clientCond !== null && $clientCond !== 'seguir configuração do módulo nfe.io') {
                return $clientCond;
            }

            return 'seguir configuração do módulo nfe.io';
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_show_issue_invoice_conds')) {
    /**
     * Returns a <select> HTML which is used only by the AdminClientProfileTabFields hook
     * in the file hooks.php.
     *
     * @param string $clientId
     * @return string|array
     */
    function nfeio_show_issue_invoice_conds($clientId) {
        try {
            $conditions = Capsule::table('tbladdonmodules')->where('module', '=', 'gofasnfeio')->where('setting', '=', 'issue_note_conditions')->get(['value'])[0]->value;
            $conditions = explode(',', $conditions);

            $previousClientCond = Capsule::table('mod_nfeio_custom_configs')->where('client_id', '=', $clientId)->get(['value'])[0]->value;

            $select = '<select name="issue_note_cond" class="form-control select-inline">';

            // Sets the previous issue condition in the first index of array $conditions.
            // in order to the previous condition be showed in the client prifile.
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

            return $select;
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_save_client_issue_nfe_cond')) {
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
            $previousClientCond = Capsule::table('mod_nfeio_custom_configs')->where('client_id', '=', $clientId)->get(['value'])[0]->value;

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
}

if (!function_exists('nfeio_save_issue_nfe_conds')) {
    /**
     * Inserts the conditions of sending invoices in the database.
     *
     * @return void|array
     */
    function nfeio_save_issue_nfe_conds() {
        try {
            $conditions = 'Quando a fatura é gerada,Quando a fatura é paga,Seguir configuração do módulo NFE.io';

            Capsule::table('tbladdonmodules')->insert(['module' => 'gofasnfeio','setting' => 'issue_note_conditions','value' => $conditions]);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

if (!function_exists('nfeio_get_whmcs_admin_url')) {
    function nfeio_get_whmcs_admin_url() {
        return Capsule::table('tblconfiguration')->where('setting', '=', 'nfeioWhmcsAdminUrl')->get(['value'])[0]->value;
    }
}


if (!function_exists('nfeio_set_admin_url')) {
    /**
     * Saves the WHMCS /admin URL in the tblconfiguration
     */
    function nfeio_set_admin_url($docRoot, $httpHost) {
        if (Capsule::table('tblconfiguration')->where('setting', '=', 'nfeioWhmcsAdminUrl')->count() === 0) {
            $actual_link = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . "://{$httpHost}{$_SERVER['REQUEST_URI']}";

            if (stripos($actual_link, '/configaddonmods.php')) {
                $whmcs_url__ = str_replace('\\', '/', (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $httpHost . substr(getcwd(), strlen($docRoot)));
                $admin_url = $whmcs_url__ . '/';
            }

            Capsule::table('tblconfiguration')->insert([
                'setting' => 'nfeioWhmcsAdminUrl',
                'value' => $admin_url,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
