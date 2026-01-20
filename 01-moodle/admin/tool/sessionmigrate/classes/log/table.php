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
 * Log table for the session migration tool.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sessionmigrate\log;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/helper.php');

class table extends \table_sql {

    /**
     * Constructor
     * @param string $uniqueid
     */
    public function __construct($uniqueid) {
        parent::__construct($uniqueid);

        $this->define_baseurl(new \moodle_url('/admin/tool/sessionmigrate/pages/log.php'));
        $this->attributes['class'] = 'generaltable';

        $columns = [
            'id',
            'action',
            'targettype',
            'targetidentifier',
            'status',
            'message',
            'viewdetails',
            'fullname',
            'timecreated',
            'timemodified',
        ];
        $this->define_columns($columns);

        $headers = [
            get_string('id', 'tool_sessionmigrate'),
            get_string('action', 'tool_sessionmigrate'),
            get_string('targettype', 'tool_sessionmigrate'),
            get_string('targetidentifier', 'tool_sessionmigrate'),
            get_string('status', 'tool_sessionmigrate'),
            get_string('message', 'tool_sessionmigrate'),
            get_string('viewdetails', 'tool_sessionmigrate'),
            get_string('triggeredby', 'tool_sessionmigrate'),
            get_string('timecreated', 'tool_sessionmigrate'),
            get_string('timemodified', 'tool_sessionmigrate'),
        ];
        $this->define_headers($headers);

        $this->sortable(true, 'timecreated', SORT_DESC);

        [$fields, $from, $where, $params] = \tool_sessionmigrate\log\helper::get_query();
        $this->set_sql($fields, $from, $where, $params);
    }

    /**
     * Define the fullname column.
     *
     * @param object $row
     * @return string
     */
    public function col_fullname($row) {
        return format_string($row->fullname);
    }

    /**
     * Define the timecreated column.
     *
     * @param object $log
     * @return string
     */
    public function col_timecreated($row) {
        return userdate($row->timecreated);
    }

    /**
     * Define the details column.
     *
     * @param object $row
     * @return string
     */
    public function col_viewdetails($row) {
        $url = new \moodle_url('/admin/tool/sessionmigrate/pages/logview.php', ['id' => $row->id]);
        return \html_writer::link($url, get_string('viewdetails', 'tool_sessionmigrate'), ['target' => '_blank']);
    }

    /**
     * Define the timemodified column.
     *
     * @param object $log
     * @return string
     */
    public function col_timemodified($row) {
        return userdate($row->timemodified);
    }
}
