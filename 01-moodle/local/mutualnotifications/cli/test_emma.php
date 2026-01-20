 <?php
 /*
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
global $CFG;

$newuserid = 47;
$courseid = 12;

\local_mutualnotifications\utils::course_welcome($newuserid, $courseid, false, "");
*/ 


define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once(dirname(__FILE__).'/../../../message/output/emma/classes/message_procesor.php');
global $DB;

$newuserid = 54188;
$courseid = 253;

$from = \core_user::get_noreply_user();
$course = $DB->get_record('course', ['id' => $courseid]);
$user = $DB->get_record('user', ['id' => $newuserid]);
$messagehtml = "<p>Prueba envio Emma 33 {$course->fullname}.</p>";
$subject = "Curso test";

$sesion = $DB->get_record('eabcattendance_sessions', ['id' => 6801]);
//var_dump($sesion);exit();

//\local_mutualnotifications\utils::send_message($from, $user,$subject, $messagehtml, $courseid);
//\local_mutualnotifications\utils::course_welcome($newuserid, $courseid, false, "");
//\local_mutualnotifications\utils::course_welcome_streaming_presencial($newuserid, $course, $sesion, $enrol_passport = false, $password_generate = "");


/*  $message_procesor   = new \emma\message\message_procesor();
$messageEmma = new \stdClass();
$messageEmma->userfrom = new \stdClass();
$messageEmma->userto = new \stdClass();
$messageEmma->subject           = 'Información de curso';
$messageEmma->fullmessagehtml   = $messagehtml;
$messageEmma->fullmessage       = $messagehtml;
$messageEmma->userfrom->id      = 2;
$messageEmma->userto->id        = 47;
$messageEmma->userto->email     = 'alain@e-abclearning.com';
//$messageEmma->userfrom->email   = 'noreply@mutual.cl';
$messageEmma->fullmessageformat = 1;
$attachment='';

$send = $message_procesor->enviadirecto($messageEmma, $attachment);

var_dump($send);  */