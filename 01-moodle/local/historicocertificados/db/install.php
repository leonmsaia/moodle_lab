<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Script de instalación del plugin local_historicocertificados
 */
function xmldb_local_historicocertificados_install()
{
    global $DB;

    $dbman = $DB->get_manager();

    // === TABLA 1: inscripcion_elearning_back_35 ===
    $table1 = new xmldb_table('inscripcion_elearning_back_35');

    if (!$dbman->table_exists($table1)) {
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table1->add_field('participanteidregistroparticip', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participanteproductid', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantefechainscripcion', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantenombre', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participanteapellido1', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participanteapellido2', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantetipodocumento', XMLDB_TYPE_INTEGER, '3', null);
        $table1->add_field('participanterut', XMLDB_TYPE_INTEGER, '20', null);
        $table1->add_field('participantedv', XMLDB_TYPE_CHAR, '1', null);
        $table1->add_field('participantepasaporte', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantefechanacimiento', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participanteidsexo', XMLDB_TYPE_INTEGER, '3', null);
        $table1->add_field('participanteemail', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantetelefonomovil', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantetelefonofijo', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantepais', XMLDB_TYPE_INTEGER, '3', null);
        $table1->add_field('participantecargo', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participanteidrol', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('participantecodigocomuna', XMLDB_TYPE_INTEGER, '10', null);
        $table1->add_field('participantedireccion', XMLDB_TYPE_TEXT, null);
        $table1->add_field('participanterutadherente', XMLDB_TYPE_INTEGER, '20', null);
        $table1->add_field('participantedvadherente', XMLDB_TYPE_CHAR, '1', null);
        $table1->add_field('responsablenombre', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsableapellido1', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsableapellido2', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsabletipodocumento', XMLDB_TYPE_INTEGER, '3', null);
        $table1->add_field('responsablerut', XMLDB_TYPE_INTEGER, '20', null);
        $table1->add_field('responsabledv', XMLDB_TYPE_CHAR, '1', null);
        $table1->add_field('responsablepasaporte', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsablefechanacimiento', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsableidsexo', XMLDB_TYPE_INTEGER, '3', null);
        $table1->add_field('responsableemail', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsabletelefonomovil', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsabletelefonofijo', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsablecargo', XMLDB_TYPE_CHAR, '255', null);
        $table1->add_field('responsablecodigocomuna', XMLDB_TYPE_INTEGER, '20', null);
        $table1->add_field('responsablecodigoregion', XMLDB_TYPE_INTEGER, '10', null);
        $table1->add_field('responsabledireccion', XMLDB_TYPE_TEXT, null);
        $table1->add_field('id_curso_moodle', XMLDB_TYPE_INTEGER, '10', null);
        $table1->add_field('id_user_moodle', XMLDB_TYPE_INTEGER, '20', null);
        $table1->add_field('createdat', XMLDB_TYPE_CHAR, '20', null);
        $table1->add_field('updatedat', XMLDB_TYPE_CHAR, '20', null);
        $table1->add_field('timereported', XMLDB_TYPE_CHAR, '100', null, null, null, '0');

        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $dbman->create_table($table1);
    }

    // === TABLA 2: certificados_back_35 ===
    $table2 = new xmldb_table('certificados_back_35');

    if (!$dbman->table_exists($table2)) {
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table2->add_field('iddocumento', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table2->add_field('idinscripcion', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
        $table2->add_field('idsesion', XMLDB_TYPE_CHAR, '100', null);
        $table2->add_field('modalidad', XMLDB_TYPE_CHAR, '100', null);
        $table2->add_field('tipodocumento', XMLDB_TYPE_CHAR, '10', null);
        $table2->add_field('fechaexpiracion', XMLDB_TYPE_CHAR, '100', null);
        $table2->add_field('urlarchivo', XMLDB_TYPE_TEXT, null);
        $table2->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null);
        $table2->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);

        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table2->add_index('idinscripcion_index', XMLDB_INDEX_NOTUNIQUE, ['idinscripcion']);
        $table2->add_index('iddocumento_index', XMLDB_INDEX_NOTUNIQUE, ['iddocumento']);

        $dbman->create_table($table2);

        // =====================================================
        // TABLA: inscripciones_back_35
        // =====================================================
        $table3 = new xmldb_table('inscripciones_back_35');
        if (!$dbman->table_exists($table3)) {
            $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table3->add_field('idinscripcion', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('idinterno', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('idsexo', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('numeroadherente', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participanteapellido1', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participanteapellido2', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participanteemail', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participantefono', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participanteidentificador', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participantenombre', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('participantepais', XMLDB_TYPE_INTEGER, '3', null);
            $table3->add_field('participantetipodocumento', XMLDB_TYPE_INTEGER, '3', null);
            $table3->add_field('responsableemail', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('responsableidentificador', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('responsablenombres', XMLDB_TYPE_CHAR, '255', null);
            $table3->add_field('id_sesion_moodle', XMLDB_TYPE_INTEGER, '10', null);
            $table3->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table3);
        }

        // =====================================================
        // TABLA: inscripcion_elearning_log_35
        // =====================================================
        $table4 = new xmldb_table('inscripcion_elearning_log_35');
        if (!$dbman->table_exists($table4)) {
            $table4->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table4->add_field('id_curso_moodle', XMLDB_TYPE_INTEGER, '10', null);
            $table4->add_field('id_user_moodle', XMLDB_TYPE_INTEGER, '10', null);
            $table4->add_field('participanteproductid', XMLDB_TYPE_CHAR, '255', null);
            $table4->add_field('participanteidregistroparticip', XMLDB_TYPE_CHAR, '255', null);
            $table4->add_field('created_at', XMLDB_TYPE_CHAR, '20', null);
            $table4->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table4);
        }

        // =====================================================
        // TABLA: user_35  (copia del esquema de mdl_user)
        // =====================================================
        $table = new xmldb_table('user_35');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('auth', XMLDB_TYPE_CHAR, '20', null);
            $table->add_field('confirmed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('policyagreed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('deleted', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('suspended', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('mnethostid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('username', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
            $table->add_field('idnumber', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('firstname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('lastname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('phone1', XMLDB_TYPE_CHAR, '20', null);
            $table->add_field('phone2', XMLDB_TYPE_CHAR, '20', null);
            $table->add_field('institution', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('department', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('address', XMLDB_TYPE_TEXT, null);
            $table->add_field('city', XMLDB_TYPE_CHAR, '120', null);
            $table->add_field('country', XMLDB_TYPE_CHAR, '2', null);
            $table->add_field('lang', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, 'es');
            $table->add_field('timezone', XMLDB_TYPE_CHAR, '100', null);
            $table->add_field('firstaccess', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('lastaccess', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('lastlogin', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('currentlogin', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('lastip', XMLDB_TYPE_CHAR, '45', null);
            $table->add_field('secret', XMLDB_TYPE_CHAR, '15', null);
            $table->add_field('picture', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null);
            $table->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('mailformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('maildisplay', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 2);
            $table->add_field('autosubscribe', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('trackforums', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('lastmodified', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // =====================================================
        // TABLA: course_35 (copia del esquema de mdl_course)
        // =====================================================
        $table = new xmldb_table('course_35');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('category', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('fullname', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL);
            $table->add_field('shortname', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL);
            $table->add_field('idnumber', XMLDB_TYPE_CHAR, '100', null);
            $table->add_field('summary', XMLDB_TYPE_TEXT, null);
            $table->add_field('summaryformat', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('format', XMLDB_TYPE_CHAR, '21', null, XMLDB_NOTNULL, null, 'topics');
            $table->add_field('showgrades', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('startdate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('enddate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('visible', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 1);
            $table->add_field('groupmode', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('groupmodeforce', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('defaultgroupingid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('requested', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, 0);
            $table->add_field('enablecompletion', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        // =====================================================
        // TABLA: curso_back_35
        // =====================================================
        $table = new xmldb_table('curso_back_35');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_field('backcapacitacion', XMLDB_TYPE_CHAR, '100', null);
            $table->add_field('codigocurso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('codigopeligro', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('codigosuseso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('contenidos', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('cursofoco', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('descripcion', XMLDB_TYPE_TEXT, null);
            $table->add_field('disponibleenportal', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('estado', XMLDB_TYPE_INTEGER, '3', null);
            $table->add_field('fechaenvio', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('foliosuseso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('horas', XMLDB_TYPE_INTEGER, '3', null);
            $table->add_field('identificadorsuseso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('maximotiempo', XMLDB_TYPE_INTEGER, '12', null);
            $table->add_field('minimotiempo', XMLDB_TYPE_INTEGER, '3', null);
            $table->add_field('modalidaddistancia', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('nombrecorto', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('objetivo', XMLDB_TYPE_TEXT, null);
            $table->add_field('observacionsuseso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('productocurso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('productoid', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('proveedor', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('tematica', XMLDB_TYPE_TEXT, null);
            $table->add_field('tipocurso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('tipomodalidad', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('vigenciadocumentos', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('id_curso_moodle', XMLDB_TYPE_INTEGER, '10', null);
            $table->add_field('codigoproveedordistancia', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('estadoenviosuseso', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('nombreproveedorcursodistancia', XMLDB_TYPE_CHAR, '255', null);
            $table->add_field('modalidad', XMLDB_TYPE_CHAR, '30', null);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }
    }
}
