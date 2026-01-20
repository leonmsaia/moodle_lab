<?php
/*
Script recorre la tabla temporal mdl_users_sin_company para asignar las empresas mediante el servicio de Nominativo.
*/

define('CLI_SCRIPT', true);

/** @var moodle_database $DB */
global $DB, $CFG;

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

// SE ELIMINA LA TABLA TEMPORAL SI EXISTE
/* $sql = "DROP TABLE IF EXISTS {users_sin_company}";
$DB->execute($sql, array());

// SE CREA LA TABLA TEMPORAL 
$sql = "CREATE TABLE {users_sin_company} (userid int(11),rut varchar(255),procesado varchar(255))";                
$DB->execute($sql, array());

// SE LLENA CON LOS USUARIOS QUE NO TIENEN EMPRESA ASOCIADA
$sql2 = "INSERT INTO mdl_users_sin_company (userid,rut,procesado)
    SELECT u.id, u.username, ''
    FROM mdl_user u
    LEFT JOIN mdl_company_users cu
        ON cu.userid = u.id 
    WHERE cu.userid IS NULL";
     */
//$DB->execute($sql2, array());

$users = $DB->get_records('users_sin_company', array());

foreach($users as $user){
    $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($user->rut, 1);
    if ($datosNominativo->return->error == 0){
        //datos empresa
        foreach($datosNominativo->return->empresas as $empresa){
            if($empresa->activo == 1){
                $array_aditional_files = array(
                    "empresarut"            => (string) $empresa->rut."-".$empresa->dv,
                    "empresarazonsocial"    => (string) $empresa->razonSocial,
                    "empresacontrato"       => (string) $empresa->contrato
                ); 

                //Guardo en campos personalizados del user
                profile_save_custom_fields($user->userid, $array_aditional_files);  
                // Se marca como procesado ok
                $params['procesado'] = (string) $datosNominativo->return->mensaje;

                $get_company_by_rut = $DB->get_record('company', array('rut' => $array_aditional_files['empresarut']));
                if (!empty($get_company_by_rut)) {
                    // Si la empresa existe obtengo el Id
                    $companyid = $get_company_by_rut->id;
                }else{
                    // Si la empresa No existe se registra y obtengo el ID
                    $dataempresa                = new stdClass();
                    $dataempresa->rut           = $array_aditional_files['empresarut'];
                    $dataempresa->contrato      = $array_aditional_files['empresacontrato'];
                    $dataempresa->razon_social  = $array_aditional_files['empresarazonsocial'];
                    $companyid      = \local_mutual\front\utils::create_company($dataempresa);
                }
                // Se le asigna la empresa al usuario
                $user_company   = \local_mutual\front\utils::assign_user_company($companyid, $user->userid);
                break;
            }    
        }
    }else{
        // Se marca No procesado con error y mensaje
        $params['procesado'] = (string) $datosNominativo->return->error . " - ".(string) $datosNominativo->return->mensaje;
    }

     // Se marca el registro para saber si se proceso
     $sql = "UPDATE {users_sin_company} SET procesado = :procesado WHERE userid = :userid";             
     $params['userid'] = $user->userid;                
     $DB->execute($sql, $params);   
}

echo "\n Fin de ejecución del script, revisar tabla 'users_sin_company' para consular cuantos se procesaron. Los mismos deben tener procesado en OK";