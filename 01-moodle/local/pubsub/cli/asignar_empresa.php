<?php
/*
El siguiente script recorre la tabla inscripcion_elearning_back en busca de participantes sin empresa relacionada.
verifica que cada registro tenga una empresa asociada. De no tenerlo, se le asigna la empresa segun 
los campos participanterutadherente y participantedvadherente. 
Si no existe la empresa, no se hace nada ya que no tenemos datos suficientes para dar de alta la misma.
*/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

use local_company;

/** @var moodle_database $DB */
global $CFG, $DB;

$participantes = $DB->get_records('inscripcion_elearning_back');

foreach ($participantes as $participante) {
    
    //Verificar si la Empresa existe
    $companyrut = $participante->participanterutadherente."-".$participante->participantedvadherente;
    $get_company_by_rut = $DB->get_record('company', array('rut' => $companyrut));        
    $companyid = ($get_company_by_rut) ? $get_company_by_rut->id : '';

    if ($companyid!=""){
        //asignar usuario a compañia
        /* $company = new \company($companyid);
        $company->assign_user_to_company($participante->id_user_moodle); */
        \local_company\metodos_comunes::assign($companyid, $participante->id_user_moodle);
        var_dump($participante); // Muestra solo los participantes que se le asigaron empresa
    }
}
