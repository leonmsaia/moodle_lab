<?php
/*
 * Script de Prueba de funcion get_personas nominativo
 */

use local_mutual\back\utils;
global $CFG;

require_once('../../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

$rut = '11111111-1';
$tipo = 1;

$datosNominativo = utils::get_personas_nominativo($rut,$tipo);
    
if ($datosNominativo->return->error == 0){
    foreach($datosNominativo->return->empresas as $empresa){
        if($empresa->activo == 1){            
            $array_aditional_files = array(
                "empresarut"            => $empresa->rut."-".$empresa->dv,
                "empresarazonsocial"    => (string) $empresa->razonSocial,
                "empresacontrato"       => (string) $empresa->contrato
            ); 
            break;
        }    
    }    
}

profile_save_custom_fields(2, $array_aditional_files);

var_dump($datosNominativo);