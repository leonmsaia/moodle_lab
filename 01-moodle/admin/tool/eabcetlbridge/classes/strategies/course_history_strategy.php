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

namespace tool_eabcetlbridge\strategies;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/csvlib.class.php');

use csv_import_reader;

/**
 * Strategy for migrating Course History data from a CSV content.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_history_strategy
{

    /** @var array The result of the import. */
    public $result = null;

    /**
     * Process the CSV content directly from a string.
     *
     * @return array A summary of the import.
     */
    /**
     * Process the CSV content using the provided reader and mapping.
     *
     * @param csv_import_reader $csvimport The CSV import reader object.
     * @param array $mapping The mapping of logical fields to CSV headers.
     * @return array A summary of the import.
     */
    public function process_import(csv_import_reader $csvimport, array $mapping)
    {
        // 1. Initialize logic
        $csvimport->init();

        $unique_entries = []; // Map: RUT . '|' . Course => Record
        $records_processed = 0;

        // Reverse mapping to get column index/name from logical name if needed, 
        // but here $mapping maps 'map_username' => '0' (index) or header name depending on form processing.
        // The form returns the INDEX of the header (from `course_history_mapping_form`).

        $headers = $csvimport->get_columns();

        while ($row = $csvimport->next()) {

            // Extract fields using mapping (which contains indices)
            $rut = $this->get_mapped_value($row, $mapping['map_username']);
            $course_name = $this->get_mapped_value($row, $mapping['map_course']);
            $grade_raw = $this->get_mapped_value($row, $mapping['map_grade']);
            $date_start_raw = $this->get_mapped_value($row, $mapping['map_startdate']);
            $date_end_raw = $this->get_mapped_value($row, $mapping['map_enddate']);

            // Validation: Must have grade.
            $grade_float = (float) str_replace(',', '.', $grade_raw);

            if ($grade_float <= 0 && $grade_raw === '') {
                continue;
            }

            if (empty($rut) || empty($course_name)) {
                continue; // Skip invalid rows
            }

            $key = $rut . '|' . $course_name;

            // Parse dates
            $date_end_ts = $this->parse_date($date_end_raw);
            $date_start_ts = $this->parse_date($date_start_raw);

            // Construct record object
            $record = [
                'rut' => $rut,
                'course' => $course_name,
                'grade' => $grade_float,
                'timecreated' => $date_start_ts,
                'timeend' => $date_end_ts,
                'original_line' => $row // array
            ];

            // duplicate handling logic:
            if (isset($unique_entries[$key])) {
                $existing = $unique_entries[$key];

                // Compare logic
                if ($record['grade'] > $existing['grade']) {
                    $unique_entries[$key] = $record;
                } else if ($record['grade'] == $existing['grade']) {
                    // Tie-breaker: Date
                    if ($record['timecreated'] > $existing['timecreated']) {
                        $unique_entries[$key] = $record;
                    }
                }
            } else {
                $unique_entries[$key] = $record;
            }

            $records_processed++;
        }

        $this->result = [
            'status' => 'success',
            'total_rows_scanned' => $records_processed,
            'unique_records_found' => count($unique_entries),
            'data' => array_values($unique_entries)
        ];

        return $this->result;
    }

    /**
     * Helper to get value from row based on mapping index.
     */
    private function get_mapped_value($row, $index)
    {
        return isset($row[$index]) ? trim($row[$index]) : '';
    }

    /**
     * Process the CSV content directly from a string (Legacy/Manual).
     *
     * @return array A summary of the import.
     */
    public function process_csv() {}

    /**
     * Save changes to the database.
     *
     * @param array $records The filtered records to process.
     * @param array $options Options for processing (e.g. override_grade).
     * @return array Summary of operations.
     */
    public function save_changes(array $records, array $options = [])
    {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $stats = [
            'processed' => 0,
            'enrollments_updated' => 0,
            'completions_created' => 0,
            'completions_updated' => 0,
            'grades_updated' => 0,
            'errors' => 0,
            'users_not_found' => 0,
            'courses_not_found' => 0,
            'not_enrolled' => 0,
        ];

        // Cache for performance
        $user_cache = [];
        $course_cache = [];

        foreach ($records as $record) {
            $stats['processed']++;
            try {
                // 1. Resolve User
                $username = $record['rut'];
                if (isset($user_cache[$username])) {
                    $userid = $user_cache[$username];
                } else {
                    // Try username first, then idnumber
                    $user = $DB->get_record('user', ['username' => $username], 'id');
                    if (!$user) {
                        $user = $DB->get_record('user', ['idnumber' => $username], 'id');
                    }
                    if (!$user) {
                        $stats['users_not_found']++;
                        continue;
                    }
                    $userid = $user->id;
                    $user_cache[$username] = $userid;
                }

                // 2. Resolve Course
                // The implementation plan says check shortname then fullname
                // But the code previously fetched course_name from CSV.
                // However, Moodle search usually relies on shortname for unique mapping or idnumber.
                // Let's assume the CSV provides SHORTNAME or FULLNAME.
                $coursename = $record['course'];
                if (isset($course_cache[$coursename])) {
                    $courseid = $course_cache[$coursename];
                } else {
                    $course = $DB->get_record('course', ['shortname' => $coursename], 'id');
                    if (!$course) {
                        $course = $DB->get_record('course', ['fullname' => $coursename], 'id');
                    }
                    if (!$course) {
                        $stats['courses_not_found']++;
                        continue;
                    }
                    $courseid = $course->id;
                    $course_cache[$coursename] = $courseid;
                }

                // 3. Update Manual Enrollment
                $updated_enrol = $this->update_manual_enrollment($userid, $courseid, $record['timecreated']);
                if ($updated_enrol) {
                    $stats['enrollments_updated']++;
                }

                // 4. Update/Create Completion
                // Only if we have an end date (completion date)
                if ($record['timeend'] > 0) {
                    $completion_result = $this->update_completion($userid, $courseid, $record['timeend'], $record['timecreated']);
                    if ($completion_result === 'created') {
                        $stats['completions_created']++;
                    } elseif ($completion_result === 'updated') {
                        $stats['completions_updated']++;
                    }
                }

                // 5. Update Gradebook (Course Total)
                if (!empty($options['override_grade']) && $record['grade'] !== '') {
                    $updated_grade = $this->update_gradebook_course_total($userid, $courseid, $record['grade']);
                    if ($updated_grade) {
                        $stats['grades_updated']++;
                    }
                }
            } catch (\Exception $e) {
                $stats['errors']++;
                // Log error?
            }
        }

        return $stats;
    }

    /**
     * Updates the manual enrollment timecreated.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $timecreated
     * @return bool True if updated.
     */
    private function update_manual_enrollment($userid, $courseid, $timecreated)
    {
        global $DB;

        if ($timecreated <= 0) {
            return false;
        }

        // Find manual enrol instance
        $instance = $DB->get_record('enrol', ['courseid' => $courseid, 'enrol' => 'manual'], '*', IGNORE_MULTIPLE);
        if (!$instance) {
            return false;
        }

        // Find user enrolment
        $ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid]);
        if ($ue) {
            if ($ue->timecreated != $timecreated) {
                $ue->timecreated = $timecreated;
                $ue->timemodified = time();
                $DB->update_record('user_enrolments', $ue);
                return true;
            }
        }
        return false;
    }

    /**
     * Updates or creates completion record.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $timecompleted
     * @param int $timeenrolled
     * @return string 'created', 'updated', or 'none'
     */
    private function update_completion($userid, $courseid, $timecompleted, $timeenrolled)
    {
        global $DB;

        $cc = $DB->get_record('course_completions', ['userid' => $userid, 'course' => $courseid]);

        if ($cc) {
            // Update if timecompleted is different
            if ($cc->timecompleted != $timecompleted) {
                $cc->timecompleted = $timecompleted;
                // Also update timeenrolled if provided and valid
                if ($timeenrolled > 0) {
                    $cc->timeenrolled = $timeenrolled;
                    $cc->timestarted = $timeenrolled; // Usually same as enrolled
                }
                $DB->update_record('course_completions', $cc);

                // Clear cache
                //$coursecompletioncache = \core_cache\cache::make('core', 'coursecompletion');
                //$coursecompletioncache->delete($userid . '_' . $courseid);

                return 'updated';
            }
        } else {
            // Create new record
            $cc = new \stdClass();
            $cc->course = $courseid;
            $cc->userid = $userid;
            $cc->timecompleted = $timecompleted;
            $cc->timeenrolled = $timeenrolled > 0 ? $timeenrolled : time();
            $cc->timestarted = $cc->timeenrolled;
            $cc->reaggregate = 0;

            $DB->insert_record('course_completions', $cc);

            // Clear cache
            //$coursecompletioncache = \core_cache\cache::make('core', 'coursecompletion');
            //$coursecompletioncache->delete($userid . '_' . $courseid);

            return 'created';
        }

        return 'none';
    }

    /**
     * Updates the Gradebook Course Total if the new grade is higher.
     *
     * @param int $userid
     * @param int $courseid
     * @param float $newgrade
     * @return bool True if updated
     */
    private function update_gradebook_course_total($userid, $courseid, $newgrade)
    {
        global $DB;

        // Get Course Grade Item
        $grade_item = \grade_item::fetch(['courseid' => $courseid, 'itemtype' => 'course']);
        if (!$grade_item) {
            return false;
        }

        // Get existing grade
        $grade_grade = \grade_grade::fetch(['itemid' => $grade_item->id, 'userid' => $userid]);

        $current_val = -1;
        if ($grade_grade && !is_null($grade_grade->finalgrade)) {
            $current_val = (float)$grade_grade->finalgrade;
        }

        // Check if we should update (only if higher)
        if ($newgrade > $current_val) {
            $formatted_date = date('d/m/Y H:i:s');
            $grade_item->update_final_grade($userid, $newgrade, 'import', 'Nota recuperada el ' . $formatted_date, FORMAT_HTML, null, null, [
                'overridden' => true // Override logic as requested
            ]);
            return true;
        }

        return false;
    }
    /**
     * Helper to parse dates from various formats (e.g. d/m/Y)
     * @param string $date_str
     * @return int Timestamp or 0
     */
    private function parse_date($date_str)
    {
        if (empty($date_str)) {
            return 0;
        }

        $date_str = trim($date_str);

        // Explicit Unix timestamp support.
        if (is_numeric($date_str) && (int)$date_str > 0) {
            return (int)$date_str;
        }

        // Try d/m/Y and variations (j/n/Y handles single digits)
        $formats = ['j/n/Y', 'd/m/Y', 'Y-m-d', 'd-m-Y', 'j-n-Y'];
        foreach ($formats as $format) {
            $d = \DateTime::createFromFormat($format, $date_str);
            if ($d) {
                $errors = \DateTime::getLastErrors();
                if ($errors['warning_count'] == 0 && $errors['error_count'] == 0) {
                    return $d->getTimestamp();
                }
            }
        }

        return strtotime($date_str) ?: 0;
    }
}
