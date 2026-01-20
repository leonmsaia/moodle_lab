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
 * Upgrade function for the eabcetlbridge plugin
 *
 * @package     tool_eabcetlbridge
 * @category    tasks
 * @copyright   2025 e-ABC <info@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_tool_eabcetlbridge_upgrade($oldversion) {
    global $DB;

    /** @var \database_manager $dbman */
    $dbman = $DB->get_manager();

    if ($oldversion < 2025100910) {

        $table = new xmldb_table('eabcetlbridge_id_map');
        $index = new xmldb_index('type_key', XMLDB_INDEX_UNIQUE, [
            'source_type', 'source_key'
        ]);

        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Godeep savepoint reached.
        upgrade_plugin_savepoint(true, 2025100910, 'tool', 'eabcetlbridge');
    }

    if ($oldversion < 2025100911) {

        $table = new xmldb_table('eabcetlbridge_id_map');
        $fields = [];
        $fields[] = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'source_key');
        $fields[] = new xmldb_field('customint1', XMLDB_TYPE_INTEGER, '10', null, false);
        $fields[] = new xmldb_field('customint2', XMLDB_TYPE_INTEGER, '10', null, false);
        $fields[] = new xmldb_field('customchar1', XMLDB_TYPE_CHAR, '255', null, false);
        $fields[] = new xmldb_field('customchar2', XMLDB_TYPE_CHAR, '255', null, false);

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_plugin_savepoint(true, 2025100911, 'tool', 'eabcetlbridge');
    }

    if ($oldversion < 2025100912) {

        $table = new xmldb_table('eabcetlbridge_id_map');
        $indexs = [];
        $indexs[] = new xmldb_index('type_key_course', XMLDB_INDEX_UNIQUE, [
            'source_type', 'source_key', 'courseid'
        ]);
        $indexs[] = new xmldb_index('courseid_ix', XMLDB_INDEX_NOTUNIQUE, ['courseid']);
        $indexs[] = new xmldb_index('type_course_ix', XMLDB_INDEX_NOTUNIQUE, [
            'source_type',
            'courseid'
        ]);

        foreach ($indexs as $index) {
            if (!$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        upgrade_plugin_savepoint(true, 2025100912, 'tool', 'eabcetlbridge');
    }

    return true;

}
