<?php

defined('MOODLE_INTERNAL') || die();

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_feedback_facilitador_upgrade($oldversion)
{
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2020060504) {

        // Define table panel_feedback_facilitadores to be created.
        $table = new xmldb_table('panel_feedback_facilitadores');

        // Adding fields to table panel_feedback_facilitadores.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('nombre', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('cumplimiento_plazo', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('cumplimiento_cierre', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('encuesta_facilitador', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('cumplimiento_horario', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('cantidad_cursos', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('recomienda_curso', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('envio_correo', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('llamo_empresa', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('encuesta_curso', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);
        $table->add_field('cantidad_cursos_suspendidos', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('facilitador_id', XMLDB_TYPE_INTEGER, '3', null, null, null, null);
        $table->add_field('total_cumplimiento', XMLDB_TYPE_NUMBER, '7, 2', null, null, null, null);


        // Adding keys to table panel_feedback_facilitadores.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for panel_feedback_facilitadores.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Feedback_facilitador savepoint reached.
        upgrade_plugin_savepoint(true, 2020060504, 'local', 'feedback_facilitador');
    }


    return true;
}