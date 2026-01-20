<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_pubsub_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025052800) {
        $table = new xmldb_table('curso_back');

        $field = new xmldb_field('maximotiempo', XMLDB_TYPE_INTEGER, '12', null, null, null, null);

        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_precision($table, $field);
        }

        upgrade_plugin_savepoint(true, 2025052800, 'local', 'pubsub');
    }
    
    if ($oldversion < 2025072500) {

        // Define field modalidad to be added to curso_back.
        $table = new xmldb_table('curso_back');
        $field = new xmldb_field('modalidad', XMLDB_TYPE_CHAR, '30', null, null, null, null, 'nombreproveedorcursodistancia');

        // Conditionally launch add field modalidad.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Pubsub savepoint reached.
        upgrade_plugin_savepoint(true, 2025072500, 'local', 'pubsub');
    }

    return true;
}
