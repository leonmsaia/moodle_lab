<?php

defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('local_migrategrades', get_string('settingstitle', 'local_migrategrades'));
$ADMIN->add('localplugins', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('local_migrategrades/dbsettings', get_string('dbsettings', 'local_migrategrades'), ''));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbhost',
        get_string('old_dbhost', 'local_migrategrades'),
        '',
        '127.0.0.1',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbport',
        get_string('old_dbport', 'local_migrategrades'),
        '',
        '3306',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbname',
        get_string('old_dbname', 'local_migrategrades'),
        '',
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbuser',
        get_string('old_dbuser', 'local_migrategrades'),
        '',
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_migrategrades/old_dbpass',
        get_string('old_dbpass', 'local_migrategrades'),
        '',
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbprefix',
        get_string('old_dbprefix', 'local_migrategrades'),
        '',
        'mdl_',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_dbcharset',
        get_string('old_dbcharset', 'local_migrategrades'),
        '',
        'utf8mb4',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_heading('local_migrategrades/gradesettings', get_string('gradesettings', 'local_migrategrades'), ''));

    $settings->add(new admin_setting_configtext(
        'local_migrategrades/old_grade_history_from',
        get_string('old_grade_history_from', 'local_migrategrades'),
        get_string('old_grade_history_from_help', 'local_migrategrades'),
        '2025-01-01 00:00:00',
        PARAM_TEXT
    ));
}
