<?php

/*
El siguiente script elimina las empresas duplicadas 
y en caso de que los usuarios esten en varias de las empresas lo deja en una sola
*/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

/** @var moodle_database $DB */
global $CFG, $DB;

    //empresas repetidas asignar usuarios a una sola empresa
try {
    $transaction = $DB->start_delegated_transaction();
    //busco las empresas y la cantidad de veces registradas segun su rut
    $sql = 'select c.id, c.rut, count(c.id) as cantidad from {company} c group by c.rut';
    $duplicate_companys = $DB->get_records_sql($sql);
    foreach($duplicate_companys as $duplicate_company) {
        //busco las empresas que si esten repetidas( mas de una vez)
        if($duplicate_company->cantidad > 0){
            //primera empresa que consigo si esta repetida
            $companyid = $duplicate_company->id;

            //busco los usuarios de esa empresa
            $sql_companyuser = 'select cu.id as idcompanyuser, c.name as empresa, 
            c.rut, c.contrato, cu.userid as useridcompany
            from {company_users} cu
            join {company} c on c.id = cu.companyid WHERE c.rut = "' . $duplicate_company->rut . '"';
            $empresas_detalles = $DB->get_records_sql($sql_companyuser);

            if(!empty($empresas_detalles) ){
                foreach($empresas_detalles as $empresas_detalle) {
                    //consulto si ese el usuario relacionado a la empresa esta en varios registros de la tabla solo puede existir una relacion usuario empresa
                    $get_user_company = $DB->get_records('company_users', array('userid' => $empresas_detalle->useridcompany));
                    
                    if(!empty($get_user_company) && count($get_user_company) > 1) {
                        //si ya tiene un registro en esa empresa borro el registro para evitar duplicidad de datos
                        $useridcompany = $empresas_detalle->useridcompany;
                        $DB->delete_records('company_users', array('userid' =>  $useridcompany));
                        $company = new \company($companyid);
                        $company->assign_user_to_company($useridcompany);
                    } else {
                        //si solo existe un registro lo actualizo para optimizar el query
                        $dataobject = new stdClass();
                        $dataobject->id = $empresas_detalle->idcompanyuser;
                        $dataobject->companyid = $companyid;
                        $DB->update_record('company_users', $dataobject);
                    }
                    
                }
            }
            
        }
        
    }
    $transaction->allow_commit();
    echo "Finalizo proceso pasar usuario a una sola empresa \n";
} catch (\Exception $e) {
    echo "Error al pasar usuarios de empresas: ",  $e->getMessage(), "\n";
    $transaction->rollback($e);
}

//borrar empresas que no tengan usuarios relacionados
$sqldesactive = 'select c.id as idcompany, 
(
    select count(*) from {company_users} cu
    WHERE cu.companyid = c.id
) as cantidad_empleados
from {company} c

';

try {
    $transactionCompany = $DB->start_delegated_transaction();
    $duplicate_companys_sactives = $DB->get_records_sql($sqldesactive);
    //echo '<pre>company : ' . print_r($duplicate_companys_sactives, true) . '</pre><br>';
    
    foreach($duplicate_companys_sactives as $duplicate_companys_sactive) {
        if($duplicate_companys_sactive->cantidad_empleados == 0){
            $DB->delete_records('company', array('id' => $duplicate_companys_sactive->idcompany));
        }
    }
    $transactionCompany->allow_commit();
    echo "Finalizo proceso borrar empresas duplicadas \n";
} catch (\Throwable $th) {
    echo "Error al al borrar empresas duplicadas: ",  $e->getMessage(), "\n";
    $transactionCompany->rollback($e);
}
