<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

function nfeio_issue_note_to_nfe($invoices, $nfeio) {
	$invoice = localAPI('GetInvoice', ['invoiceid' => $invoices->id], false);
	$client = localAPI('GetClientsDetails', ['clientid' => $invoices->userid], false);

	$params = nfeio_get_setting();

	//create second option from description nfe
	foreach ($invoice['items']['item'] as $value) {
		$line_items[] = $value['description'];
	}

	//  CPF/CNPJ/NAME
	$customer = nfeio_get_customer($invoices->userid, $client);
	nfeio_log('nfeio', 'nfeio_get_customer', $customer, '', '', '');

	if ($customer['doc_type'] == 2) {
		if ($client['companyname'] != '') {
			$name = $client['companyname'];
		} else {
			$name = $client['fullname'];
		}
	} elseif ($customer['doc_type'] == 1 || 'CPF e/ou CNPJ ausente.' == $customer || !$customer['doc_type']) {
		$name = $client['fullname'];
	}
	$name = htmlspecialchars_decode($name);

	//service_code
	$service_code = $nfeio->service_code ? $nfeio->service_code : $params['service_code'];

	//description nfe
	if ($params['custom_invoice_descri'] == 'Número da fatura') {
		$gnfeWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;

		$desc = 'Nota referente a fatura #' . $invoices->id . '  ';
		if ($params['send_invoice_url'] === 'Sim') {
			$desc .= $gnfeWhmcsUrl . 'viewinvoice.php?id=' . $invoices->id;
		}
		$desc .= ' ' . $params['custom_invoice_descri'];
	} elseif ($params['custom_invoice_descri'] == 'Nome dos serviços') {
		$desc = substr(implode("\n", $line_items), 0, 600) . ' ' . $params['custom_invoice_descri'];
	} elseif ($params['custom_invoice_descri'] == 'Número da fatura + Nome dos serviços') {
		$gnfeWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;
		$desc = 'Nota referente a fatura #' . $invoices->id . '  ';
		if ($params['send_invoice_url'] === 'Sim') {
			$desc .= $gnfeWhmcsUrl . 'viewinvoice.php?id=' . $invoices->id;
		}
		$desc .= ' | ' . substr(implode("\n", $line_items), 0, 600) . ' ' . $params['custom_invoice_descri'];
	}

	nfeio_log('nfeio', 'description-custom_invoice_descri', $params['custom_invoice_descri'], '', '', '');
	nfeio_log('nfeio', 'description-custom_invoice_descri', $params['custom_invoice_descri'], '', '', '');
	nfeio_log('nfeio', 'description', $params, '', '', '');

	//define address
	if (strpos($client['address1'], ',')) {
		$array_adress = explode(',', $client['address1']);
		$street = $array_adress[0];
		$number = $array_adress[1];
	} else {
		$street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1']));
		$number = preg_replace('/[^0-9]/', '', $client['address1']);
	}

	if ($params['email_nfe_config'] == 'on') {
		$client_email = $client['email'];
	} else {
		$client_email = '';
	}

	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe - customer', $customer, '', '', '');
	$code = nfeio_get_city_postal_code(preg_replace('/[^0-9]/', '', $client['postcode']));
	if ($code == 'ERROR') {
		nfeio_log('nfeio', 'nfeio_issue_note_to_nfe - nfeio_get_city_postal_code', $customer, '', 'ERROR', '');
		nfeio_update_nfe_status($nfeio->invoice_id, 'Error_cep');
	} else {
		//cria o array do request
		$postfields = createRequestFromAPI($service_code, $desc, $nfeio->services_amount, $customer['document'], $customer['municipal_inscri'], $name, $client_email, $client['countrycode'], $client['postcode'], $street, $number, $client['address2'], $code, $client['city'], $client['state']);

		//envia o requisição
		$nfe = nfeio_issue_nfe($postfields);

		if ($nfe->message) {
			nfeio_log('nfeio', 'nfeio_issue_note_to_nfe', $postfields, $nfe, 'ERROR', '');
		}
		if (!$nfe->message) {
			nfeio_log('nfeio', 'nfeio_issue_note_to_nfe', $postfields, $nfe, 'OK', '');
			$nfeio_update_nfe = nfeio_update_nfe($nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $waiting->id);

			if ($nfeio_update_nfe && $nfeio_update_nfe !== 'success') {
				nfeio_log('nfeio', 'nfeio_issue_note_to_nfe - nfeio_update_nfe', [$nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $waiting->id], $nfeio_update_nfe, 'ERROR', '');
			}
		}
	}
}

function createRequestFromAPI($service_code, $desc, $services_amount, $document, $municipal_inscri = '', $name, $email, $countrycode, $postcode, $street, $number, $address2, $code, $city, $state) {
	$postfields = [
		'cityServiceCode' => $service_code,
		'description' => $desc,
		'servicesAmount' => $services_amount,
		'borrower' => [
			'federalTaxNumber' => $document,
			'municipalTaxNumber' => $municipal_inscri,
			'name' => $name,
			'email' => $email,
			'address' => [
				'country' => nfeio_country_code($countrycode),
				'postalCode' => preg_replace('/[^0-9]/', '', $postcode),
				'street' => $street,
				'number' => $number,
				'additionalInformation' => '',
				'district' => $address2,
				'city' => [
					'code' => $code,
					'name' => $city,
				],
				'state' => $state,
			],
		],
	];
	strlen($municipal_inscri) == 0 ? '' : $postfields['borrower']['municipalTaxNumber'] = $municipal_inscri;

	return $postfields;
}
