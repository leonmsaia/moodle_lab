<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @category    admin
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$settings = new admin_settingpage('local_sso', get_string('settingstitle', 'local_sso'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext(
        'local_sso/url_login',
        get_string('url_login', 'local_sso'),
        get_string('url_login', 'local_sso'),
        'https://172.177.177.139/dx/api/core/v1/auth/login',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_sso/url_validate',
        get_string('url_validate', 'local_sso'),
        get_string('url_validate', 'local_sso'),
        'https://172.177.177.139/dx/api/core/v1/auth/validate',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_sso/url_moodle',
        'Url moodle',
        'Url moodle',
        'http://localhost:1032',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_sso/wstoken',
        'wstokwstokenen',
        'wstoken',
        '123456789abcdef0123456789abcdef',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'local_sso/sso_secret',
        'Secret SSO',
        'Secret SSO',
        'pgEvk1VCNkFzFhGziNubJRgBprTXxdIX',
        PARAM_RAW
    ));


    // $settings->add(new admin_setting_configtext(
    //     'local_sso/url_moodle_45',
    //     'Url moodle 35',
    //     'Url moodle 35',
    //     '',
    //     PARAM_RAW
    // ));

    $settings->add(new admin_setting_configcheckbox(
        'local_sso/enable_manual_login_sso',
        'Activar inicio de sesión manual SSO',
        'Activar inicio de sesión manual SSO',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_sso/enable_menu_login_sso',
        'Activar inicio de sesión en menu',
        'Activar inicio de sesión en menu',
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_sso/title_external_site',
        'Ir a mis capacitaciones',
        'Ir a mis capacitaciones',
        'Ir a mis capacitaciones',
        PARAM_RAW
    ));
    
    $settings->add(new admin_setting_configtext(
        'local_sso/api_buscar_trabajador',
        'Api mutual buscar trabajador',
        'Api mutual buscar trabajador',
        'http://wls-prd.mutual.cl/MTOSB_GestionUsuarioTrabajador/buscaTrabajadorService',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_sso/enable_login_sso_msg',
        'Activar mensaje de inicio de sesión SVT',
        'Activar mensaje de inicio de sesión SVT',
        0
    ));

    
    $settings->add(new admin_setting_configtext(
        'local_sso/migracion_bearer',
        'Bearer para migración',
        'Bearer para migración',
        '123456',
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_sso/enable_login_recover_pass',
        'Recuperar contraseña externa',
        'Activar recuperación de contraseña externa',
        0
    ));
}