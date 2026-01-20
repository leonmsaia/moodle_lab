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
 * Structure step to restore one eabcattendance activity
 *
 * @package    mod_eabcattendance
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_eabcattendance_activity_task
 *
 * @copyright  2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_eabcattendance_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore workflow.
     *
     * @return restore_path_element $structure
     */
    protected function define_structure() {

        $paths = array();

        $userinfo = $this->get_setting_value('userinfo'); // Are we including userinfo?

        // XML interesting paths - non-user data.
        $paths[] = new restore_path_element('eabcattendance', '/activity/eabcattendance');

        $paths[] = new restore_path_element('eabcattendance_status',
                       '/activity/eabcattendance/statuses/status');

        $paths[] = new restore_path_element('eabcattendance_warning',
            '/activity/eabcattendance/warnings/warning');

        $paths[] = new restore_path_element('eabcattendance_coursegroup',
                       '/activity/eabcattendance/coursegroups/coursegroup');

        $paths[] = new restore_path_element('eabcattendance_session',
                       '/activity/eabcattendance/sessions/session');

        // End here if no-user data has been selected.
        if (!$userinfo) {
            return $this->prepare_activity_structure($paths);
        }

        // XML interesting paths - user data.
        $paths[] = new restore_path_element('eabcattendance_log',
                       '/activity/eabcattendance/sessions/session/logs/log');

        $paths[] = new restore_path_element('eabcattendance_inscripcion',
                       '/activity/eabcattendance/sessions/session/inscripciones/inscripcion');

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process an eabcattendance restore.
     *
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();

        // Insert the eabcattendance record.
        $newitemid = $DB->insert_record('eabcattendance', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process eabcattendance status restore
     * @param object $data The data in object form
     * @return void
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_eabcattendance_status($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->eabcattendanceid = $this->get_new_parentid('eabcattendance');

        $newitemid = $DB->insert_record('eabcattendance_statuses', $data);
        $this->set_mapping('eabcattendance_status', $oldid, $newitemid);
    }

    /**
     * Process eabcattendance warning restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance_warning($data) {
        global $DB;

        $data = (object)$data;

        $data->idnumber = $this->get_new_parentid('eabcattendance');

        $DB->insert_record('eabcattendance_warning', $data);
    }

    /**
     * Process eabcattendance coursegroup restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance_coursegroup($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->curso = $this->get_courseid();

        $newitemid = $DB->insert_record('eabcattendance_course_groups', $data);
        $this->set_mapping('eabcattendance_coursegroup', $oldid, $newitemid);
    }

    /**
     * Process eabcattendance session restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance_session($data) {
        global $DB;

        $userinfo = $this->get_setting_value('userinfo'); // Are we including userinfo?

        $data = (object)$data;
        $oldid = $data->id;

        $data->eabcattendanceid = $this->get_new_parentid('eabcattendance');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->sessdate = $this->apply_date_offset($data->sessdate);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->caleventid = $this->get_mappingid('event', $data->caleventid);

        if ($userinfo) {
            $data->lasttaken = $this->apply_date_offset($data->lasttaken);
            $data->lasttakenby = $this->get_mappingid('user', $data->lasttakenby);
        } else {
            $data->lasttaken = 0;
            $data->lasttakenby = 0;
        }

        $newitemid = $DB->insert_record('eabcattendance_sessions', $data);
        $data->id = $newitemid;
        $this->set_mapping('eabcattendance_session', $oldid, $newitemid, true);

        // Create Calendar event.
        eabcattendance_create_calendar_event($data);
    }

    /**
     * Process eabcattendance log restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance_log($data) {
        global $DB;

        $data = (object)$data;

        $data->sessionid = $this->get_mappingid('eabcattendance_session', $data->sessionid);
        $data->studentid = $this->get_mappingid('user', $data->studentid);
        $data->statusid = $this->get_mappingid('eabcattendance_status', $data->statusid);
        $statusset = explode(',', $data->statusset);
        foreach ($statusset as $st) {
            $st = $this->get_mappingid('eabcattendance_status', $st);
        }
        $data->statusset = implode(',', $statusset);
        $data->timetaken = $this->apply_date_offset($data->timetaken);
        $data->takenby = $this->get_mappingid('user', $data->takenby);

        $DB->insert_record('eabcattendance_log', $data);
    }

    /**
     * Process eabcattendance inscripcion restore
     * @param object $data The data in object form
     * @return void
     */
    protected function process_eabcattendance_inscripcion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->id_sesion_moodle = $this->get_mappingid('eabcattendance_session', $data->id_sesion_moodle);

        $newitemid = $DB->insert_record('inscripciones_back', $data);
        $this->set_mapping('eabcattendance_inscripcion', $oldid, $newitemid);
    }

    /**
     * Once the database tables have been fully restored, restore the files
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_eabcattendance', 'session', 'eabcattendance_session');
    }
}
