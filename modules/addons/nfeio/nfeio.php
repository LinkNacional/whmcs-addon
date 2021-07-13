<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/output.php';
require_once __DIR__ . '/database.php';

/**
 * Shows the module setting fields.
 */
function nfeio_config()
{
	if ($_GET['doc_log']) nfeio_download_log();

	$moduleVersion = '2.0.0';
	$whmcsSystemUrl = nfeio_get_whmcs_url();
	$whmcsSystemAdminUrl = nfeio_get_whmcs_admin_url();

	$moduleSettings = [
		'name' => 'NFE.io',
		'description' => 'Módulo NFE.io de Nota Fiscal para WHMCS',
		'version' => $moduleVersion,
		'author' => '<a title="NFE.io Nota Fiscal WHMCS" href="https://github.com/nfe/whmcs-addon/" target="_blank" ><img src="' . $whmcsSystemUrl . 'modules/addons/nfeio/lib/logo.png"></a>',
		'fields' => [

			'header' => [
				'Description' => '
                <h4 style="padding-top: 5px;">Módulo NFE.io de Nota Fiscal para WHMCS | v' . $moduleVersion . '</h4>
                <a style="text-decoration:underline;" href="https://app.nfe.io/companies/edit/fiscal/' . nfeio_get_setting('company_id') . '" target="_blank">
                Consultar: RPS / Série do RPS
                </a>',
			],

			'api_key' => [
				'FriendlyName' => 'API Key',
				'Type' => 'text',
				'Description' => '<a href="https://app.nfe.io/account/apikeys" style="text-decoration:underline;" target="_blank">Obter chave de acesso</a>',
			],

			'company_id' => [
				'FriendlyName' => 'ID da Empresa',
				'Type' => 'text',
				'Description' => '<a href="https://app.nfe.io/companies/" style="text-decoration:underline;" target="_blank">Obter ID da empresa</a>',
			],

			'service_code' => [
				'FriendlyName' => 'Código de Serviço Principal',
				'Type' => 'text',
				'Description' => '<a style="text-decoration:underline;" href="https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/#o-que-e-codigo-de-servico" target="_blank">O que é Código de Serviço?</a>',
			],

			'issue_note_after_days' => [
				'FriendlyName' => 'Agendar Emissão',
				'Type' => 'text',
				'Default' => '',
				'Description' => '<div>Número de dias após o pagamento da fatura que as notas devem ser emitidas. <span style="color:#c00; font-weight: bold;">Preencher essa opção desativa a opção abaixo.</span></div>',
			],

			'issue_note_default_cond' => [
				'FriendlyName' => 'Quando emitir NFE',
				'Type' => 'dropdown',
				'Options' => 'Quando a fatura é gerada,Quando a fatura é paga,Manualmente',
				'Default' => 'Manualmente',
			],

			'municipal_inscri_field' => [
				'FriendlyName' => 'Inscrição Municipal',
				'Type' => 'dropdown',
				'Options' => nfeio_get_custom_fields_dropdown(),
				'Description' => 'Escolha o campo personalizado de Inscrição Municipal',
			],

			'cpf_field' => [
				'FriendlyName' => 'CPF ',
				'Type' => 'dropdown',
				'Options' => nfeio_get_custom_fields_dropdown(),
				'Description' => 'Escolha o campo personalizado do CPF',
			],

			'cnpj_field' => [
				'FriendlyName' => 'CNPJ',
				'Type' => 'dropdown',
				'Options' => nfeio_get_custom_fields_dropdown(),
				'Description' => 'Escolha o campo personalizado do CNPJ',
			],

			'invoice_details' => [
				'FriendlyName' => 'O que deve aparecer nos detalhes da fatura?',
				'Type' => 'dropdown',
				'Options' => 'Número da fatura,Nome dos serviços,Número da fatura + Nome dos serviços',
				'Default' => 'Número da fatura',
			],

			'custom_invoice_descri' => [
				'FriendlyName' => 'Adicione uma informação personalizada na nota fiscal:',
				'Type' => 'text',
				'Default' => '',
				'Description' => 'Esta informação será acrescida após detalhes da fatura.',
			],

			'email_nfe_config' => [
				'FriendlyName' => 'Disparar e-mail com a nota',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Permitir o disparo da nota fiscal via NFE.io para o e-mail do cliente.',
			],

			'cancel_invoice_cancels_nfe' => [
				'FriendlyName' => 'Cancelar NFE',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Cancela a nota fiscal quando a fatura cancelada.',
			],

			'debug' => [
				'FriendlyName' => 'Debug',
				'Type' => 'yesno',
				'Default' => 'yes',
				'Description' => 'Marque essa opção para salvar informações de diagnóstico no
                <a target="_blank" style="text-decoration:underline;" href="' . $whmcsSystemAdminUrl . 'systemmodulelog.php">Log de Módulo</a>
                | Baixar log <a target="_blank" href="' . $whmcsSystemAdminUrl . 'configaddonmods.php?doc_log=true" style="text-decoration:underline;">AQUI
                </a>',
			],

			'apply_tax' => [
				'FriendlyName' => 'Aplicar imposto automaticamente em todos os produtos ?',
				'Type' => 'radio',
				'Options' => 'Sim,Não',
				'Default' => 'Sim',
			],

			'send_invoice_url' => [
				'FriendlyName' => 'Exibir link da fatura na nota fiscal?',
				'Type' => 'radio',
				'Options' => 'Sim,Não',
				'Default' => 'Não',
			],

			'development_env' => [
				'FriendlyName' => 'Ambiente de desenvolvimento',
				'Type' => 'yesno',
				'Default' => '',
				'Description' => 'Habilitar ambiente de desenvolvimento',
			],

			'footer' => [
				'Description' => '&copy; ' . date('Y') . ' <a target="_blank" title="Para suporte utilize o GitHub" href="https://github.com/nfe/whmcs-addon/issues">Suporte do módulo</a>',
			],
		],
	];

	$lastVersion = nfeio_get_module_last_version();

	if (version_compare($lastVersion, $moduleVersion, '>')) {
		$moduleSettings['fields']['header']['Description'] .=
			'<span style="display: block; color: red; font-size: 14px; padding-top: 10px; padding-bottom: 5px;">
            <i class="fas fa-exclamation-triangle"></i> Há uma nova versão disponível. Acesse:
            <a style="text-decoration:underline;" href="https://github.com/nfe/whmcs-addon/releases" target="_blank">Nova versão</a>
        </span>';
	} else {
		$moduleSettings['fields']['header']['Description'] .=
			'<span style="display: block; color: green; font-size: 14px; padding-top: 10px; padding-bottom: 5px;">
            <i class="fas fa-check-square"></i> Esta é a versão mais recente do módulo.
        </span>';
	}

	return $moduleSettings;
}

/**
 * Performed when the module is activated.
 * Creates the tables in the database.
 */
function nfeio_activate()
{
	nfeio_create_tables();
	nfeio_set_whmcs_admin_url($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_HOST']);
	nfeio_set_issue_nfe_conds();
	nfeio_set_initial_date();
}

/**
 * Performed when teh module is deactivated.
 * Undo all actions done in nfeio_config()
 */
function nfeio_deactivate()
{
	// TODO
}

/**
 * Called the first time the module is accessed following an update.
 * Performs required database and schema modifications per upgrade.
 *
 * @param array $vars
 */
function nfeio_upgrade($vars)
{
	$currentlyInstalledVersion = $vars['version'];
	nfeio_log('nfeio', 'nfeio_upgrade: $currentlyInstalledVersion', $currentlyInstalledVersion, '', '');

	// Between 1.4.0 and 1.4.9
	if (
		version_compare($currentlyInstalledVersion, '1.4.0', '>=')
		&& version_compare($currentlyInstalledVersion, '2.0.0', '<')
		|| empty($currentlyInstalledVersion)
	) {
		$moduleVersion = '2.0.0';

		if (Capsule::table('tbladdonmodules')->where('module', '=', 'nfeio')->where('setting', '=', 'version')->count() == 0) {
			Capsule::table('tbladdonmodules')->insert(['module' => 'nfeio', 'setting' => 'version', 'value' => $moduleVersion]);
		} else {
			Capsule::table('tbladdonmodules')->where('module', '=', 'nfeio')->where('setting', '=', 'version')->update(['value' => $moduleVersion]);
		}

		// Deletes old rows of tbladdonmodules table.
		$tblAddonOldRows = [
			'api_key',
			'company_id',
			'service_code',
			'issue_note_default_cond',
			'issue_note_after',
			'gnfe_email_nfe_config',
			'cancel_invoice_cancel_nfe',
			'debug',
			'insc_municipal',
			'cpf_camp',
			'cnpj_camp',
			'tax',
			'InvoiceDetails',
			'send_invoice_url',
			'descCustom',
			'NFEioEnvironment',
			'footer',
			'access',
			'last_cron',
			'module_version',
			'issue_note_conditions'
		];

		foreach ($tblAddonOldRows as $row) {
			nfeio_log('nfeio', 'Deleting', 'Deleting row' . $row, '', '');
			Capsule::table('tbladdonmodules')
				->where('module', '=', 'gofasnfeio')
				->where('setting', '=', $row)
				->delete();
		}

		// Deletes old rows of tblconfiguration table.
		$tblConfigOldRows = [
			'gnfe_webhook_id',
			'gnfe_email_nfe',
			'gnfewhmcsurl',
			'gnfewhmcsadminpath',
			'gnfewhmcsadminurl',
		];

		try {
			foreach ($tblConfigOldRows as $row) {
				Capsule::table('tblconfiguration')
					->where('setting', '=', $row)
					->delete();
			}
			nfeio_log('nfeio', 'nfeio_upgrade', 'Delete tblconfiguration old rows: Capsule->delete()', 'SUCCESS', '');
		} catch (Exception $e) {
			nfeio_log('nfeio', 'nfeio_upgrade', 'Delete tblconfiguration old rows: Capsule->delete()', $e->getMessage(), '');
		}

		// Renames the table gofasnfeio to nfeio.
		if (Capsule::schema()->hasTable('gofasnfeio')) {
			try {
				$pdo = Capsule::connection()->getPdo();
				$pdo->beginTransaction();

				try {
					$cmd = $pdo->prepare('RENAME TABLE gofasnfeio TO nfeio');
					$cmd->execute();
				} catch (Exception $e) {
					$pdo->rollBack();
					nfeio_log('nfeio', 'nfeio_upgrade', 'RENAME TABLE gofasnfeio TO nfeio: cmd->execute', $e->getMessage(), '');
				}

				nfeio_log('nfeio', 'nfeio_upgrade', 'RENAME TABLE gofasnfeio TO nfeio', 'SUCCESS', '');
				$pdo->commit();
			} catch (Exception $e) {
				nfeio_log('nfeio', 'nfeio_upgrade', 'RENAME TABLE gofasnfeio TO nfeio: pdo->commit()', $e->getMessage(), '');
			}
		}

		nfeio_create_tables();
		nfeio_set_whmcs_admin_url($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_HOST']);
		nfeio_set_issue_nfe_conds();
		nfeio_set_initial_date();
		nfeio_log('nfeio', 'nfeio_upgrade', 'Upgrade successfull.', '', '');
	}
}
