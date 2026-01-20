<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_sendgrade_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2021093002) {

        // Define table local_sendgrade_log to be created.
        $table = new xmldb_table('local_sendgrade_log');

        // Adding fields to table local_sendgrade_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '20, 10', null, null, null, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table local_sendgrade_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_sendgrade_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Sendgrade savepoint reached.
        upgrade_plugin_savepoint(true, 2021093002, 'local', 'sendgrade');
    }


    return true;
}
