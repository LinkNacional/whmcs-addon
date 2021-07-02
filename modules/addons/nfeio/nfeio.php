<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/output.php';
require_once __DIR__ . '/database.php';

/**
 * Shows the module setting fields.
 */
function nfeio_config() {
    if ($_GET['doc_log']) nfeio_download_log();

    $moduleVersion = '2.0.0';

    $moduleSettings = array(
        'name' => 'NFE.io',
        'description' => 'Módulo NFE.io de Nota Fiscal para WHMCS',
        'version' => $moduleVersion,
        'author' => '<a title="NFE.io Nota Fiscal WHMCS" href="https://github.com/nfe/whmcs-addon/" target="_blank" ><img src="' . nfeio_get_whmcs_admin_url() . 'modules/addons/nfeio/lib/logo.png"></a>',
        'fields' => array (

            'api_key' => array(
                'FriendlyName' => 'API Key',
                'Type' => 'text',
                'Description' => '<a href="https://app.nfe.io/account/apikeys" style="text-decoration:underline;" target="_blank">Obter chave de acesso</a>'
            ),

            'company_id' => array(
                'FriendlyName' => 'ID da Empresa',
                'Type' => 'text',
                'Description' => '<a href="https://app.nfe.io/companies/" style="text-decoration:underline;" target="_blank">Obter ID da empresa</a>',
            )
        )
    );

    if (empty(nfeio_get_setting('api_key')) || empty(nfeio_get_setting('company_id'))) {
        $moduleSettings['header']['Description'] .= 'Preencha os campos de API Key e ID da Empresa para acessar as configurações do módulo.';
    } else {
        $moduleSettings['header']['Description'] .=
        '<a style="text-decoration:underline;" href="https://app.nfe.io/companies/edit/fiscal/' . nfeio_get_setting('company_id') . '" target="_blank">
            Consultar: RPS / Série do RPS
        </a>';

        $moduleSettings['fields'] .= array (

            'service_conde' => array(
                'FriendlyName' => 'Código de Serviço Principal',
                'Type' => 'text',
                'Description' => '<a style="text-decoration:underline;" href="https://nfe.io/docs/nota-fiscal-servico/conceitos-nfs-e/#o-que-e-codigo-de-servico" target="_blank">O que é Código de Serviço?</a>',
            ),

            'issue_note_after_days' => array(
                'FriendlyName' => 'Agendar Emissão',
                'Type' => 'text',
                'Default' => '',
                'Description' => '<br>Número de dias após o pagamento da fatura que as notas devem ser emitidas. <span style="color:#c00">Preencher essa opção desativa a opção abaixo.</span>',
            ),

            'issue_note_default_cond' => array(
                'FriendlyName' => 'Quando emitir NFE',
                'Type' => 'radio',
                'Options' => 'Quando a fatura é gerada,Quando a fatura é paga,Manualmente.',
                'Default' => 'Manualmente'
            ),

            'email_nfe_config' => array(
                'FriendlyName' => 'Disparar e-mail com a nota',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Permitir o disparo da nota fiscal via NFE.io para o e-mail do cliente.'
            ),

            'cancel_invoice_cancels_nfe' => array(
                'FriendlyName' => 'Cancelar NFE',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Cancela a nota fiscal quando a fatura cancelada.',
            ),

            'debug' => array(
                'FriendlyName' => 'Debug',
                'Type' => 'yesno',
                'Default' => 'yes',
                'Description' => 'Marque essa opção para salvar informações de diagnóstico no <a target="_blank" style="text-decoration:underline;" href="' . $admin_url . 'systemmodulelog.php">Log de Módulo</a> | Baixar log <a target="_blank" href="' . $admin_url . 'configaddonmods.php?doc_log=true" style="text-decoration:underline;">AQUI</a>',
            ),

            'municipal_inscri' => array(
                'FriendlyName' => 'Inscrição Municipal',
                'Type' => 'dropdown',
                'Options' => nfeio_get_custom_fields_dropdown(),
                'Description' => 'Escolha o campo personalizado de Inscrição Municipal',
            ),

            'cpf_field' => array(
                'FriendlyName' => 'CPF ',
                'Type' => 'dropdown',
                'Options' => nfeio_get_custom_fields_dropdown(),
                'Description' => 'Escolha o campo personalizado do CPF',
            ),

            'cnpj_field' => array(
                'FriendlyName' => 'CNPJ',
                'Type' => 'dropdown',
                'Options' => nfeio_get_custom_fields_dropdown(),
                'Description' => 'Escolha o campo personalizado do CNPJ'
            ),

            'apply_tax' => array(
                'FriendlyName' => 'Aplicar imposto automaticamente em todos os produtos ?',
                'Type' => 'radio',
                'Options' => 'Sim,Não',
                'Default' => 'Sim'
            ),

            'invoice_details' => array(
                'FriendlyName' => 'O que deve aparecer nos detalhes da fatura?',
                'Type' => 'radio',
                'Options' => 'Número da fatura,Nome dos serviços,Número da fatura + Nome dos serviços',
                'Default' => 'Número da fatura',
            ),

            'send_invoice_url' => array(
                'FriendlyName' => 'Exibir link da fatura na nota fiscal?',
                'Type' => 'radio',
                'Options' => 'Sim,Não',
                'Default' => 'Não'
            ),

            'custom_invoice_descri' => array(
                'FriendlyName' => 'Adicione uma informação personalizada na nota fiscal:',
                'Type' => 'text',
                'Default' => '',
                'Description' => 'Esta informação será acrescida após detalhes da fatura.',
            ),

            'development_env' => array(
                'FriendlyName' => 'Ambiente de desenvolvimento',
                'Type' => 'yesno',
                'Default' => '',
                'Description' => 'Habilitar ambiente de desenvolvimento',
            )

        );
    }

    $moduleSettings['fields'] .=
    array('footer' => array(
        'Description' => '&copy; ' . date('Y') . ' <a target="_blank" title="Para suporte utilize o GitHub" href="https://github.com/nfe/whmcs-addon/issues">Suporte módulo</a>',
    ));

    return $moduleSettings;
}

/**
 * Performed when the module is activated.
 * Creates the tables in the database.
 */
function nfeio_activate() {
    nfeio_set_admin_url($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_HOST']);
    nfeio_create_tables();
}

/**
 * Performed when teh module is deactivated.
 * Undo all actions done in nfeio_config()
 */
function nfeio_deactivate() {
    // TODO
}

/**
 * Called the first time the module is accessed following an update.
 * Performs required database and schema modifications per upgrade.
 *
 * @param array $vars
 */
function nfeio_upgrade ($vars) {
    $currentlyInstalledVersion = $vars['version'];

    // Deletes old rows
    $tblconfiguration = [
        'gnfe_webhook_id',
        'gnfe_email_nfe',
        'gnfewhmcsurl',
        'nfeioWhmcsAdminUrl',
        'gnfewhmcsadminpath'
    ];

    foreach ($tblconfiguration as $field) {
        Capsule::table('tblconfiguration')
            ->where('setting', '=', $field)
            ->delete();
    }

    nfeio_set_admin_url($_SERVER['DOCUMENT_ROOT'], $_SERVER['HTTP_HOST']);
    nfeio_create_tables();
}
