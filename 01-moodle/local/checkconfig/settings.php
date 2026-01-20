<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_checkconfig', get_string('pluginname', 'local_checkconfig'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/activechecks',
        get_string('activechecks', 'local_checkconfig'),
        get_string('activechecks_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/activeexclude',
        get_string('activeexclude', 'local_checkconfig'),
        '',
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/checkenddate',
        get_string('checkenddate', 'local_checkconfig'),
        get_string('checkenddate_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configduration(
        'local_checkconfig/checkenddatethreshold',
        get_string('checkenddatethreshold', 'local_checkconfig'),
        get_string('checkenddatethreshold_desc', 'local_checkconfig'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/checkcompletionenabled',
        get_string('checkcompletionenabled', 'local_checkconfig'),
        get_string('checkcompletionenabled_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/checkhascompletioncriterias',
        get_string('checkhascompletioncriterias', 'local_checkconfig'),
        get_string('checkhascompletioncriterias_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/checkgradepass',
        get_string('checkgradepass', 'local_checkconfig'),
        get_string('checkgradepass_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_checkconfig/checkendedcourse',
        get_string('checkendedcourse', 'local_checkconfig'),
        get_string('checkendedcourse_desc', 'local_checkconfig'),
        false
    ));

    $settings->add(new admin_setting_configduration(
        'local_checkconfig/checkendedcoursethreshold',
        get_string('checkendedcoursethreshold', 'local_checkconfig'),
        get_string('checkendedcoursethreshold_desc', 'local_checkconfig'),
        0,
        PARAM_INT
    ));
}
