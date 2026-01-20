<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    global $ADMIN;
    $settings = new admin_settingpage('local_companymessage', new lang_string('pluginname', 'local_companymessage'));

    $ADMIN->add('localplugins', $settings);

    // Setting for company RUTs.
    $settings->add(new admin_setting_configtextarea(
        'local_companymessage/companyruts',
        new lang_string('setting_companyruts', 'local_companymessage'),
        new lang_string('setting_companyruts_desc', 'local_companymessage'),
        '',
        PARAM_RAW
    ));

    // Setting for the login message.
    $settings->add(new admin_setting_configtext(
        'local_companymessage/loginmessage',
        new lang_string('setting_loginmessage', 'local_companymessage'),
        new lang_string('setting_loginmessage_desc', 'local_companymessage'),
        'Tareas de mantenimiento',
        PARAM_RAW
    ));

    // Enable maintenance message.
    $settings->add(new admin_setting_configcheckbox(
        'local_companymessage/maintenance_enabled',
        new lang_string('setting_maintenance_enabled', 'local_companymessage'),
        new lang_string('setting_maintenance_enabled_desc', 'local_companymessage'),
        0
    ));

    // Maintenance message.
    $settings->add(new admin_setting_configtextarea(
        'local_companymessage/maintenance_message',
        new lang_string('setting_maintenance_message', 'local_companymessage'),
        new lang_string('setting_maintenance_message_desc', 'local_companymessage'),
        'Plataforma en mantención, es posible que presente intermitencia durante su navegación.',
        PARAM_RAW
    ));

    // Maintenance start time.
    $settings->add(new admin_setting_configtext(
        'local_companymessage/maintenance_starttime',
        new lang_string('setting_maintenance_starttime', 'local_companymessage'),
        new lang_string('setting_maintenance_starttime_desc', 'local_companymessage'),
        '00:00'
    ));
}
