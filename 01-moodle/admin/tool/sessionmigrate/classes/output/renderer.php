<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute and/or modify
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
 * Custom renderer for the session migration tool.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace tool_sessionmigrate\output;

use html_writer;
use moodle_url;
use tabobject;

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot . '/theme/mutualseguridad/classes/output/renderer.php');

class renderer extends \theme_mutualseguridad\output\renderer {
    /**
     * Renders the secondary navigation for the tool.
     *
     * @return string
     */
    public function secondary_navigation() {
        global $PAGE;

        // URL actual.
        $currenturl = $PAGE->url;

        // Definición de las pestañas disponibles.
        $tabs = [];
        $params = $currenturl->params();

        $coursearchurl = new moodle_url('/admin/tool/sessionmigrate/pages/coursearch.php');
        $logurl = new moodle_url('/admin/tool/sessionmigrate/pages/log.php');

        $tabs[] = new tabobject(
            'coursearch',
            $coursearchurl,
            get_string('coursearch', 'tool_sessionmigrate')
        );

        $closedgroupsqueueurl = new moodle_url('/admin/tool/sessionmigrate/pages/closedgroupsqueue.php');
        $tabs[] = new tabobject(
            'closedgroupsqueue',
            $closedgroupsqueueurl,
            get_string('closedgroupsqueue', 'tool_sessionmigrate')
        );

        $sessionsearchurl = new moodle_url('/admin/tool/sessionmigrate/pages/sessionsearch.php');
        $tabs[] = new tabobject(
            'sessionsearch',
            $sessionsearchurl,
            get_string('sessionsearch', 'tool_sessionmigrate')
        );

        $duplicatesearchurl = new moodle_url('/admin/tool/sessionmigrate/pages/duplicates.php');
        $tabs[] = new tabobject(
            'duplicatesearch',
            $duplicatesearchurl,
            get_string('duplicatesearch', 'tool_sessionmigrate')
        );

        $migrationbydateurl = new moodle_url('/admin/tool/sessionmigrate/pages/migrationbydate.php');
        $tabs[] = new tabobject(
            'migrationbydate',
            $migrationbydateurl,
            get_string('migrationbydate', 'tool_sessionmigrate')
        );

        // NUEVA PESTAÑA: migración por curso + rango de fechas
        $migrationbycourseanddateurl = new moodle_url('/admin/tool/sessionmigrate/pages/migrationbycourseanddate.php');
        $tabs[] = new tabobject(
            'migrationbycourseanddate',
            $migrationbycourseanddateurl,
            get_string('migrationbycourseanddate', 'tool_sessionmigrate')
        );

        $migrationbysessionsurl = new moodle_url('/admin/tool/sessionmigrate/pages/migrationbysessions.php');
        $tabs[] = new tabobject(
            'migrationbysessions',
            $migrationbysessionsurl,
            get_string('migrationbysessions', 'tool_sessionmigrate')
        );

        $tabs[] = new tabobject(
            'log',
            $logurl,
            get_string('logviewer', 'tool_sessionmigrate')
        );


        // Determinar la pestaña actual según la URL.
        $currenttab = '';
        $path = $currenturl->get_path();
        $path = explode('/', $path);
        if (!empty($path)) {
            $currenttab = basename(end($path));
            $currenttab = str_replace('.php', '', $currenttab);
        }

        // Validar coincidencias explícitas.
        if ($currenturl->compare($coursearchurl, URL_MATCH_BASE)) {
            $currenttab = 'coursearch';
        } else if ($currenturl->compare($closedgroupsqueueurl, URL_MATCH_BASE)) {
            $currenttab = 'closedgroupsqueue';
        } else if ($currenturl->compare($logurl, URL_MATCH_BASE)) {
            $currenttab = 'log';
        } else if ($currenturl->compare($sessionsearchurl, URL_MATCH_BASE)) {
            $currenttab = 'sessionsearch';
        } else if ($currenturl->compare($duplicatesearchurl, URL_MATCH_BASE)) {
            $currenttab = 'duplicatesearch';
        } else if ($currenturl->compare($migrationbydateurl, URL_MATCH_BASE)) {
            $currenttab = 'migrationbydate';
        } else if ($currenturl->compare($migrationbycourseanddateurl, URL_MATCH_BASE)) {
            $currenttab = 'migrationbycourseanddate';
        } else if ($currenturl->compare($migrationbysessionsurl, URL_MATCH_BASE)) {
            $currenttab = 'migrationbysessions';
        }

        // Crear el árbol de pestañas.
        if (count($tabs) > 1) {
            $tabtree = new \tabtree($tabs, $currenttab);
            return $this->render($tabtree);
        }

        return null;
    }

}
