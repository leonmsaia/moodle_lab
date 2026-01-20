<?php
/**
 * Script para ocultar las etiquetas de las seccion 0 de todos los cursos streaming y presenciales
 * Utiliza el archivo CSV "eliminar_seccion_cero.csv" el cual tiene los IDs de cursos Moodle
 * Query para poblar el archivos CSV con los IDs:
 * -- select id_curso_moodle from mdl_curso_back where tipomodalidad = "100000000" or modalidaddistancia = "201320001"
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
global $CFG, $DB;

$cursosmoodlecsv = "./eliminar_seccion_cero.csv";
$cursosmoodle = file_get_contents($cursosmoodlecsv);
$idscursos = explode("\n", $cursosmoodle);

$data = new stdClass;
$moduleLabel = $DB->get_record('modules',array('name'=>'label'));

foreach ($idscursos as $idcurso) {
    $course_sections = $DB->get_record('course_sections', array('course' => $idcurso, 'section'=> 0));
    if ($course_sections->sequence){
        $sequencias = explode(',',$course_sections->sequence);
        foreach ($sequencias as $seq) {            
            $module = $DB->get_record('course_modules',array('id'=>$seq));
            if ($module->module == $moduleLabel->id){ // Si es LABEL, ocultar
             $data->id = $seq;
             $data->visible = 0;
             $DB->update_record('course_modules', $data);
            }
        }
        rebuild_course_cache($course_sections->course);
    }
}

echo "Script finalizado. \n";
