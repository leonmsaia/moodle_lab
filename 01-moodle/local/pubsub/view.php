<?php

/**
 * @throws coding_exception
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/local/pubsub/lib.php');
//require_once($CFG->dirroot . '/webservice/externallib.php');
require_once($CFG->libdir . '/externallib.php');


/** @var moodle_page $PAGE */
global $PAGE, $OUTPUT, $DB, $USER;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/pubsub/view.php'));
$eabctiles_closegroup = "";



echo $OUTPUT->header();
echo "<h1>Welcome to local_pubsub</h1>";

//crea un curso con dos grupos y una sesion en cada uno a partir de los datos del backend
/* 
$sesionid = external_api::call_external_function("local_pubsub_create_sesion", ["Id" => "6b5188eb-935e-ea11-a811-000d3a4f658a" , "IdCurso" => "28ba1985-885e-ea11-a811-000d3a4f658a", "IdEvento" => "ad6719bc-472f-480e-9831-67470e1acc8e" ,"Action" => "Alta"]);
echo "<br>".var_dump($sesionid);

$sesionid = external_api::call_external_function("local_pubsub_create_sesion", ["Id" => "47886431-955e-ea11-a811-000d3a4f658a" , "IdCurso" => "28ba1985-885e-ea11-a811-000d3a4f658a", "IdEvento" => "64958fa0-d640-4d1c-b97a-c21701e9d5a9" ,"Action" => "Alta"]);					
echo "<br>".var_dump($sesionid); */

echo $OUTPUT->footer();
