<?php

namespace local_password_company\table;
require_once("$CFG->libdir/tablelib.php");
use stdClass;
use core_plugin_manager;
use html_writer;
use moodle_url;

class custom_table extends \table_sql{
    /**
     * @param string $uniqueid a string identifying this table.Used as a key in
     *                          session  vars.
     */
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array(
            'id',
            'name',
            'rut',
            'contrato',
            'razonsocial',
            'timecreate_secure',
            'delete'
        );
        $this->define_columns($columns);
        $headers = array(
            /* get_String('nombre', 'local_manage_grades'),
            get_String('email'),
            get_String('grades', 'local_manage_grades'), */
            'id',
            'Empresa',
            'Rut',
            'Contrato',
            'Razón social',
            'Fecha clave segura asignada',
            'Borrar'
        );
        $this->define_headers($headers);

    }

    /* function other_cols($colname, $row){
        global $DB, $CFG, $USER, $OUTPUT;
        if($colname == 'grade'){
            return '<a class="grade-user" data-userid="'.$row->id.'" data-courseid="'.$row->courseid.'" title="Calificaciones"><img src="'.$OUTPUT->image_url('i/grades', 'moodle').'"></a>
            <a href="'.new moodle_url($CFG->wwwroot.'/local/manage_grades/index.php#report', array('id' => $row->courseid, 'userid' => $row->id, 'report' => 1)).'" data-userid="'.$row->id.'" data-courseid="'.$row->courseid.'" title="Reporte"><img src="'.$OUTPUT->image_url('i/report', 'moodle').'"></a>';
        }
        if($colname == 'enrol'){
            $dateenrol = ($row->timestart == 0) ? $row->timecreated : $row->timestart;
            return date('d/m/Y', $dateenrol);
        }
    } */
    
    function col_delete($row) {
        global $CFG;
        return '<a href="'.new moodle_url($CFG->wwwroot.'/local/password_company/delete.php', array('id' => $row->id)).'" title="Borrar clave segura">Borrar</a>';
    }

}
