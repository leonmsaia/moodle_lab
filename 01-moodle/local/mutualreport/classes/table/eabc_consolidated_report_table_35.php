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
use local_mutualreport\reportbuilder\local\entities\completion;

/**
 * Consolidated system report dynamic table class (from external to local).
 *
 * This class fetches data from the external (Moodle 3.5) database and then enriches it
 * with data from the local (Moodle 4.x) database, applying specific
 * business logic for consolidation.
 *
 * @package     local_mutualreport
 * @copyright   2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class eabc_consolidated_report_table_35 extends eabc_external_report_table {

    /**
     * Query the db, consolidate with local data, and store results.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     * @throws moodle_exception
     */
    public function query_db($pagesize, $useinitialsbar = true): void {
        global $DB, $CFG;

        // 1. Get the initial dataset from the external (3.5) database.
        $utils35 = new utils35();
        if (!$utils35->validate_connection()) {
            // Fallback to parent if no external connection.
            parent::query_db($pagesize, $useinitialsbar);
            return;
        }

        $recordset = null;
        if (!$this->is_downloading()) {
            $this->pagesize($pagesize, $this->externaldb->count_records_sql($this->countsql, $this->countparams));
            $recordset = $this->externaldb->get_recordset_sql(
                $this->get_table_sql(),
                $this->sql->params,
                $this->get_page_start(),
                $this->get_page_size()
            );
        } else {
            $recordset = $this->externaldb->get_recordset_sql($this->get_table_sql(), $this->sql->params);
        }

        if (!$recordset || !$recordset->valid()) {
            $this->rawdata = [];
            $recordset && $recordset->close();
            return;
        }

        // 2. Collect usernames, course shortnames, and load all external data into an array for processing.
        $usernames = [];
        $courseshortnames = [];
        $externaldata = [];
        foreach ($recordset as $row) {
            $usernames[$row->username] = $row->username;
            $courseshortnames[$row->courseshortname] = $row->courseshortname;
            // Create a unique key for each user-course pair.
            $key = $row->username . '::' . $row->courseshortname;
            $externaldata[$key] = $row;
        }
        $recordset->close(); // Close original recordset.

        // 3. Fetch corresponding 'approved' records from the local (4.x) database.
        $localapproved = [];
        if (!empty($usernames) && !empty($courseshortnames)) {
            [$userinsql, $userparams] = $DB->get_in_or_equal(array_values($usernames), SQL_PARAMS_NAMED, 'uname');
            [$courseinsql, $courseparams] = $DB->get_in_or_equal(array_values($courseshortnames), SQL_PARAMS_NAMED, 'cshort');

            $sql = "SELECT u.username, c.shortname, gg.finalgrade
                      FROM {user} u
                      JOIN {user_enrolments} ue ON ue.userid = u.id
                      JOIN {enrol} e ON e.id = ue.enrolid
                      JOIN {course} c ON c.id = e.courseid
                      JOIN {grade_items} gi ON gi.courseid = c.id AND gi.itemtype = 'course'
                      JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = u.id
                     WHERE u.mnethostid = :mnethostid
                       AND u.username {$userinsql}
                       AND c.shortname {$courseinsql}
                       AND gg.finalgrade >= gi.gradepass";

            $params = array_merge(['mnethostid' => $CFG->mnet_localhost_id], $userparams, $courseparams);
            $localapproved = $DB->get_records_sql($sql, $params);
        }

        // Get the dynamic alias for the 'estado' column.
        $statuscolumn = $this->report->get_column('completion:estado');
        $statusalias = $statuscolumn->get_column_alias();
        $gradecolumn = $this->report->get_column('completion:calificacionenviada');
        $gradealias = $gradecolumn->get_column_alias();

        // 4. Consolidate the data: If a user-course is 'Aprobado' in the local DB, update the status in our external data.
        foreach ($localapproved as $localrecord) {
            $key = $localrecord->username . '::' . $localrecord->shortname;
            if (isset($externaldata[$key]) &&
                isset($externaldata[$key]->{$statusalias}) &&
                isset($externaldata[$key]->{$gradealias})) {
                // Prioritize 'Aprobado' status and grade from the local DB.
                if ($externaldata[$key]->{$statusalias} != completion::STATUS_APPROVED) {
                    $externaldata[$key]->{$statusalias} = completion::STATUS_APPROVED;
                    $externaldata[$key]->{$gradealias} = floor($localrecord->finalgrade);
                }

            }
        }

        // 5. Replace the rawdata with the consolidated data array.
        $this->rawdata = array_values($externaldata);
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
}
