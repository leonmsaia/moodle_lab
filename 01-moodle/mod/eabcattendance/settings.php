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
 * Eabcattendance plugin settings
 *
 * @package    mod_eabcattendance
 * @copyright  2013 Netspot, Tim Lock.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once(dirname(__FILE__).'/lib.php');
    require_once(dirname(__FILE__).'/locallib.php');

    $tabmenu = eabcattendance_print_settings_tabs();

    $settings->add(new admin_setting_heading('eabcattendance_header', '', $tabmenu));

    $plugininfos = core_plugin_manager::instance()->get_plugins_of_type('local');

	
	$settings->add(new admin_setting_configtext(
												'eabcattendance/tokenapi',
												'Token API',
												'Token API',
												'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJncnAiOiJNVVRVQUwiLCJ2cnNuIjoiMi4wIiwiYXBwIjoiQXBsaWNhY2nDs24gZGUgY29uc29sYSIsInJvbGUiOiJJTkdFTklFUk8gREUgU09QT1JURSIsInJvbGVJZCI6IjE1IiwiZXhwIjoxNzUxOTc4MjkzLCJpc3MiOiJNdXR1YWwuSUEuVG9rZW5Qb3J0YWwiLCJhdWQiOiJNdXR1YWwuSUEuQVBJIn0.1ABpOhjJKgsMvM8Nxl3L1MqWidiHwsCLJgUx5TyPTGs',
												PARAM_TEXT 
												));
												
	$settings->add(new admin_setting_configtext(
												'eabcattendance/subscriptionkey',
												'Subscription key',
												'Subscription key',
												'b83bc53fb3d046008703dddddeb5e0fd',
												PARAM_TEXT 
												));
												
	$settings->add(new admin_setting_configtext(
												'eabcattendance/endpointparticipantes',
												'Endpoint participantes',
												'Endpoint participantes',
												'https://qaapimeus2apiadh001.azure-api.net/integracion/inscripciones/participantes',
												PARAM_TEXT 
												));
    $settings->add(new admin_setting_configtext(
                                                    'eabcattendance/endpointupdateparticipantes',
                                                    'Endpoint update participantes',
                                                    'Endpoint update participantes',
                                                    'https://qaapimeus2apiadh001.azure-api.net/integracion/updDelInscripcion',
                                                    PARAM_TEXT 
                                                    ));
    $settings->add(new admin_setting_configtext(
                                                'eabcattendance/endpointdeleteparticipante',
                                                'Endpoint Eliminar participante',
                                                'Endpoint Eliminar participante',
                                                'https://qaapimeus2apiadh001.azure-api.net/integracion/updDelInscripcion',
                                                PARAM_TEXT 
                                                ));

	$settings->add(new admin_setting_configtextarea(
												'eabcattendance/guidroles',
												'GUID / Roles participantes',
												'GUID / Roles participantes',
												"c4bcc283-fe15-ea11-a811-000d3a4f6db7/Dirigente sindical\n".
												"49bcba8f-fe15-ea11-a811-000d3a4f6db7/Empleador\n".
												"1320cd77-fe15-ea11-a811-000d3a4f6db7/Miembro comité paritario\n".
												"498dc57d-fe15-ea11-a811-000d3a4f6db7/Monitor o delegado\n".
												"80af5dc9-fd15-ea11-a811-000d3a4f6db7/Profesional SST\n".
												"56b5d471-fe15-ea11-a811-000d3a4f6db7/Trabajador",
												PARAM_TEXT 
												));


    // Paging options.
    $options = array(
          0 => get_string('donotusepaging', 'eabcattendance'),
         25 => 25,
         50 => 50,
         75 => 75,
         100 => 100,
         250 => 250,
         500 => 500,
         1000 => 1000,
    );

    $settings->add(new admin_setting_configselect('eabcattendance/resultsperpage',
        get_string('resultsperpage', 'eabcattendance'), get_string('resultsperpage_desc', 'eabcattendance'), 25, $options));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/studentscanmark',
        get_string('studentscanmark', 'eabcattendance'), get_string('studentscanmark_desc', 'eabcattendance'), 1));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/studentscanmarksessiontime',
        get_string('studentscanmarksessiontime', 'eabcattendance'),
        get_string('studentscanmarksessiontime_desc', 'eabcattendance'), 1));

    $settings->add(new admin_setting_configtext('eabcattendance/studentscanmarksessiontimeend',
        get_string('studentscanmarksessiontimeend', 'eabcattendance'),
        get_string('studentscanmarksessiontimeend_desc', 'eabcattendance'), '60', PARAM_INT));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/subnetactivitylevel',
        get_string('subnetactivitylevel', 'eabcattendance'),
        get_string('subnetactivitylevel_desc', 'eabcattendance'), 1));

    $options = array(
        EABCATT_VIEW_ALL => get_string('all', 'eabcattendance'),
        EABCATT_VIEW_ALLPAST => get_string('allpast', 'eabcattendance'),
        EABCATT_VIEW_NOTPRESENT => get_string('below', 'eabcattendance', 'X'),
        EABCATT_VIEW_MONTHS => get_string('months', 'eabcattendance'),
        EABCATT_VIEW_WEEKS => get_string('weeks', 'eabcattendance'),
        EABCATT_VIEW_DAYS => get_string('days', 'eabcattendance')
    );

    $settings->add(new admin_setting_configselect('eabcattendance/defaultview',
        get_string('defaultview', 'eabcattendance'),
            get_string('defaultview_desc', 'eabcattendance'), EABCATT_VIEW_ALL, $options));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/multisessionexpanded',
        get_string('multisessionexpanded', 'eabcattendance'),
        get_string('multisessionexpanded_desc', 'eabcattendance'), 0));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/showsessiondescriptiononreport',
        get_string('showsessiondescriptiononreport', 'eabcattendance'),
        get_string('showsessiondescriptiononreport_desc', 'eabcattendance'), 0));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/studentrecordingexpanded',
        get_string('studentrecordingexpanded', 'eabcattendance'),
        get_string('studentrecordingexpanded_desc', 'eabcattendance'), 1));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/enablecalendar',
        get_string('enablecalendar', 'eabcattendance'),
        get_string('enablecalendar_desc', 'eabcattendance'), 1));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/enablewarnings',
        get_string('enablewarnings', 'eabcattendance'),
        get_string('enablewarnings_desc', 'eabcattendance'), 0));

    $name = new lang_string('defaultsettings', 'mod_eabcattendance');
    $description = new lang_string('defaultsettings_help', 'mod_eabcattendance');
    $settings->add(new admin_setting_heading('defaultsettings', $name, $description));

    $settings->add(new admin_setting_configtext('eabcattendance/subnet',
        get_string('requiresubnet', 'eabcattendance'), get_string('requiresubnet_help', 'eabcattendance'), '', PARAM_RAW));

    $name = new lang_string('defaultsessionsettings', 'mod_eabcattendance');
    $description = new lang_string('defaultsessionsettings_help', 'mod_eabcattendance');
    $settings->add(new admin_setting_heading('defaultsessionsettings', $name, $description));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/calendarevent_default',
        get_string('calendarevent', 'eabcattendance'), '', 1));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/absenteereport_default',
        get_string('includeabsentee', 'eabcattendance'), '', 1));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/studentscanmark_default',
        get_string('studentscanmark', 'eabcattendance'), '', 0));

    $options = eabcattendance_get_automarkoptions();

    $settings->add(new admin_setting_configselect('eabcattendance/automark_default',
        get_string('automark', 'eabcattendance'), '', 0, $options));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/randompassword_default',
        get_string('randompassword', 'eabcattendance'), '', 0));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/includeqrcode_default',
        get_string('includeqrcode', 'eabcattendance'), '', 0));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/autoassignstatus',
        get_string('autoassignstatus', 'eabcattendance'), '', 0));

    $options = eabcattendance_get_sharedipoptions();
    $settings->add(new admin_setting_configselect('eabcattendance/preventsharedip',
        get_string('preventsharedip', 'eabcattendance'),
        '', EABCATTENDANCE_SHAREDIP_DISABLED, $options));

    $settings->add(new admin_setting_configtext('eabcattendance/preventsharediptime',
        get_string('preventsharediptime', 'eabcattendance'), get_string('preventsharediptime_help', 'eabcattendance'), '', PARAM_RAW));

    $name = new lang_string('defaultwarningsettings', 'mod_eabcattendance');
    $description = new lang_string('defaultwarningsettings_help', 'mod_eabcattendance');
    $settings->add(new admin_setting_heading('defaultwarningsettings', $name, $description));

    $options = array();
    for ($i = 1; $i <= 100; $i++) {
        $options[$i] = "$i%";
    }
    $settings->add(new admin_setting_configselect('eabcattendance/warningpercent',
        get_string('warningpercent', 'eabcattendance'), get_string('warningpercent_help', 'eabcattendance'), 70, $options));

    $options = array();
    for ($i = 1; $i <= 50; $i++) {
        $options[$i] = "$i";
    }
    $settings->add(new admin_setting_configselect('eabcattendance/warnafter',
        get_string('warnafter', 'eabcattendance'), get_string('warnafter_help', 'eabcattendance'), 5, $options));

    $settings->add(new admin_setting_configselect('eabcattendance/maxwarn',
        get_string('maxwarn', 'eabcattendance'), get_string('maxwarn_help', 'eabcattendance'), 1, $options));

    $settings->add(new admin_setting_configcheckbox('eabcattendance/emailuser',
        get_string('emailuser', 'eabcattendance'), get_string('emailuser_help', 'eabcattendance'), 1));

    $settings->add(new admin_setting_configtext('eabcattendance/emailsubject',
        get_string('emailsubject', 'eabcattendance'), get_string('emailsubject_help', 'eabcattendance'),
        get_string('emailsubject_default', 'eabcattendance'), PARAM_RAW));


    $settings->add(new admin_setting_configtextarea('eabcattendance/emailcontent',
        get_string('emailcontent', 'eabcattendance'), get_string('emailcontent_help', 'eabcattendance'),
        get_string('emailcontent_default', 'eabcattendance'), PARAM_RAW));
    
    $settings->add(new admin_setting_configtext('eabcattendance/rolwscreateactivity',
        get_string('rolwscreateactivity', 'eabcattendance'), get_string('rolwscreateactivity', 'eabcattendance'),
        '', PARAM_RAW));
    

}
