<?php
/*
 * Script para insertar una actividad typo URL en todos los cursos
*/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot.'/course/modlib.php');

global $DB, $CFG;

$id_curso_desde = optional_param('id_curso_desde', '', PARAM_RAW);
$id_curso_hasta = optional_param('id_curso_hasta', '', PARAM_RAW);

$procesados = 0;
$procesados_ant = 0;
$url = $CFG->wwwroot.'/local/encuesta_movilidad/view.php';

$module_url =  $DB->get_record('modules', array('name' => 'url'));
$module_quiz =  $DB->get_record('modules', array('name' => 'quiz'));

if (!$id_curso_desde || !$id_curso_hasta){
    echo "Faltan parametros para ejecutar";
    exit;
}

$sql = "SELECT * FROM {course} WHERE id >= :id_curso_desde and id <= :id_curso_hasta and visible = 1";
$params['id_curso_desde'] =  $id_curso_desde;
$params['id_curso_hasta'] =  $id_curso_hasta;
$courses = $DB->get_records_sql($sql, $params);

foreach($courses as $course){

    $encuesta = $DB->get_record('url', array('course' => $course->id, 'name' => 'Encuesta de movilidad'));

    if(empty($encuesta)){

        $section = $DB->get_record('course_sections', array('course' => $course->id, 'section' => 2));

        if(!$section){
            $sql = "SELECT * FROM {course_sections} WHERE course = :courseid ORDER BY id DESC LIMIT 1";
            $params['courseid'] =  $course->id;
            $section = $DB->get_record_sql($sql, $params);
        }

        try {
            $resource = new stdClass;
            $resource->course = $course->id;
            $resource->name = 'Encuesta de movilidad';
            $resource->intro = '<p>Encuesta de movilidad</p>';
            $resource->introformat = 1;
            $resource->externalurl = $url;
            $resource->display = 0;
            $resource->timemodified = time();
    
            $newresourceid = $DB->insert_record('url', $resource, true);
    
            $mod = new stdClass;
            $mod->course = $course->id;
            $mod->module = $module_url->id;
            $mod->section = $section->section;
            $mod->added = time();
            $mod->instance = $newresourceid;
    
            $modid = add_course_module($mod);
            $resp  = course_add_cm_to_section($course->id, $modid, $section->section);

            $sql = "SELECT * FROM {course_modules} WHERE course = :courseid AND module = :moduleid ORDER BY id ASC LIMIT 1";
            $params['courseid'] =  $course->id;
            $params['moduleid'] =  $module_quiz->id;
            $course_module_quiz = $DB->get_record_sql($sql, $params);

            if ($course_module_quiz){
                $sequences =  explode(',',$section->sequence);
                $key = array_search($course_module_quiz->id, $sequences);
                $inserted = array($modid);
                array_splice($sequences, $key, 0, $inserted);
                $sequences_mod = implode(",", $sequences);

                $record = new \stdClass();
                $record->id         = $section->id;
                $record->sequence   = $sequences_mod;
                $DB->update_record('course_sections', $record);
                rebuild_course_cache($course->id, true);
            }
            
            $procesados++;
        } catch (\Exception $e) {
            echo "Error: ".$e;
        }    
    }else{
        $procesados_ant++;
    }
}

echo "Procesados: ".$procesados. " de un total: ".count($courses)." , ya se encontraban previamente: ".$procesados_ant;