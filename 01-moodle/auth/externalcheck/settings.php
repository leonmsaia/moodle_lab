<?php
defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('auth_externalcheck', 'Remote Auth Settings SVT');
$ADMIN->add('localplugins', $settings);

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox(
        'auth_externalcheck/enable_login_sso_msg',
        'Activar remote auth',
        'Activar remote auth',
        0
    ));
}
