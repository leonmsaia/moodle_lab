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
 * The enrol_presumar upgrade file.
 *
 * @package    local_eabcprogramas
 * @copyright  2019 Eimar Urbina <eimar@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * @param $oldversion
 * @throws ddl_exception
 * @throws dml_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_local_eabcprogramas_upgrade($oldversion)
{
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    // Creacion tabla local_diplomas

    if ($oldversion < 2020052007) {

        // Define table local_diplomas to be created.
        $table = new xmldb_table('local_diplomas');

        // Adding fields to table local_diplomas.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('img', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('fromdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('todate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table local_diplomas.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_diplomas.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcprogramas savepoint reached.
        //upgrade_plugin_savepoint(true, 2020052007, 'local', 'eabcprogramas');
    }

    if ($oldversion < 2020052008) {

        // Changing type of field name on table local_eabcprogramas to char.
        $table = new xmldb_table('local_eabcprogramas');

        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'id');

        // Launch change of type for field name.
        $dbman->change_field_type($table, $field);

        // Define field codigo to be added to local_eabcprogramas.
        $field = new xmldb_field('codigo', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'status');

        // Conditionally launch add field codigo.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null, 'codigo');

        // Conditionally launch add field description.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('horas', XMLDB_TYPE_CHAR, '2', null, null, null, null, 'description');

        // Conditionally launch add field horas.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('responsable', XMLDB_TYPE_CHAR, '200', null, null, null, null, 'horas');

        // Conditionally launch add field responsable.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Eabcprogramas savepoint reached.
        //upgrade_plugin_savepoint(true, 2020052008, 'local', 'eabcprogramas');
    }

    if ($oldversion < 2020052009) {

        // Define field fromdate to be dropped from local_eabcprogramas.
        $table = new xmldb_table('local_eabcprogramas');
        $field = new xmldb_field('diploma_img');

        // Conditionally launch drop field .
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('diploma_text');

        // Conditionally launch drop field .
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('certificado_img');

        // Conditionally launch drop field .
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        $field = new xmldb_field('certificado_text');

        // Conditionally launch drop field .
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        // Eabcprogramas savepoint reached.
        // upgrade_plugin_savepoint(true, 2020052009, 'local', 'eabcprogramas');
    }
    if ($oldversion < 2020052010) {

        // Changing type of field cursos on table local_eabcprogramas_usuarios to text.
        $table = new xmldb_table('local_eabcprogramas_usuarios');
        $field = new xmldb_field('cursos', XMLDB_TYPE_TEXT, null, null, null, null, null, 'status');

        // Launch change of type for field cursos.
        $dbman->change_field_type($table, $field);

        $field = new xmldb_field('codigo_programa', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'timemodified');

        // Conditionally launch add field codigo_programa.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('description', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'codigo_programa');

        // Conditionally launch add field description.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('usuario', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'description');

        // Conditionally launch add field usuario.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('rut_usuario', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'usuario');

        // Conditionally launch add field rut_usuario.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('empresa', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'rut_usuario');

        // Conditionally launch add field empresa.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('rut_empresa', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'empresa');

        // Conditionally launch add field rut_empresa.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('end_firstcourse', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'rut_empresa');

        // Conditionally launch add field end_firstcourse.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('fecha_otorgamiento', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'end_firstcourse');

        // Conditionally launch add field fecha_otorgamiento.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('fecha_vencimiento', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'fecha_otorgamiento');

        // Conditionally launch add field fecha_otorgamiento.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('diplomaid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'fecha_vencimiento');

        // Conditionally launch add field diplomaid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('certificadoid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'diplomaid');

        // Conditionally launch add field certificadoid.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        //////////////////////////////////////
        ///local_eabcprogramas
        ////////////77
        $table = new xmldb_table('local_eabcprogramas');
        $field = new xmldb_field('description');

        // Conditionally launch drop field name.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Eabcprogramas savepoint reached.
        // upgrade_plugin_savepoint(true, 2020052010, 'local', 'eabcprogramas');
    }
    if ($oldversion < 2020052011) {

        // Rename field description on table local_eabcprogramas to NEWNAMEGOESHERE.
        $table = new xmldb_table('local_eabcprogramas');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'id');

        // Launch rename field description.
        $dbman->rename_field($table, $field, 'description');

        // Eabcprogramas savepoint reached.
    }
    if ($oldversion < 2020052012) {

        // Define field codigo to be added to local_eabcprogramas_usuarios.
        $table = new xmldb_table('local_eabcprogramas_usuarios');
        $field = new xmldb_field('codigo', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'certificadoid');

        // Conditionally launch add field codigo.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('codigo_diploma', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'codigo');

        // Conditionally launch add field codigo_diploma.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('codigo_certificado', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'codigo_diploma');

        // Conditionally launch add field codigo_certificado.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    if ($oldversion < 2020052014) {

        // Define table local_certificados to be created.
        $table = new xmldb_table('local_certificados');

        // Adding fields to table local_certificados.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('img', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('fromdate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('todate', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '1', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '20', null, null, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '20', null, null, null, null);

        // Adding keys to table local_certificados.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Conditionally launch create table for local_certificados.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Eabcprogramas savepoint reached.
        // upgrade_plugin_savepoint(true, 2020052014, 'local', 'eabcprogramas');
    }
    if ($oldversion < 2020052015) {

        // Define field horas to be added to local_eabcprogramas_usuarios.
        $table = new xmldb_table('local_eabcprogramas_usuarios');
        $field = new xmldb_field('horas', XMLDB_TYPE_CHAR, '3', null, null, null, null, 'codigo_certificado');

        // Conditionally launch add field horas.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('caducidad', XMLDB_TYPE_CHAR, '3', null, null, null, null, 'horas');

        // Conditionally launch add field caducidad.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('adherente', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'caducidad');

        // Conditionally launch add field adherente.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Eabcprogramas savepoint reached.
        // upgrade_plugin_savepoint(true, 2020052015, 'local', 'eabcprogramas');
    }

    if ($oldversion < 2020052016) {

        // Define field urltemp to be added to local_diplomas.
        $table = new xmldb_table('local_diplomas');
        $field = new xmldb_field('urltemp', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'timemodified');

        // Conditionally launch add field urltemp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('urlfile', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'urltemp');

        // Conditionally launch add field urlfile.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('local_certificados');
        $field = new xmldb_field('urltemp', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'timemodified');

        // Conditionally launch add field urltemp.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $field = new xmldb_field('urlfile', XMLDB_TYPE_CHAR, '500', null, null, null, null, 'urltemp');

        // Conditionally launch add field urlfile.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }


        // Eabcprogramas savepoint reached.
        // upgrade_plugin_savepoint(true, 2020052016, 'local', 'eabcprogramas');
    }

    if ($oldversion < 2020052019) {

        // Define field objetivos to be added to local_eabcprogramas.
        $table = new xmldb_table('local_eabcprogramas');
        $field = new xmldb_field('objetivos', XMLDB_TYPE_TEXT, null, null, null, null, null, 'responsable');

        // Conditionally launch add field objetivos.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

    }

    upgrade_plugin_savepoint(true, 2020052019, 'local', 'eabcprogramas');
}
