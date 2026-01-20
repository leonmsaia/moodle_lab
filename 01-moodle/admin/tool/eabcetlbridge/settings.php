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
 * @package     tool_eabcetlbridge
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN, $CFG;

use tool_eabcetlbridge\url;

if ($hassiteconfig) {
    $ADMIN->add(
        'tools',
        new admin_category('eabcetlbridgefolder', get_string('pluginname', 'tool_eabcetlbridge'))
    );

    $ADMIN->add(
        'eabcetlbridgefolder',
        new admin_externalpage(
            'viewbatch_files',
            'Listado de Archivos de Migración',
            url::viewbatch_files(),
            'moodle/site:config'
        )
    );

    $ADMIN->add(
        'eabcetlbridgefolder',
        new admin_externalpage(
            'viewconfigs',
            'Listado de Configuraciones de Estrategias de Migración',
            url::viewconfigs(),
            'moodle/site:config'
        )
    );

    $ADMIN->add(
        'eabcetlbridgefolder',
        new admin_externalpage(
            'viewplanners',
            'Listado de Planificadores de Migración',
            url::viewplanner(),
            'moodle/site:config'
        )
    );

    $ADMIN->add(
        'eabcetlbridgefolder',
        new admin_externalpage(
            'viewadhoc_tasks',
            'Listado de Tareas Adhoc',
            url::viewadhoc_tasks(),
            'moodle/site:config'
        )
    );

    $settings = new admin_settingpage('tool_eabcetlbridge', 'Ajustes de e-ABC para migración de datos');

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/planner_limitnum',
        'Lote de registros a procesar por el Planificador',
        '',
        1000,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/planner_status_update_limitnum',
        'Lote de registros a procesar por el Planificador de Actualización de Estado',
        '',
        1000,
        PARAM_INT
    ));


    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/adhoc_for_getting_external_grades_limitnum',
        'Lote de tareas adhoc para solicitar calificaciones en plataforma externa',
        '',
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/idmapper_limitnum',
        'Lote de registros a procesar por el ID Mapper',
        '',
        1000,
        PARAM_INT
    ));

    $choices = [
        10 => 'Si, usar el método personalizado, ideal para cursos grandes',
        20 => 'No, usar el método por defecto, ideal para cursos pequeños'
    ];
    $settings->add(new admin_setting_configselect(
        'tool_eabcetlbridge/get_grades_with_pagination',
        'Obtener calificaciones con paginación',
        '',
        0,
        $choices
    ));

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/clean_overridden_grades_startdate',
        'Fecha de inicio para limpiar calificaciones sobreescritas',
        '',
        1759190400,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/clean_overridden_grades_endate',
        'Fecha de fin para limpiar calificaciones sobreescritas',
        '',
        1760655036,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/clean_overridden_grades_limitnum',
        'Lote de registros a procesar para limpiar calificaciones sobreescritas',
        '',
        1000,
        PARAM_INT
    ));

    // URL de la plataforma externa.
    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/externalmoodleurl',
        'URL de la plataforma externa',
        'Example: https://MOODLEDOMAIN/webservice/rest/server.php',
        '',
        PARAM_URL,
    ));

    // Token de la plataforma externa.
    $settings->add(new admin_setting_configtext(
        'tool_eabcetlbridge/externalmoodletoken',
        'Token de la plataforma externa',
        '',
        '',
        PARAM_TEXT,
    ));

    $ADMIN->add('eabcetlbridgefolder', $settings);

}

