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

namespace local_mutualreport\table;

defined('MOODLE_INTERNAL') || die;

use local_mutualreport\utils35;
use moodle_exception;
use core\output\html_writer;
use core_reportbuilder\table\system_report_table;
use local_mutualreport\reportbuilder\local\entities\completion;

/**
 * Consolidated system report dynamic table class.
 *
 * This class fetches data from the local database and then enriches it
 * with data from an external (Moodle 3.5) database, applying specific
 * business logic for consolidation.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_consolidated_report_table extends system_report_table {

    /**
     * Query the db, consolidate with external data, and store results.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @throws moodle_exception
     */
    public function query_db($pagesize, $useinitialsbar = true): void {
        global $DB, $CFG;

        // 1. Get the initial dataset from the local (4.x) database, handling pagination for web view vs. full data for download.
        $recordset = null;
        if (!$this->is_downloading()) {
            $this->pagesize($pagesize, $DB->count_records_sql($this->countsql, $this->countparams));
            $recordset = $DB->get_recordset_sql(
                $this->get_table_sql(),
                $this->sql->params,
                $this->get_page_start(),
                $this->get_page_size()
            );
        } else {
            $recordset = $DB->get_recordset_sql($this->get_table_sql(), $this->sql->params);
        }

        if (!$recordset || !$recordset->valid()) {
            $this->rawdata = [];
            $recordset && $recordset->close();
            return;
        }

        // 2. Prepare for external DB query. If no connection, just return the local data.
        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            $this->rawdata = $recordset;
            return;
        }

        // 3. Collect usernames, course shortnames, and load all local data into an array for processing.
        $usernames = [];
        $courseshortnames = [];
        $localdata = [];
        foreach ($recordset as $row) {
            $usernames[$row->username] = $row->username;
            $courseshortnames[$row->courseshortname] = $row->courseshortname;
            // Create a unique key for each user-course pair.
            $key = $row->username . '::' . $row->courseshortname;
            $localdata[$key] = $row;
        }
        $recordset->close(); // Close original recordset.

        // 4. Fetch corresponding 'approved' records from the external (3.5) database in chunks to avoid memory issues.
        $externalapproved = [];
        if (!empty($usernames) && !empty($courseshortnames)) {
            [$userinsql, $userparams] = $utils35->db->get_in_or_equal(array_values($usernames), SQL_PARAMS_NAMED, 'uname');
            [$courseinsql, $courseparams] = $utils35->db->get_in_or_equal(array_values($courseshortnames), SQL_PARAMS_NAMED, 'cshort');

            // Get external mnethostid from config to optimize the query.
            $config = get_config('local_mutualreport');
            $mnethostid = !empty($config->external_db_mnethostid) ? (int)$config->external_db_mnethostid : 1;
            $mnetparamname = 'mnethostid';

            $sql = "SELECT u.username, c.shortname, gg.finalgrade
                      FROM {user} u
                      JOIN {user_enrolments} ue ON ue.userid = u.id
                      JOIN {enrol} e ON e.id = ue.enrolid
                      JOIN {course} c ON c.id = e.courseid
                      JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                      JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                     WHERE u.mnethostid = :{$mnetparamname}
                       AND u.username {$userinsql}
                       AND c.shortname {$courseinsql}
                       AND gg.finalgrade >= gi.gradepass";

            $params = array_merge([$mnetparamname => $mnethostid], $userparams, $courseparams);
            $externalapproved = $utils35->db->get_records_sql($sql, $params);
        }

        // Get the dynamic alias for the 'estado' column.
        $statuscolumn = $this->report->get_column('completion:estado');
        $statusalias = $statuscolumn->get_column_alias();
        $gradecolumn = $this->report->get_column('completion:calificacionenviada');
        $gradealias = $gradecolumn->get_column_alias();

        // 5. Consolidate the data: If a user-course is 'Aprobado' in the external DB, update the status in our local data.
        foreach ($externalapproved as $extrecord) {
            $key = $extrecord->username . '::' . $extrecord->shortname;
            if (isset($localdata[$key]) &&
                isset($localdata[$key]->{$statusalias}) &&
                isset($localdata[$key]->{$gradealias})) {
                // Prioritize 'Aprobado' status from the external DB.
                if ($localdata[$key]->{$statusalias} != completion::STATUS_APPROVED) {
                    $localdata[$key]->{$statusalias} = completion::STATUS_APPROVED;
                    $localdata[$key]->{$gradealias} = floor($extrecord->finalgrade);
                }
            }
        }

        // 6. Replace the rawdata with the consolidated data array. The tablelib is compatible with an array of objects.
        $this->rawdata = array_values($localdata);
    }

    /**
     * Get the html for the download buttons
     *
     * @return string
     */
    public function download_buttons(): string {
        global $OUTPUT;

        if ($this->report->can_be_downloaded() && !$this->is_downloading()) {
            return $OUTPUT->download_dataformat_selector(
                get_string('downloadas', 'table'),
                new \moodle_url('/local/mutualreport/pages/download35.php'),
                'download',
                [
                    'id' => $this->persistent->get('id'),
                    'parameters' => json_encode($this->report->get_parameters()),
                ]
            );
        }

        return '';
    }

    /**
     * Override start of HTML to remove top pagination.
     */
    public function start_html() {
        // Render the dynamic table header.
        echo $this->get_dynamic_table_html_start();

        // Render button to allow user to reset table preferences.
        echo $this->render_reset_button();

        echo $this->download_buttons();

        $this->wrap_html_start();

        $this->set_caption($this->report::get_name(), ['class' => 'sr-only']);

        echo html_writer::start_tag('div');
        echo html_writer::start_tag('table', $this->attributes) . $this->render_caption();
    }

}
