<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings = new admin_settingpage('local_encuesta_movilidad', 'Configuración encuesta movilidad');

    // Create 
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
            'local_encuesta_movilidad/name_activity',
            get_string('name_activity', 'local_encuesta_movilidad'),
            get_string('name_activity_desc', 'local_encuesta_movilidad'),
            'Encuesta de Movilidad', // This is the default value
            PARAM_TEXT // This is the type of Parameter this config is
    ));

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
        'local_encuesta_movilidad/text_activity',
        get_string('text_activity', 'local_encuesta_movilidad'),
        get_string('text_activity_desc', 'local_encuesta_movilidad'),
        'Para completar la Encuesta de Movilidad, hacer un click aquí:', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
        'local_encuesta_movilidad/text_button_activity',
        get_string('text_button_activity', 'local_encuesta_movilidad'),
        get_string('text_button_activity_desc', 'local_encuesta_movilidad'),
        'Encuesta de Movilidad', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
        'local_encuesta_movilidad/link_activity',
        get_string('link_activity', 'local_encuesta_movilidad'),
        get_string('link_activity_desc', 'local_encuesta_movilidad'),
        'https://ncv.microsoft.com/XcM2VkZGOd', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

}
