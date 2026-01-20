<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings = new admin_settingpage('local_mutual', 'Configuración Servicio de personas');

    // Create 
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
            'local_mutual/buscar_persona_service',
            'Servicio xml de Buscar personas',
            'Servicio xml de Buscar personas',
            'https://64c6ea59-a55d-4c82-b1d3-11b2e86effe9.mock.pstmn.io/', 
            PARAM_TEXT 
    ));

    $settings->add(new admin_setting_configtext(
                'local_mutual/xml_request',
                'Xml request',
                'Xml request',
                'http://cl.mutual.ws', 
                PARAM_TEXT 
        ));
}