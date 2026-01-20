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
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $ADMIN, $CFG;

/** @var bool $hassiteconfig */
if($hassiteconfig) {
    // Añadir la sección principal del plugin (si no existe ya).
    $settings = new admin_settingpage('tool_sessionmigrate', get_string('pluginname', 'tool_sessionmigrate'));
    $ADMIN->add('tools', $settings);

    // Páginas externas del plugin.
    $ADMIN->add('tools', new admin_externalpage(
        'tool_sessionmigrate_migrationbydate',
        get_string('migrationbydate', 'tool_sessionmigrate'),
        new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php')
    ));

    // Página nueva: migración por curso + rango de fechas.
    $ADMIN->add('tools', new admin_externalpage(
        'tool_sessionmigrate_migrationbycourseanddate',
        get_string('migrationbycourseanddate', 'tool_sessionmigrate'),
        new moodle_url('/admin/tool/sessionmigrate/pages/migrationbycourseanddate.php')
    ));

    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_coursearch',
            get_string('coursearch', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/coursearch.php'),
            'moodle/site:config'
        )
    );
    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_closedgroupsqueue',
            get_string('closedgroupsqueue', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/closedgroupsqueue.php'),
            'moodle/site:config'
        )
    );
    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_sessionsearch',
            get_string('sessionsearch', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/sessionsearch.php'),
            'moodle/site:config'
        )
    );
    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_duplicates',
            get_string('duplicatesearch', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/duplicates.php'),
            'moodle/site:config'
        )
    );
    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_migrationbydate',
            get_string('migrationbydate', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php'),
            'moodle/site:config'
        )
    );
    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_migrationbysessions',
            get_string('migrationbysessions', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/migrationbysessions.php'),
            'moodle/site:config'
        )
    );

    $ADMIN->add(
        'sessionmigratefolder',
        new admin_externalpage(
            'sessionmigrate_log',
            get_string('logviewer', 'tool_sessionmigrate'),
            new moodle_url('/admin/tool/sessionmigrate/pages/log.php'),
            'moodle/site:config'
        )
    );

    
}