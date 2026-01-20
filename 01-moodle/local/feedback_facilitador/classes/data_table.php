<?php

class data_table extends table_sql {

    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array('facilitador_id', 'nombre', 'cumplimiento_plazo','cumplimiento_cierre','encuesta_facilitador','cumplimiento_horario','total_cumplimiento','cantidad_cursos','cantidad_cursos_suspendidos','recomienda_curso','envio_correo','llamo_empresa','encuesta_curso');
        $this->define_columns($columns);

        $headers = array(
            'id',
            'Nombre',
            'Cumplimiento plazo',
            'Cumplimiento cierre',
            'Resultados Encuesta facilitador',
            'Cumplimiento Horario',
            'Total Cumplimiento',
            'Cantidad cursos',
            'Cantidad cursos supendidos',
            'Recomienda curso',
            'Envio correo emp',
            'Llamo emp',
            'Resultados encuesta curso',
        );
        $this->define_headers($headers);
    }

    function define_columns($columns) {
        return parent::define_columns($columns);
    }

    function define_headers($headers) {
        return parent::define_headers($headers);
    }
}