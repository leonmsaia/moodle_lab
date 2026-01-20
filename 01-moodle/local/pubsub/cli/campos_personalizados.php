<?php
/*
El siguiente script recorre la tabla usuarios
actualiza los campos personalizados de usuario en base a la tabla inscripcion elearning back
y asocia esos usuarios a empresas*/

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');

/** @var moodle_database $DB */
global $CFG, $DB;
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . "/user/profile/lib.php");

$users = $participantes = $DB->get_records('user');
foreach ($users as $user) {
    $i_elearning = $participantes = $DB->get_records('inscripcion_elearning_back', array('id_user_moodle' => $user->id));
    
    if(!empty($i_elearning)){
        
        $obj_end = end($i_elearning);
        $data = \local_mutual\front\utils::create_object_user_custon_field($obj_end);
        $custom_field = \local_mutual\front\utils::insert_custom_fields_user($user->id, $data);
        if($custom_field["error"] == ""){
            $get_company_by_rut = $DB->get_record('company', array('rut' => $obj_end->participanterutadherente . "-" . $obj_end->participantedvadherente));
            if(!empty($get_company_by_rut)){
                $user_company = \local_mutual\front\utils::assign_user_company($get_company_by_rut->id, $user->id);
                if(!empty($user_company["error"])){
                    echo "error asignando user a company: " . print_r($user_company, true);
                }
            }
        } else {
            echo "error cargando data: " . print_r($custom_field, true);
        }
    }
}

