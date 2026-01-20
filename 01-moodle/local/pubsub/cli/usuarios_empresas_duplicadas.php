<?php
/*
 * Script para leer un CSV con RUTs de usuarios que consulte el servicio de Nominativo
 * y actualice la empresa del usuario
 * El CSV "apellido_materno.csv" debe 2 columnas, una con el id Usery y otra con los RUTS de los usuarios
 * el query para llenar el CSV es el siguiente:
    Select cu.userid, u.username
    from prefix_company_users cu
    join prefix_user u on u.id = cu.userid
    group by cu.userid
    HAVING count(cu.companyid) > 1
*/
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

global $DB, $CFG;

$contentcsv = "./usuarios_empresas_duplicadas.csv";
$rutscsv = file_get_contents($contentcsv);
$users = explode("\n", $rutscsv);
$procesados = 0;

foreach($users as $user){
    $registro   = explode(",", $user);
    $userid     = $registro[0];
    $userrut    = $registro[1];
        
    $datosNominativo = \local_mutual\back\utils::get_personas_nominativo($userrut, 1);    

    if ( isset($datosNominativo->return->error) &&  ($datosNominativo->return->error == 0)){
        foreach($datosNominativo->return->empresas as $empresa){
            if($empresa->activo == 1){
                $rut_empresa = $empresa->rut."-".$empresa->dv;
                $empresa_existe = $DB->get_records('company',array('rut'=>$rut_empresa));
                // Si no existe ninguna empresa con ese RUT se crea una nueva
                if (empty($empresa_existe)){
                    $dataempresa                = new stdClass();
                    $dataempresa->rut           = (string) $rut_empresa;
                    $dataempresa->contrato      = (string) $empresa->contrato;
                    $dataempresa->razon_social  = (string) $empresa->razonSocial;
                    $companyid                  = \local_mutual\front\utils::create_company($dataempresa);
                }else{
                    foreach($empresa_existe as $emp){
                        $companyid      = $emp->id;
                        break;
                    }
                }
                //Elimino todas las empresas que tenga el usuario asociadas
                $DB->delete_records('company_users',array('userid' => $userid));
                //Asigno la empresa del servicio al usuario
                \local_mutual\front\utils::assign_user_company($companyid, $userid);
            }
        }
        ++$procesados;
    }else{
        // Se marca No procesado con error y mensaje
        $error = (string) $datosNominativo->return->error . " - ".(string) $datosNominativo->return->mensaje;
        echo "Error en ". $error."\n";
    }
}

echo "\n Fin de la ejecución. Procesados: ".$procesados. " de un total de: ".count($users)."\n\n";