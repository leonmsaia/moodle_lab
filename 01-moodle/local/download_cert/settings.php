<?php

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {


    $settings = new admin_settingpage('local_download_cert', 'Configuración certificado de cursos');

    // Create 
    $ADMIN->add('localplugins', $settings);

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
            'local_download_cert/completion_attendance',
            get_string('completion_attendance', 'local_download_cert'),
            get_string('completion_attendance_desc', 'local_download_cert'),
            100, // This is the default value
            PARAM_INT // This is the type of Parameter this config is
    ));

    // Add a setting field to the settings for this page
    $settings->add(new admin_setting_configtext(
        'local_download_cert/completion_mod_ilerning',
        get_string('completion_mod_ilerning', 'local_download_cert'),
        get_string('completion_mod_ilerning_desc', 'local_download_cert'),
        'feedback', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
        'local_download_cert/completion_mod_presencial',
        get_string('completion_mod_presencial', 'local_download_cert'),
        get_string('completion_mod_presencial_desc', 'local_download_cert'),
        'quiz', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
        'local_download_cert/config_years_certificate',
        get_string('completion_mod_presencial', 'local_download_cert'),
        get_string('completion_mod_presencial_desc', 'local_download_cert'),
        'quiz', // This is the default value
        PARAM_TEXT // This is the type of Parameter this config is
    ));

    $settings->add(new admin_setting_configtext(
        'local_download_cert/config_years_certificate',
        get_string('completion_mod_presencial', 'local_download_cert'),
        get_string('completion_mod_presencial_desc', 'local_download_cert'),
        3, 
        PARAM_INT 
    ));

}
