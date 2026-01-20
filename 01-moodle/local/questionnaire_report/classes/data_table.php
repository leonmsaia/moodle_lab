<?php

class data_table extends table_sql {

    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array('id', 'course_fullname',  'user', 'username','participantecargo','participantefechanacimiento', 'participantesexo', 'empresarut', 'empresarazonsocial', 'empresacontrato', 'pregunta','respuesta');
        $this->define_columns($columns);    

        $headers = array(
            'id',
            'Curso',
            'Usuario',
            'RUT',
            'Cargo',
            'Fecha nac',
            'Sexo',
            'RUT Empresa',
            'Razon social',
            'Contrato',            
            'pregunta',
            'respuesta',
        );
        $this->define_headers($headers);
    }
}