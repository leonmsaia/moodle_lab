<?php

function xmldb_local_cron_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2019071500) {

        // Define field gradeuser to be added to mutual_log_local_cron.
        $table = new xmldb_table('mutual_log_local_cron');
        $field = new xmldb_field('gradeuser', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'timemodified');
        
        // Conditionally launch add field gradeuser.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('gradeapprovecourse', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'gradeuser');

        // Conditionally launch add field gradeuser.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Cron savepoint reached.
        upgrade_plugin_savepoint(true, 2019071500, 'local', 'cron');
    }
    return true;
}