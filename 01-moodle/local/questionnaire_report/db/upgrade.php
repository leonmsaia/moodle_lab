<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_questionnaire_report_upgrade($oldversion)
{
    // @codingStandardsIgnoreLine
    /** @var \moodle_database $DB */
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020072702) {

        // Define table questionnaire_report to be created.
        $table = new xmldb_table('questionnaire_report');

        // Adding fields to table questionnaire_report.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('course_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('course_fullname', XMLDB_TYPE_CHAR, '700', null, null, null, null);
        $table->add_field('course_shortname', XMLDB_TYPE_CHAR, '300', null, null, null, null);
        $table->add_field('pregunta', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('pregunta_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('respuesta', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('respuesta_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('user', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('username', XMLDB_TYPE_CHAR, '200', null, null, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('tipo_respuesta', XMLDB_TYPE_INTEGER, '10', null, null, null, null);

        // Adding keys to table questionnaire_report.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for questionnaire_report.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Questionnaire_report savepoint reached.
        upgrade_plugin_savepoint(true, 2020072702, 'local', 'questionnaire_report');
    }

    if ($oldversion < 2020072703) {

        // Define field enviado to be added to questionnaire_report.
        $table = new xmldb_table('questionnaire_report');
        $field = new xmldb_field('enviado', XMLDB_TYPE_CHAR, '200', null, null, null, null, 'tipo_respuesta');
        $field2 = new xmldb_field('participantecargo', XMLDB_TYPE_CHAR, '300', null, null, null, null, 'enviado');
        $field3 = new xmldb_field('participantefechanacimiento', XMLDB_TYPE_CHAR, '300', null, null, null, null, 'participantecargo');

        // Conditionally launch add field enviado.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_field($table, $field2);
            $dbman->add_field($table, $field3);
        }
        // Questionnaire_report savepoint reached.
        upgrade_plugin_savepoint(true, 2020072703, 'local', 'questionnaire_report');
    }

    if ($oldversion < 2020072704) {

        // Define field questionnaire_id to be added to questionnaire_report.
        $table = new xmldb_table('questionnaire_report');
        $field = new xmldb_field('questionnaire_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'course_shortname');
        $field2 = new xmldb_field('questionnaire_name', XMLDB_TYPE_CHAR, '300', null, null, null, null, 'questionnaire_id');

        // Conditionally launch add field questionnaire_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_field($table, $field2);
        }

        // Questionnaire_report savepoint reached.
        upgrade_plugin_savepoint(true, 2020072704, 'local', 'questionnaire_report');
    }

    if ($oldversion < 2020081804) {

        // Define field empresarut to be added to questionnaire_report.
        $table  = new xmldb_table('questionnaire_report');
        $field  = new xmldb_field('empresarut', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'questionnaire_name');
        $field2 = new xmldb_field('empresarazonsocial', XMLDB_TYPE_TEXT, null, null, null, null, null, 'empresarut');
        $field3 = new xmldb_field('empresacontrato', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'empresarazonsocial');
        $field4 = new xmldb_field('participantesexo', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'empresacontrato');

        // Conditionally launch add field empresarut.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_field($table, $field2);
            $dbman->add_field($table, $field3);
            $dbman->add_field($table, $field4);

        }

        // Questionnaire_report savepoint reached.
        upgrade_plugin_savepoint(true, 2020081804, 'local', 'questionnaire_report');
    }




    return true;
}