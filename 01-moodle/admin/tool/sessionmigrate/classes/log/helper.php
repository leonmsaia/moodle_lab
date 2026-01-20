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
 * Log helper for the session migration tool.
 *
 * @package     tool_sessionmigrate
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_sessionmigrate\log;

defined('MOODLE_INTERNAL') || die();

class helper {
    /**
     * Returns the query parts for the log table.
     *
     * @return array
     */
    public static function get_query() {
        global $DB;

        $fields = [
            'l.id',
            'l.action',
            'l.targettype',
            'l.targetidentifier',
            'l.status',
            'l.message',
            'l.timecreated',
            'l.timemodified',
            'l.userid',
            $DB->sql_fullname('u.firstname', 'u.lastname') . ' AS fullname'
        ];

        $from = '{tool_sessionmigrate_log} l JOIN {user} u ON l.userid = u.id';

        $fields = implode(', ', $fields);


        $where = '1=1';
        $params = [];

        return [
            $fields,
            $from,
            $where,
            $params
        ];
    }
}
