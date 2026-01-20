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

use core\exception\moodle_exception, dml_missing_record_exception, dml_multiple_records_exception;
use stdClass;

/**
 * AdHoc
 *
 * @package   tool_eabcetlbridge
 * @category  helpers
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adhoc {

    /** @var stdClass $record */
    protected $record;

    /**
     * Constructs an instance of this class, given an ad hoc task id.
     *
     * @param int $id The id of the ad hoc task to load.
     *
     * @return stdClass The loaded record.
     *
     * @throws dml_missing_record_exception If the record does not exist.
     * @throws dml_multiple_records_exception If multiple records are found.
     */
    public function __construct($id) {
        global $DB;

        $this->record = $DB->get_record('task_adhoc', ['id' => $id]);

        return $this->record;
    }

    /**
     * Deletes the ad hoc task represented by this object.
     *
     * @return void
     */
    public function delete() {
        global $DB;

        $DB->delete_records('task_adhoc', ['id' => $this->record->id]);
    }

    /**
     * Returns the name of the ad hoc task.
     *
     * The name will be of the form "Adhoc: ({$id}) {$component} {$classname} con faildelay {$faildelay}".
     *
     * @return string The name of the ad hoc task.
     */
    public function get_name() {

        $name = "Adhoc: ({$this->record->id}) {$this->record->component} {$this->record->classname}";
        $name .= " con faildelay {$this->record->faildelay}";

        return $name;
    }

}
