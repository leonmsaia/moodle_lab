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
 * upgrade processes for this module.
 *
 * @package   mod_eabcattendance
 * @copyright 2011 Artem Andreev <andreev.artem@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(__FILE__) . '/upgradelib.php');

/**
 * upgrade this eabcattendance instance - this function could be skipped but it will be needed later
 * @param int $oldversion The old version of the eabcattendance module
 * @return bool
 * @throws ddl_change_structure_exception
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 * @throws coding_exception
 */
function xmldb_eabcattendance_upgrade($oldversion = 0)
{

    global $DB;
    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    $result = true;

    if ($oldversion < 2014112000) {
        $table = new xmldb_table('eabcattendance_sessions');

        $field = new xmldb_field('studentscanmark');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2014112000, 'eabcattendance');
    }

    if ($oldversion < 2014112001) {
        // Replace values that reference old module "attforblock" to "eabcattendance".
        $sql = "UPDATE {grade_items}
                   SET itemmodule = 'eabcattendance'
                 WHERE itemmodule = 'attforblock'";

        $DB->execute($sql);

        $sql = "UPDATE {grade_items_history}
                   SET itemmodule = 'eabcattendance'
                 WHERE itemmodule = 'attforblock'";

        $DB->execute($sql);

        /*
         * The user's custom capabilities need to be preserved due to the module renaming.
         * Capabilities with a modifierid = 0 value are installed by default.
         * Only update the user's custom capabilities where modifierid is not zero.
         */
        $sql = $DB->sql_like('capability', '?') . ' AND modifierid <> 0';
        $rs = $DB->get_recordset_select('role_capabilities', $sql, array('%mod/attforblock%'));
        foreach ($rs as $cap) {
            $renamedcapability = str_replace('mod/attforblock', 'mod/eabcattendance', $cap->capability);
            $exists = $DB->record_exists('role_capabilities', array('roleid' => $cap->roleid, 'capability' => $renamedcapability));
            if (!$exists) {
                $DB->update_record('role_capabilities', array('id' => $cap->id, 'capability' => $renamedcapability));
            }
        }

        // Delete old role capabilities.
        $sql = $DB->sql_like('capability', '?');
        $DB->delete_records_select('role_capabilities', $sql, array('%mod/attforblock%'));

        // Delete old capabilities.
        $DB->delete_records_select('capabilities', 'component = ?', array('mod_attforblock'));

        upgrade_mod_savepoint(true, 2014112001, 'eabcattendance');
    }

    if ($oldversion < 2015040501) {
        // Define table eabcattendance_tempusers to be created.
        $table = new xmldb_table('eabcattendance_tempusers');

        // Adding fields to table eabcattendance_tempusers.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('studentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('fullname', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('created', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table eabcattendance_tempusers.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for eabcattendance_tempusers.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Conditionally launch add index courseid.
        $index = new xmldb_index('courseid', XMLDB_INDEX_NOTUNIQUE, array('courseid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Conditionally launch add index studentid.
        $index = new xmldb_index('studentid', XMLDB_INDEX_UNIQUE, array('studentid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2015040501, 'eabcattendance');
    }

    if ($oldversion < 2015040502) {

        // Define field setnumber to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_statuses');
        $field = new xmldb_field('setnumber', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'deleted');

        // Conditionally launch add field setnumber.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field statusset to be added to eabcattendance_sessions.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('statusset', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'descriptionformat');

        // Conditionally launch add field statusset.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2015040502, 'eabcattendance');
    }

    if ($oldversion < 2015040503) {

        // Changing type of field grade on table eabcattendance_statuses to number.
        $table = new xmldb_table('eabcattendance_statuses');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '5, 2', null, XMLDB_NOTNULL, null, '0', 'description');

        // Launch change of type for field grade.
        $dbman->change_field_type($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2015040503, 'eabcattendance');
    }

    if ($oldversion < 2016052202) {
        // Adding field to store calendar event ids.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('caleventid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', null);

        // Conditionally launch add field statusset.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Creating events for all existing sessions.
        eabcattendance_upgrade_create_calendar_events();

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2016052202, 'eabcattendance');
    }

    if ($oldversion < 2016082900) {

        // Define field timemodified to be added to eabcattendance.
        $table = new xmldb_table('eabcattendance');
        $field = new xmldb_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'grade');

        // Conditionally launch add field timemodified.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2016082900, 'eabcattendance');
    }
    if ($oldversion < 2016112100) {
        $table = new xmldb_table('eabcattendance');
        $newfield = $table->add_field('subnet', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timemodified');
        if (!$dbman->field_exists($table, $newfield)) {
            $dbman->add_field($table, $newfield);
        }
        upgrade_mod_savepoint(true, 2016112100, 'eabcattendance');
    }

    if ($oldversion < 2016121300) {
        $table = new xmldb_table('eabcattendance');
        $field = new xmldb_field('sessiondetailspos', XMLDB_TYPE_CHAR, '5', null, null, null, 'left', 'subnet');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('showsessiondetails', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'subnet');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2016121300, 'eabcattendance');
    }

    if ($oldversion < 2017020700) {
        // Define field timemodified to be added to eabcattendance.
        $table = new xmldb_table('eabcattendance');

        $fields = [];
        $fields[] = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'timemodified');
        $fields[] = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, 0, 'intro');

        // Conditionally launch add field.
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017020700, 'eabcattendance');
    }

    if ($oldversion < 2017042800) {
        $table = new xmldb_table('eabcattendance_sessions');

        $field = new xmldb_field('studentpassword');
        $field->set_attributes(XMLDB_TYPE_CHAR, '50', null, false, null, '', 'studentscanmark');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017042800, 'eabcattendance');
    }

    if ($oldversion < 2017051101) {

        // Define field studentavailability to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_statuses');
        $field = new xmldb_field('studentavailability', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'grade');

        // Conditionally launch add field studentavailability.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017051101, 'eabcattendance');
    }

    if ($oldversion < 2017051103) {
        $table = new xmldb_table('eabcattendance_sessions');
        $newfield = $table->add_field('subnet', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'studentpassword');
        if (!$dbman->field_exists($table, $newfield)) {
            $dbman->add_field($table, $newfield);
        }
        upgrade_mod_savepoint(true, 2017051103, 'eabcattendance');
    }

    if ($oldversion < 2017051104) {
        // The meaning of the subnet in the eabcattendance table has changed - it is now the "default" value - find all existing
        // Eabcattendance with subnet set and set the session subnet for these.
        $eabcattendances = $DB->get_recordset_select('eabcattendance', 'subnet IS NOT NULL');
        foreach ($eabcattendances as $eabcattendance) {
            if (!empty($eabcattendance->subnet)) {
                // Get all sessions for this eabcattendance.
                $sessions = $DB->get_recordset('eabcattendance_sessions', array('eabcattendanceid' => $eabcattendance->id));
                foreach ($sessions as $session) {
                    $session->subnet = $eabcattendance->subnet;
                    $DB->update_record('eabcattendance_sessions', $session);
                }
                $sessions->close();
            }
        }
        $eabcattendances->close();

        upgrade_mod_savepoint(true, 2017051104, 'eabcattendance');
    }

    if ($oldversion < 2017051900) {
        // Define field setunmarked to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_statuses');
        $field = new xmldb_field('setunmarked', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'studentavailability');

        // Conditionally launch add field studentavailability.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017051900, 'eabcattendance');
    }

    if ($oldversion < 2017052201) {
        // Define field setunmarked to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('automark', XMLDB_TYPE_INTEGER, '1', null, true, null, '0', 'subnet');

        // Conditionally launch add field automark.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('automarkcompleted', XMLDB_TYPE_INTEGER, '1', null, true, null, '0', 'automark');

        // Conditionally launch add field automarkcompleted.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017052201, 'eabcattendance');
    }

    if ($oldversion < 2017060900) {
        // Automark values changed.
        $default = get_config('eabcattendance', 'automark_default');
        if (!empty($default)) { // Change default if set.
            set_config('automark_default', 2, 'eabcattendance');
        }
        // Update any sessions set to use automark = 1.
        $sql = "UPDATE {eabcattendance_sessions} SET automark = 2 WHERE automark = 1";
        $DB->execute($sql);

        // Update automarkcompleted to 2 if already complete.
        $sql = "UPDATE {eabcattendance_sessions} SET automarkcompleted = 2 WHERE automarkcompleted = 1";
        $DB->execute($sql);

        upgrade_mod_savepoint(true, 2017060900, 'eabcattendance');
    }

    if ($oldversion < 2017062000) {

        // Define table eabcattendance_warning_done to be created.
        $table = new xmldb_table('eabcattendance_warning_done');

        // Adding fields to table eabcattendance_warning_done.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('notifyid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timesent', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table eabcattendance_warning_done.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table eabcattendance_warning_done.
        $table->add_index('notifyid_userid', XMLDB_INDEX_UNIQUE, array('notifyid', 'userid'));

        // Conditionally launch create table for eabcattendance_warning_done.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017062000, 'eabcattendance');
    }

    if ($oldversion < 2017071305) {

        // Define table eabcattendance_warning to be created.
        $table = new xmldb_table('eabcattendance_warning');

        if (!$dbman->table_exists($table)) {
            // Adding fields to table eabcattendance_warning.
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('idnumber', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('warningpercent', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('warnafter', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('emailuser', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('emailsubject', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('emailcontent', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('emailcontentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
            $table->add_field('thirdpartyemails', XMLDB_TYPE_TEXT, null, null, null, null, null);

            // Adding keys to table eabcattendance_warning.
            $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
            $table->add_key('level_id', XMLDB_KEY_UNIQUE, array('idnumber', 'warningpercent', 'warnafter'));

            // Conditionally launch create table for eabcattendance_warning.
            $dbman->create_table($table);

        } else {
            // Key definition is probably incorrect so fix it - drop_key dml function doesn't seem to work.
            $indexes = $DB->get_indexes('eabcattendance_warning');
            foreach ($indexes as $name => $index) {
                if ($DB->get_dbfamily() === 'mysql') {
                    $DB->execute("ALTER TABLE {eabcattendance_warning} DROP INDEX " . $name);
                } else {
                    $DB->execute("DROP INDEX " . $name);
                }
            }
            $index = new xmldb_key('level_id', XMLDB_KEY_UNIQUE, array('idnumber', 'warningpercent', 'warnafter'));
            $dbman->add_key($table, $index);
        }
        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017071305, 'eabcattendance');
    }

    if ($oldversion < 2017071800) {
        // Define field setunmarked to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_warning');
        $field = new xmldb_field('maxwarn', XMLDB_TYPE_INTEGER, '10', null, true, null, '1', 'warnafter');

        // Conditionally launch add field automark.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017071800, 'eabcattendance');
    }

    if ($oldversion < 2017071802) {
        // Define field setunmarked to be added to eabcattendance_statuses.
        $table = new xmldb_table('eabcattendance_warning_done');

        $index = new xmldb_index('notifyid_userid', XMLDB_INDEX_UNIQUE, array('notifyid', 'userid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        $index = new xmldb_index('notifyid', XMLDB_INDEX_NOTUNIQUE, array('notifyid', 'userid'));
        $dbman->add_index($table, $index);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017071802, 'eabcattendance');
    }

    if ($oldversion < 2017082200) {
        // Warnings idnumber field should use eabcattendanceid instead of cmid.
        $sql = "SELECT cm.id, cm.instance
                  FROM {course_modules} cm
                  JOIN {modules} md ON md.id = cm.module AND md.name = 'eabcattendance'";
        $idnumbers = $DB->get_records_sql_menu($sql);
        $warnings = $DB->get_recordset('eabcattendance_warning');
        foreach ($warnings as $warning) {
            if (!empty($warning->idnumber) && !empty($idnumbers[$warning->idnumber])) {
                $warning->idnumber = $idnumbers[$warning->idnumber];
                $DB->update_record("eabcattendance_warning", $warning);
            }
        }
        $warnings->close();

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2017082200, 'eabcattendance');
    }

    if ($oldversion < 2017120700) {
        $table = new xmldb_table('eabcattendance_sessions');

        $field = new xmldb_field('absenteereport');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '1', 'statusset');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017120700, 'eabcattendance');
    }

    if ($oldversion < 2017120801) {
        $table = new xmldb_table('eabcattendance_sessions');

        $field = new xmldb_field('autoassignstatus');
        $field->set_attributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'studentscanmark');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2017120801, 'eabcattendance');
    }

    if ($oldversion < 2018022204) {
        $table = new xmldb_table('eabcattendance');
        $field = new xmldb_field('showextrauserdetails', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '1', 'showsessiondetails');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018022204, 'eabcattendance');
    }

    if ($oldversion < 2018050100) {
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('preventsharedip', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'absenteereport');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('preventsharediptime', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
            null, null, null, 'preventsharedip');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('eabcattendance_log');
        $field = new xmldb_field('ipaddress', XMLDB_TYPE_CHAR, '45', null,
            null, null, '', 'remarks');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2018050100, 'eabcattendance');
    }

    if ($oldversion < 2018051402) {
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('calendarevent', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '1', 'caleventid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            if (empty(get_config('eabcattendance', 'enablecalendar'))) {
                // Calendar disabled on this site, set calendarevent for existing records to 0.
                $DB->execute("UPDATE {eabcattendance_sessions} set calendarevent = 0");
            }
        }
        upgrade_mod_savepoint(true, 2018051402, 'eabcattendance');
    }

    if ($oldversion < 2018051404) {
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('includeqrcode', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'calendarevent');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2018051404, 'eabcattendance');
    }

    if ($oldversion < 2018051408) {

        // Changing precision of field statusset on table eabcattendance_log to (1333).
        $table = new xmldb_table('eabcattendance_log');
        $field = new xmldb_field('statusset', XMLDB_TYPE_CHAR, '1333', null, null, null, null, 'statusid');

        // Launch change of precision for field statusset.
        $dbman->change_field_precision($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2018051408, 'eabcattendance');
    }

    if ($oldversion < 2019122502) {
        global $DB;

        $result = true;

        $DB->delete_records('eabcattendance_statuses', array('eabcattendanceid' => 0));
        $arr = array('L1' => 0, 'L2' => 0.5, 'L3' => 1, 'L4' => 1.5, 'L5' => 2);
        foreach ($arr as $k => $v) {
            $rec = new stdClass;
            $rec->eabcattendanceid = 0;
            $rec->acronym = get_string($k . 'acronym', 'eabcattendance');
            $rec->description = get_string($k . 'full', 'eabcattendance');
            $rec->grade = $v;
            $rec->visible = 1;
            $rec->deleted = 0;
            if (!$DB->record_exists('eabcattendance_statuses', array('eabcattendanceid' => 0, 'acronym' => $rec->acronym))) {
                $result = $result && $DB->insert_record('eabcattendance_statuses', $rec);
            }
        }

        upgrade_mod_savepoint(true, 2019122502, 'eabcattendance');
    }

    if ($oldversion < 2019122503) {

        // Define field direction to be added to eabcattendance_sessions.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('direction', XMLDB_TYPE_TEXT, null, null, null, null, null, 'includeqrcode');

        // Conditionally launch add field direction.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field directionformat to be added to eabcattendance_sessions.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('directionformat', XMLDB_TYPE_INTEGER, '2', null, null, null, null, 'direction');

        // Conditionally launch add field directionformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2019122503, 'eabcattendance');
    }

    if ($oldversion < 2020011300) {

        // Define field guid to be added to eabcattendance_sessions.
        $table = new xmldb_table('eabcattendance_sessions');
        $field = new xmldb_field('guid', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'directionformat');

        // Conditionally launch add field guid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2020011501) {

        // Define table eabcattendance_course_groups to be created.
        $table = new xmldb_table('eabcattendance_course_groups');

        // Adding fields to table eabcattendance_course_groups.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('grupo', XMLDB_TYPE_INTEGER, '20', null, null, null, '0');
        $table->add_field('curso', XMLDB_TYPE_INTEGER, '20', null, null, null, '0');
        $table->add_field('uuid', XMLDB_TYPE_INTEGER, '20', null, null, null, '0');

        // Adding keys to table eabcattendance_course_groups.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for eabcattendance_course_groups.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020011501, 'eabcattendance');
    }

    if ($oldversion < 2020011600) {

        // Define table eabcattendance_course_gu to be created.
        $table = new xmldb_table('eabcattendance_course_gu');

        // Adding fields to table eabcattendance_course_gu.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('guid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table eabcattendance_course_gu.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for eabcattendance_course_gu.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020011600, 'eabcattendance');
    }
    
    
        if ($oldversion < 2020020602) {

        // Changing type of field guid on table eabcattendance_course_gu to char.
        $table = new xmldb_table('eabcattendance_course_gu');
        $field = new xmldb_field('guid', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'courseid');

        // Launch change of type for field guid.
        $dbman->change_field_type($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020020602, 'eabcattendance');
    }


    if ($oldversion < 2020021805) {

        // Define table enrolment_idinterno to be created.
        $table = new xmldb_table('eabcattendance_enrol_idin');

        // Adding fields to table enrolment_idinterno.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('id_usr_enrolment', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('id_interno', XMLDB_TYPE_CHAR, '50', null, null, null, null);

        // Adding keys to table enrolment_idinterno.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for enrolment_idinterno.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020021805, 'eabcattendance');
    }

    if ($oldversion < 2020031700) {

        // Changing type of field uuid on table eabcattendance_course_groups to char.
        $table = new xmldb_table('eabcattendance_course_groups');
        $field = new xmldb_field('uuid', XMLDB_TYPE_CHAR, '255', null, null, null, '0', 'curso');

        // Launch change of type for field uuid.
        $dbman->change_field_type($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020031700, 'eabcattendance');

    }

    if ($oldversion < 2020030600) {

        // Define table eabcattendance_extrafields to be created.
        $table = new xmldb_table('eabcattendance_extrafields');

        // Adding fields to table eabcattendance_extrafields.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('participantesexo', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('pais', XMLDB_TYPE_INTEGER, '1', null, null, null, '1');
        $table->add_field('participantefechanacimiento', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rol', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('apellidomaterno', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('nroadherente', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table eabcattendance_extrafields.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for eabcattendance_extrafields.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020030600, 'eabcattendance');
    }

    if ($oldversion < 2020030800) {

        // Define field empresarut to be added to eabcattendance_extrafields.
        $table = new xmldb_table('eabcattendance_extrafields');
        $field = new xmldb_field('empresarut', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'nroadherente');

        // Conditionally launch add field empresarut.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020030800, 'eabcattendance');
    }

    if ($oldversion < 20200526701) {

        // Define table eabcattendance_flags to be created.
        $table = new xmldb_table('eabcattendance_flags');

        // Adding fields to table eabcattendance_flags.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('send_email', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('call_phone', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('facilitadorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table eabcattendance_flags.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for eabcattendance_flags.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 20200526701, 'eabcattendance');
    }

    if ($oldversion < 20200900002) {

        // Changing type of field participantefechanacimiento on table eabcattendance_extrafields to char.
        $table = new xmldb_table('eabcattendance_extrafields');
        $field = new xmldb_field('participantefechanacimiento', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'pais');

        // Launch change of type for field participantefechanacimiento.
        $dbman->change_field_type($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 20200900002, 'eabcattendance');
    }

    if ($oldversion < 20200900003) {

        // Define table focalizacion to be created.
        $table = new xmldb_table('focalizacion');

        // Adding fields to table focalizacion.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('instructorid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('email', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('emaildate', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('call', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('calldate', XMLDB_TYPE_CHAR, '20', null, null, null, null);

        // Adding keys to table focalizacion.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for focalizacion.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 20200900003, 'eabcattendance');
    }

    if ($oldversion < 20200914001) {

         // Define field callemp to be dropped from focalizacion.
         $table = new xmldb_table('focalizacion');
         $field = new xmldb_field('call');
 
         // Conditionally launch drop field callemp.
         if ($dbman->field_exists($table, $field)) {
             $dbman->drop_field($table, $field);
         }
 
        $field = new xmldb_field('callemp', XMLDB_TYPE_INTEGER, '1', null, null, null, null, 'emaildate');

        // Conditionally launch add field callemp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 20200914001, 'eabcattendance');
    }


    if ($oldversion < 2020111300) {

        // Changing type of field nroadherente on table eabcattendance_extrafields to char.
        $table = new xmldb_table('eabcattendance_extrafields');
        $field = new xmldb_field('nroadherente', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'apellidomaterno');

        // Launch change of type for field nroadherente.
        $dbman->change_field_type($table, $field);

        // Eabcattendance savepoint reached.
        upgrade_mod_savepoint(true, 2020111300, 'eabcattendance');
    }

    if ($oldversion < 20241209002) {

        $table = new xmldb_table('eabcattendance_carga_masiva');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('guid_sesion', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('guid_evento', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_curso_moodle', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_grupo_moodle', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('id_sesion_moodle', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('tipo_documento', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('numero_documento', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('nombres', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('apellido_paterno', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('apellido_materno', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('correo', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ciudad', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('nacionalidad', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('genero', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('fecha_nac', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'dd/mm/yyyy');
        $table->add_field('rol', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('num_adherente', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('rut_adherente', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('calificacion', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('asistencia', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enviado', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fecha_envio', XMLDB_TYPE_DATETIME, null, null, null, null, null);
        $table->add_field('recibido', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('fecha_recibido', XMLDB_TYPE_DATETIME, null, null, null, null, null);
        $table->add_field('id_inscripcion_dynamics', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('es_update', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('count_update', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('identificador_proceso', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('user_statuses_att_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resultado', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('mensaje', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 20241209002, 'eabcattendance');
    }   

    if ($oldversion < 20250211000) {
        $table = new xmldb_table('eabcattendance_carga_masiva');
        $field = new xmldb_field('user_id_upload', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'mensaje');

        // Agregar el campo si no existe.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 20250211000, 'eabcattendance');
    }

    return $result;
}
