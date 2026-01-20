<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_download_cert_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();
    if ($oldversion < 2020090100) {

        // Define table download_cert_code to be created.
        $table = new xmldb_table('download_cert_code');

        // Adding fields to table download_cert_code.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('code_certificate', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table download_cert_code.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for download_cert_code.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Download_cert savepoint reached.
        upgrade_plugin_savepoint(true, 2020090100, 'local', 'download_cert');
    }

    return true;
}
