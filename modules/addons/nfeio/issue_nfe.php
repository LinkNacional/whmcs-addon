<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

/**
 * Get the data of the NFe and then enqueue the NFe to the NFE.io.
 *
 * @param object $invoices stdClass object
 * @param object $nfeio stdClass object
 */
function nfeio_issue_note_to_nfe($invoices, $nfeio) {
	$invoice = localAPI('GetInvoice', ['invoiceid' => $invoices->id], false);
	$client = localAPI('GetClientsDetails', ['clientid' => $invoices->userid], false);
	$params = nfeio_get_setting();

	// Gets the complete description of the NFe.
	$description = nfeio_get_nfe_description($invoices->userid, $invoices->id, $invoice['items']['item']);
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_get_nfe_description', '', $description, '');

	// CPF/CNPJ/NAME
	$customer = nfeio_get_customer($invoices->userid, $client);
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_get_customer', '', $customer, '');

	if ($customer['doc_type'] == 2) {
		$name = !empty($client['companyname']) ? $client['companyname'] : $client['fullname'];
	} elseif ($customer['doc_type'] == 1 || $customer === 'CPF e/ou CNPJ ausente.' || !$customer['doc_type']) {
		$name = $client['fullname'];
	}

	// Borrower's name
	$name = htmlspecialchars_decode($name);

	// Service code
	$service_code = $nfeio->service_code ? $nfeio->service_code : $params['service_code'];
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_get_nfe_description', '', $service_code, '');

	// Adress
	if (strpos($client['address1'], ',')) {
		$array_adress = explode(',', $client['address1']);
		$street = $array_adress[0];
		$number = $array_adress[1];
	} else {
		$street = str_replace(',', '', preg_replace('/[0-9]+/i', '', $client['address1']));
		$number = preg_replace('/[^0-9]/', '', $client['address1']);
	}

	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe: $street', '', $street, '');
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe: $number', '', $number, '');

	// Must the client email be sent?
	$clientEmail = $params['email_nfe_config'] === 'on' ? $client['email'] : '';
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe: $number', '', $clientEmail, '');

	$code = nfeio_get_city_postal_code(preg_replace('/[^0-9]/', '', $client['postcode']));
	nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_get_city_postal_code', '', $code, '');

	if ($code == 'ERROR') {
		nfeio_update_nfe_status($nfeio->invoice_id, 'Error_cep');
	} else {
		echo '<pre>';
		print_r($customer);
		echo '</pre><hr>';
		$json = nfeio_get_nfe_json_request(
			$service_code,
			$description,
			$nfeio->services_amount,
			$customer['document'],
			$customer['municipal_inscri'],
			$name,
			$clientEmail,
			$client['countrycode'],
			$client['postcode'],
			$street,
			$number,
			$client['address2'],
			$code,
			$client['city'],
			$client['state']
		);

		// Envia o requisição
		$nfe = nfeio_issue_nfe($json);
		echo '<pre>';
		print_r($nfe);
		echo '</pre><hr>';

		if ($nfe->message) {
			nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_issue_nfe', $json, $nfe, 'ERROR', '');
		} else {
			nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_issue_nfe', $json, $nfe, 'OK', '');
			$nfeio_update_nfe = nfeio_update_nfe($nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $waiting->id);
			echo '<pre>';
			print_r($nfeio_update_nfe);
			echo '</pre><hr>';
			if ($nfeio_update_nfe !== 'success') {
				nfeio_log('nfeio', 'nfeio_issue_note_to_nfe -> nfeio_update_nfe', [$nfe, $invoices->userid, $invoices->id, 'n/a', date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $waiting->id], $nfeio_update_nfe, 'ERROR', '');
			}
		}
	}
}

function nfeio_get_nfe_json_request($service_code, $description, $services_amount, $document, $municipal_inscri = '', $name, $email, $countrycode, $postcode, $street, $number, $address2, $code, $city, $state) {
	return [
		'cityServiceCode' => $service_code,
		'description' => $description,
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
}

/**
 * Returns the description shown on the NFe.
 *
 * @param string $userId
 * @param array $invoiceItems
 */
function nfeio_get_nfe_description($userId, $invoiceId, $invoiceItems) {
	$items = nfeio_get_nfe_items_descriptions($userId, $invoiceItems);
	$sendInvoiceDetails = nfeio_get_setting('invoice_details');
	$sendInvoiceUrl = nfeio_get_setting('send_invoice_url');
	$nfeioWhmcsUrl = Capsule::table('tblconfiguration')->where('setting', '=', 'Domain')->get(['value'])[0]->value;

	$desc = '';

	switch ($sendInvoiceDetails) {
		case 'Número da fatura':
			$desc = 'Nota referente a fatura #' . $invoiceId . '  ';
			$desc .= $sendInvoiceUrl === 'Sim'
				? $nfeioWhmcsUrl . 'viewinvoice.php?id=' . $invoiceId
				: '';

			break;

		case 'Nome dos serviços':
			$desc = substr(implode("\n", $items), 0, 600);

			break;

		default:
			$desc = 'Nota referente a fatura #' . $invoiceId . '  ';
			$desc .= $sendInvoiceUrl === 'Sim'
				? $nfeioWhmcsUrl . 'viewinvoice.php?id=' . $invoiceId
				: '';
			$desc .= ' | ' . substr(implode("\n", $items), 0, 600);

			break;
	}

	$desc .= nfeio_get_setting('custom_invoice_descri');

	return $desc;
}

/**
 * Returns the description and the custom description for each item of an invoice.
 * These custom descriptions are from the exclusive functionality of the module.
 *
 * @param string $userid
 * @param array $items
 */
function nfeio_get_nfe_items_descriptions($userId, $items) {
	$itemsDescriptions = [];

	foreach ($items as $item) {
		$key = 'service_custom_desc_' . $item['relid'];

		$customDescrip = Capsule::table('mod_nfeio_custom_configs')
			->where('client_id', '=', $userId)
			->where('key', '=', $key)
			->get(['value'])[0]->value;

		if ($item['type'] === 'Hosting' && !empty($customDescrip)) {
			$itemsDescriptions[] = $item['description'] . ' | ' . $customDescrip;
		} else {
			$itemsDescriptions[] = $item['description'];
		}
	}

	return $itemsDescriptions;
}
