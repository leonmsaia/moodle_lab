<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
/** @var moodle_database $DB */
global $CFG, $DB;

$sql = 'SELECT cb.productocurso as productocurso, cb.id as cursobackid, c.id as courseid, cb.productoid, c.fullname, c.shortname, c.idnumber, cb.codigocurso as codigocurso, cb.id_curso_moodle as idold
from {curso_back} as cb
join {course} c on c.idnumber = cb.codigosuseso
where c.fullname = c.shortname and cb.id_curso_moodle != c.id';

$cursos = $DB->get_records_sql($sql);

foreach ($cursos as $curso) {
    // 1) Actualizo el id_curso_moodle de mdl_curso_back con el id de mdl_course 
    $datos_cursoback = new \stdClass(); 
    $datos_cursoback->id = $curso->cursobackid;
    $datos_cursoback->id_curso_moodle = $curso->courseid;
    $DB->update_record('curso_back', $datos_cursoback);
   
    // 2) Actualizo el shortname y fullname de mdl_course con el codigocurso de mdl_curso_back
    $datos_coursemoodle = new \stdClass(); 
    $datos_coursemoodle->id = $curso->courseid;
    $datos_coursemoodle->shortname = $curso->codigocurso;
    $datos_coursemoodle->fullname = $curso->productocurso;
    $DB->update_record('course', $datos_coursemoodle);

    // 3) Actualizo el id_curso_moodle de mdl_inscripcion_elearning_back con el id de mdl_course
    $params = array(
        'id_curso_moodle'   => $curso->courseid,
        'idold'             => $curso->idold
    );
    $DB->execute(
        "UPDATE {inscripcion_elearning_back}
                SET id_curso_moodle = :id_curso_moodle
                WHERE id_curso_moodle = :idold",
        $params
    );

    $DB->execute(
        "UPDATE {inscripcion_elearning_log}
                SET id_curso_moodle = :id_curso_moodle
                WHERE id_curso_moodle = :idold",
        $params
    );

    //4 Inscribir a los participantes de mdl_inscripcion_elearning_back que se actualizó en el nuevo curso
    $enrolinstances = enrol_get_instances($curso->courseid, true);
    foreach ($enrolinstances as $courseenrolinstance) {
        if ($courseenrolinstance->enrol == "manual") {
            $instance = $courseenrolinstance;
            break;
        }
    }
    $enrol = enrol_get_plugin('manual');

    $sql = 'SELECT id_user_moodle from {inscripcion_elearning_back} where id_curso_moodle = :id_curso_moodle';
    $participantes = $DB->get_records_sql($sql,$params);

    foreach($participantes as $participante){
        try{
            $enrol->enrol_user($instance, $participante->id_user_moodle, 5);
        }catch (\Exception $e) {
            echo "Error con userid: " .$participante->id_user_moodle. " Mensaje del error: " .$e->getMessage();
        }
    }
    
    // 5 Inactivar el curso con el id_curso_moodle que se reemplazó en el punto 1.
    $DB->execute("UPDATE {course} SET visible = 0 WHERE id = :idold", $params);
}
