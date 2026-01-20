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
//define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../../config.php');

global $DB, $CFG;

require_once($CFG->libdir . '/gradelib.php');

$contentcsv = "./return_grade_csv.csv";
$rutscsv = file_get_contents($contentcsv);
$users = explode("\n", $rutscsv);
$procesados = 0;
$course = 695;
$modinfo = get_fast_modinfo($course);

foreach($users as $key => $user){
    
    if($key > 0){
        
        $registro   = explode(",", $user);
        
        $userrut    = $registro[0];
        $userid     = $registro[1];
        $date       = $registro[2];
        $grade      = $registro[3];

        $get_user = $DB->get_record('user', ['id' => $userid]);
        //solo proceso la linea si el susuario existe y tiene nota en el csv
        if(!empty($get_user) && !empty($grade)){
            $coursecontext = context_course::instance($course);
            //si no esta matriculado en el curso lo matriculo con los criterios de matriculacion
            if(!is_enrolled($coursecontext, $userid)){
                $enrol_data = \local_pubsub\metodos_comunes::enrol_user_elearning($course, $userid , 5);
                //luego de matricularlo le coloco la nota
                if(!empty($enrol_data)){
                    $gradeitemparamsmod = [
                        'courseid' => $course,
                        'itemtype' => 'mod',
                        'itemmodule' => 'quiz'
                    ];
                    //busco los grade items de quiz
                    $grade_mods = \grade_item::fetch_all($gradeitemparamsmod);
                    if (!empty($grade_mods)) {
                        foreach($grade_mods as $grade_mod){
                            echo '<br>llego aca<br>';
                            echo print_r($grade_mod->id, true);
                            if (!empty($grade_mod)) {
                                $calcule_final_grade = ($grade_mod->grademax == 100) ? (floatval($grade) * 10) : floatval($grade);
                                $get_record = $DB->get_record('grade_grades', array('itemid' => $grade_mod->id, 'userid' => $get_user->id));
                                $grade_grade = new grade_grade();
                                $grade_grade->itemid = $grade_mod->id;
                                $grade_grade->userid = $get_user->id;
                                $grade_grade->rawgrade = floatval($calcule_final_grade);
                                $grade_grade->finalgrade = floatval($calcule_final_grade);
                                $grade_grade->rawgrademax = floatval($grade_mod->grademax);
                                $grade_grade->rawgrademin = floatval($grade_mod->grademin);
                                $grade_grade->timecreated = time();
                                $grade_grade->timemodified = time();
                                if (empty($get_record)) {
                                    $grade_grade->insert();
                                } else {
                                    $grade_grade->id = $get_record->id;
                                    $grade_grade->update();
                                }
                                grade_regrade_final_grades($course, $course, $grade_mod);
                            }
                        }
                    }
                }
            } else {
                echo '<br>usuario no se a matriculado';
            }
        }
    }
        
}

echo "\n Fin de la ejecución. Procesados: ".$procesados. " de un total de: ".count($users)."\n\n";
