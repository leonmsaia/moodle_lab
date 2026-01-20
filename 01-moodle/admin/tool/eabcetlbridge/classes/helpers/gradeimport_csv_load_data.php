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

namespace tool_eabcetlbridge\helpers;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/grade/import/csv/classes/load_data.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');

use gradeimport_csv_load_data as core_grade_loader;
use grade_item;
use grade_grade;
use core\user as core_user;
use core\exception\moodle_exception, dml_missing_record_exception, dml_multiple_records_exception;
use tool_eabcetlbridge\persistents\batch_files;
use stdClass;

/**
 * A class for loading and preparing grade data from import.
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gradeimport_csv_load_data extends core_grade_loader {

    /** @var batch_files $batchfile The batch file object. */
    protected $batchfile;

    /** @var array Contiene los datos de usuarios procesados correctamente. */
    public $processedusers = [];
    /** @var array Contiene los nombres de usuarios procesados correctamente. */
    public $usersinfile = [];
    /** @var array Contiene los username de usuarios que no existen en la plataforma. */
    public $nonexistentusers = [];

    /**
     * Constructor for the gradeimport_csv_load_data class.
     *
     * @param batch_files $batchfile The batch file object to be associated with this object.
     */
    public function __construct($batchfile) {
        $this->batchfile = $batchfile;
        $this->processedusers = [];
        $this->usersinfile = [];
        $this->nonexistentusers = [];
    }

    /**
     * eABC: We override this method omitting error checking.
     *
     * Checks and prepares grade data for inserting into the gradebook.
     *
     * @param array $header Column headers of the CSV file.
     * @param object $formdata Mapping information from the preview page.
     * @param object $csvimport csv import reader object for iterating over the imported CSV file.
     * @param int $courseid The course ID.
     * @param bool $separatemode If we have groups are they separate?
     * @param mixed $currentgroup current group information.
     * @param bool $verbosescales Form setting for grading with scales.
     * @return bool True if the status for importing is okay, false if there are errors.
     */
    public function prepare_import_grade_data(
            $header,
            $formdata,
            $csvimport,
            $courseid,
            $separatemode,
            $currentgroup,
            $verbosescales) {
        global $DB, $USER;

        // The import code is used for inserting data into the grade tables.
        $this->importcode = $formdata->importcode;
        $this->status = true;
        $this->headers = $header;
        $this->studentid = null;
        $this->gradebookerrors = null;
        $forceimport = $formdata->forceimport;
        // Temporary array to keep track of what new headers are processed.
        $this->newgradeitems = array();
        $this->trim_headers();
        $timeexportkey = null;
        $map = array();
        // Loops mapping_0, mapping_1 .. mapping_n and construct $map array.
        foreach ($header as $i => $head) {
            if (isset($formdata->{'mapping_'.$i})) {
                $map[$i] = $formdata->{'mapping_'.$i};
            }
            if ($head == get_string('timeexported', 'gradeexport_txt')) {
                $timeexportkey = $i;
            }
        }

        // If mapping information is supplied.
        $map[clean_param($formdata->mapfrom, PARAM_RAW)] = clean_param($formdata->mapto, PARAM_RAW);

        // Check for mapto collisions.
        $maperrors = array();
        foreach ($map as $i => $j) {
            if (($j == 0) || ($j == 'new')) {
                // You can have multiple ignores or multiple new grade items.
                continue;
            } else {
                if (!isset($maperrors[$j])) {
                    $maperrors[$j] = true;
                } else {
                    // Collision.
                    throw new moodle_exception('cannotmapfield', '', '', $j);
                }
            }
        }

        $this->raise_limits();

        $qtyrecords = 0;
        $qtyrecordsprocessed = 0;

        $csvimport->init();

        while ($line = $csvimport->next()) {
            if (count($line) <= 1) {
                // There is no data on this line, move on.
                continue;
            }
            $qtyrecords++;

            // Array to hold all grades to be inserted.
            $this->newgrades = array();
            // Array to hold all feedback.
            $this->newfeedbacks = array();
            // Each line is a student record.
            foreach ($line as $key => $value) {

                $value = clean_param($value, PARAM_RAW);
                $value = trim($value);

                /*
                 * the options are
                 * 1) userid, useridnumber, usermail, username - used to identify user row
                 * 2) new - new grade item
                 * 3) id - id of the old grade item to map onto
                 * 3) feedback_id - feedback for grade item id
                 */

                // Explode the mapping for feedback into a label 'feedback' and the identifying number.
                $mappingbase = explode("_", $map[$key]);
                $mappingidentifier = $mappingbase[0];
                // Set the feedback identifier if it exists.
                if (isset($mappingbase[1])) {
                    $feedbackgradeid = (int)$mappingbase[1];
                } else {
                    $feedbackgradeid = '';
                }

                $this->map_user_data_with_value(
                        $mappingidentifier,
                        $value,
                        $header,
                        $map,
                        $key,
                        $courseid,
                        $feedbackgradeid,
                        $verbosescales
                );
                if ($this->status === false) {
                    return $this->status;
                }
            }

            // No user mapping supplied at all, or user mapping failed.
            if (empty($this->studentid) || !is_numeric($this->studentid)) {
                // User not found, abort whole import.
                //mtrace("[ERROR] El estudiante no fue mapeado.");
                continue;
                // $this->cleanup_import(get_string('usermappingerrorusernotfound', 'grades'));
                // break;
            }

            if ($separatemode and !groups_is_member($currentgroup, $this->studentid)) {
                // Not allowed to import into this group, abort.
                //mtrace("[ERROR] El estudiante no está en el grupo.");
                continue;
                // $this->cleanup_import(get_string('usermappingerrorcurrentgroup', 'grades'));
                // break;
            }

            // Insert results of this students into buffer.
            $processed = false;
            if ($this->status and !empty($this->newgrades)) {

                foreach ($this->newgrades as $newgrade) {

                    // Check if grade_grade is locked and if so, abort.
                    if (!empty($newgrade->itemid) and $gradegrade = new grade_grade(array('itemid' => $newgrade->itemid,
                            'userid' => $this->studentid), true)) {

                        // If current grade is higher than the new one, skip it.
                        if (isset($gradegrade->finalgrade) && isset($newgrade->finalgrade) && $gradegrade->finalgrade > $newgrade->finalgrade) {
                            mtrace(
                                "[INFO] Calificación actual ({$gradegrade->finalgrade}) es " .
                                "mayor que la nueva ({$newgrade->finalgrade}). " .
                                "Se omite para usuario ID {$this->studentid}.");
                            continue;
                        }

                        if ($gradegrade->is_locked()) {
                            // Individual grade locked.
                            mtrace("[WARNING] La calificación individual está bloqueada " .
                                   "(Omitido). Para usuario ID {$this->studentid}");
                            continue;
                            // $this->cleanup_import(get_string('gradelocked', 'grades'));
                            // return $this->status;
                        }
                        // Check if the force import option is disabled and the last exported date column is present.
                        if (!$forceimport && !empty($timeexportkey)) {
                            $exportedtime = $line[$timeexportkey];
                            if (clean_param($exportedtime, PARAM_INT) != $exportedtime || $exportedtime > time() ||
                                    $exportedtime < strtotime("-1 year", time())) {
                                // The date is invalid, or in the future, or more than a year old.
                                $this->cleanup_import(get_string('invalidgradeexporteddate', 'grades'));
                                return $this->status;

                            }
                            $timemodified = $gradegrade->get_dategraded();
                            if (!empty($timemodified) && ($exportedtime < $timemodified)) {
                                // The item was graded after we exported it, we return here not to override it.
                                mtrace("[WARNING] La calificación ya fue actualizada" .
                                       "(Omitido). Para usuario ID {$this->studentid}");
                                continue;
                                //$user = core_user::get_user($this->studentid);
                                //$this->cleanup_import(get_string('gradealreadyupdated', 'grades', fullname($user)));
                                //return $this->status;
                            }
                        }
                    }
                    if (isset($newgrade->itemid)) {
                        $gradeitem = new grade_item(['id' => $newgrade->itemid]);
                    } else if (isset($newgrade->newgradeitem)) {
                        $gradeitem = new grade_item(['id' => $newgrade->newgradeitem]);
                    }
                    $insertid = isset($gradeitem) ? self::insert_grade_record($newgrade, $this->studentid, $gradeitem) : null;
                    // Check to see if the insert was successful.
                    if (empty($insertid)) {
                        return null;
                    }
                    $processed = true;
                }
            }

            // Updating/inserting all comments here.
            if ($this->status and !empty($this->newfeedbacks)) {
                foreach ($this->newfeedbacks as $newfeedback) {
                    $sql = "SELECT *
                              FROM {grade_import_values}
                             WHERE importcode=? AND userid=? AND itemid=? AND importer=?";
                    if ($feedback = $DB->get_record_sql($sql, array($this->importcode, $this->studentid, $newfeedback->itemid,
                            $USER->id))) {
                        $newfeedback->id = $feedback->id;
                        $DB->update_record('grade_import_values', $newfeedback);

                    } else {
                        // The grade item for this is not updated.
                        $newfeedback->importonlyfeedback = true;
                        $insertid = self::insert_grade_record($newfeedback, $this->studentid, new grade_item(['id' => $newfeedback->itemid]));
                        // Check to see if the insert was successful.
                        if (empty($insertid)) {
                            return null;
                        }
                        $processed = true;
                    }
                }
            }
            if ($processed) {
                $qtyrecordsprocessed++;
                $this->processedusers[] = $this->studentid;
            }
        }
        $this->batchfile->set('qtyrecords', $qtyrecords);
        $this->batchfile->set('qtyrecordsprocessed', $qtyrecordsprocessed);
        $this->batchfile->save();
        return $this->status;
    }

    /**
     * Check that the user is in the system.
     *
     * @param string $value The value, from the csv file, being mapped to identify the user.
     * @param array $userfields Contains the field and label being mapped from.
     * @return int Returns the user ID if it exists, otherwise null.
     */
    protected function check_user_exists($value, $userfields) {
        global $DB;

        $user = null;
        $errorkey = false;
        // The user may use the incorrect field to match the user. This could result in an exception.
        try {
            $field = $userfields['field'];
            // Fields that can be queried in a case-insensitive manner.
            $caseinsensitivefields = [
                'email',
                'username',
            ];
            // Build query predicate.
            if (in_array($field, $caseinsensitivefields)) {
                // Case-insensitive.
                $select = $DB->sql_equal($field, ':' . $field, false);
            } else {
                // Exact-value.
                $select = "{$field} = :{$field}";
            }

            // Validate if the user id value is numerical.
            if ($field === 'id' && !is_numeric($value)) {
                $errorkey = 'usermappingerror';
            }
            // Make sure the record exists and that there's only one matching record found.
            $user = $DB->get_record_select('user', $select, array($userfields['field'] => $value), '*', MUST_EXIST);
        } catch (dml_missing_record_exception $missingex) {
            $errorkey = 'usermappingerror';
        } catch (dml_multiple_records_exception $multiex) {
            $errorkey = 'usermappingerrormultipleusersfound';
        }

        $this->usersinfile[] = $value;

        // Field may be fine, but no records were returned.
        if ($errorkey) {
            $usermappingerrorobj = new stdClass();
            $usermappingerrorobj->field = $userfields['label'];
            $usermappingerrorobj->value = $value;
            //$this->cleanup_import(get_string($errorkey, 'grades', $usermappingerrorobj));
            //mtrace("[WARNING]" . get_string($errorkey, 'grades', $usermappingerrorobj));

            $this->nonexistentusers[] = $value;

            unset($usermappingerrorobj);
            return null;
        }
        return $user->id;
    }


}
