<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_local_migrategrades_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025122601) {
        $table = new xmldb_table('local_migrategrades_company_log');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);

        $table->add_field('actorid', XMLDB_TYPE_INTEGER, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        $table->add_field('empresarut', XMLDB_TYPE_CHAR, '20', null, null, null, null);
        $table->add_field('companyid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'error');
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);

        $table->add_field('soap_error', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('soap_mensaje', XMLDB_TYPE_TEXT, null, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('idx_actor_time', XMLDB_INDEX_NOTUNIQUE, array('actorid', 'timecreated'));
        $table->add_index('idx_username', XMLDB_INDEX_NOTUNIQUE, array('username'));
        $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, array('status'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2025122601, 'local', 'migrategrades');
    }

    return true;
}
