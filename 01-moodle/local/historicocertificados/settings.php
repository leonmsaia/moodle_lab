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

$settings = new admin_settingpage('local_historicocertificados', get_string('pluginname', 'local_historicocertificados'));
$ADMIN->add('localplugins', $settings);
if ($ADMIN->fulltree) {
    
    $settings->add(new admin_setting_configcheckbox(
        'local_historicocertificados/enable_historic_cert',
        'Activar Histórico de certificados',
        'Activar Histórico de certificados',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_historicocertificados/enable_historic_cert_elearning',
        'Activar Histórico de certificados Elearning',
        'Activar Histórico de certificados Elearning',
        0
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_historicocertificados/enable_historic_cert_syp',
        'Activar Histórico de certificados Streaming y presenciales',
        'Activar Histórico de certificados Streaming y presenciales',
        0
    ));
}