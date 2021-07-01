<?php

defined('WHMCS') or exit;

use WHMCS\Database\Capsule;

if (!function_exists('nfeio_create_tables')) {
    /**
     * nfeio, mod_nfeio_custom_configs
     */
    function nfeio_create_tables() {
        if (!Capsule::schema()->hasTable('nfeio')) {
            try {
                Capsule::schema()->create('nfeio', function ($table) {
                    $table->increments('id');
                    $table->string('invoice_id');
                    $table->string('user_id');
                    $table->string('nfe_id');
                    $table->string('status');
                    $table->decimal('services_amount',$precision = 16,$scale = 2);
                    $table->string('environment');
                    $table->string('flow_status');
                    $table->string('pdf');
                    $table->string('rpsSerialNumber');
                    $table->string('rpsNumber');
                    $table->string('created_at');
                    $table->string('updated_at');
                    $table->string('service_code')->nullable(true);
                    $table->string('tics')->nullable(true);
                });
            } catch (\Exception $e) {
                nfeio_log('nfeio', 'nfeio_create_tables: nfeio', '', $e->getMessage(), '');
            }
        }

        if (!Capsule::schema()->hasTable('mod_nfeio_custom_configs')) {
            try {
                Capsule::schema()->create('mod_nfeio_custom_configs', function ($table) {
                    $table->increments('id');
                    $table->integer('client_id');
                    $table->string('key');
                    $table->string('value');
                });
            } catch (\Exception $e) {
                nfeio_log('nfeio', 'nfeio_create_tables: mod_nfeio_custom_configs', '', $e->getMessage(), '');
            }
        }

        if (!Capsule::schema()->hasTable('tblproductcode')) {
            try {
                $pdo = Capsule::connection()->getPdo();
                $pdo->beginTransaction();

                $statement = $pdo->prepare('CREATE TABLE tblproductcode (
                            id int(10) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                            product_id int(10) NOT NULL,
                            code_service int(10) NOT NULL,
                            create_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            update_at TIMESTAMP NULL,
                            ID_user int(10) NOT NULL)'
                        );

                $statement->execute();
                $pdo->commit();
            } catch (\Exception $e) {
                nfeio_log('nfeio', 'nfeio_create_tables: tblproductcode', '', $e->getMessage(), '');
                $pdo->rollBack();
            }
        }
    }
}

if (!function_exists('set_custom_field_ini_date')) {
    function set_custom_field_ini_date() {
        $data = getTodaysDate(false);
        $dataAtual = toMySQLDate($data);

        try {
            if (
                Capsule::table('tbladdonmodules')
                    ->where('module', '=', 'gofasnfeio')
                    ->where('setting', '=', 'initial_date')
                    ->count() < 1
            ) {
                Capsule::table('tbladdonmodules')
                ->insert([
                    'module' => 'gofasnfeio',
                    'setting' => 'initial_date',
                    'value' => $dataAtual
                ]);
            }
        } catch (\Exception $e) {
            nfeio_log('nfeio', 'set_custom_field_ini_date: initial_date', '', $e->getMessage(), '');
        }
    }
}
