<?php

require_once '../../config.php';
global $PAGE, $DB, $OUTPUT, $SESSION;

require_once($CFG->libdir . '/adminlib.php');
try {
    
    $action = optional_param('action', '', PARAM_RAW);
    $attempt = optional_param('attempt', '', PARAM_RAW);
    $attempsquiz = optional_param('attempsquiz', 0, PARAM_INT);
    $attempsquizuser = optional_param('attempsquizuser', 0, PARAM_INT);
    $cmid = optional_param('cmid', '', PARAM_RAW);
    $courseid = optional_param('courseid', '', PARAM_RAW);
    $quizid = optional_param('quizid', '', PARAM_RAW);
    $sumgrade = optional_param('sumgrade', '', PARAM_RAW);
    
    $course = $DB->get_record('course', ['id' => $courseid]);
    require_login($course);
    $context = \context_course::instance($course->id);
    $PAGE->set_context($context);

    define("CONFIRMED", 1);
    define("CANCEL", 0);

    $options = array(
        'action' => $action,
        'attempt' => $attempt,
        'finishattempt' => 1,
        'timeup' => 0,
        'slots' => '',
        'cmid' => $cmid,
        'sesskey' => sesskey(),
    );

    $PAGE->set_url(new moodle_url('/local/sendgrade/confirm_send.php', $options));
    
    echo $OUTPUT->header();
    //simulo el comportamiento nativo enviado la nota luego de cofirmar o rechazar

    if ($action == 'confirm') {
        //seguir con los intentos
        $decode_grade = base64_decode($sumgrade);
        $grade_user_course = $DB->get_record('grade_grades', ['itemid' => $item_id_course->id, 'userid' => $USER->id]);
        $grade = ($sumgrade >= $grade_user_course->finalgrade) ? $decode_grade : $grade_user_course->finalgrade;
        \local_sendgrade\utils::send_grade_log($USER->id, $course->id, $grade, $quizid, CANCEL, $cmid);
        redirect(new \moodle_url('/course/view.php?id=' . $course->id));
    } else if ($action == 'cancel') {
        //enviar nota
        $SESSION->send_grade = 'cancel';
        $decode_grade = base64_decode($sumgrade);
        $item_id_course = $DB->get_record('grade_items', ['itemtype' => 'course', 'courseid' => $courseid]);
        $grade_user_course = $DB->get_record('grade_grades', ['itemid' => $item_id_course->id, 'userid' => $USER->id
        ]);
        //busco el mayor entre la nota del curso y la nota del usuario
        $grade = ($sumgrade >= $grade_user_course->finalgrade) ? $decode_grade : $grade_user_course->finalgrade;

        $SESSION->send_grade = 'procesar';
        \local_sendgrade\utils::send_grade($USER->id, $course->id, round((float) $grade, 2), $item_id_course);
        $params = array(
            'userid'  => $USER->id,
            'course'  => $course->id
        );
        $ccompletion = new \completion_completion($params);
        $ccompletion->mark_complete();

        \local_sendgrade\utils::send_grade_log($USER->id, $course->id, $grade, $quizid, CONFIRMED, $cmid);
        redirect(new \moodle_url('/course/view.php?id=' . $course->id));
    } else {
        $actionurl = new moodle_url('/local/sendgrade/confirm_send.php', array('action' => 'confirm', 'attempt' => $attempt, 'attempsquiz' => $attempsquiz, 'attempsquizuser' => $attempsquizuser, 'cmid' => $cmid, 'courseid' => $courseid, 'quizid' => $quizid, 'sumgrade' => $sumgrade));
        $cancelurl  = new moodle_url('/local/sendgrade/confirm_send.php', array('action' => 'cancel', 'attempt' => $attempt, 'cmid' => $cmid, 'courseid' => $courseid, 'quizid' => $quizid, 'sumgrade' => $sumgrade));
        //despues de confirmar o rechazar debo simular el comportamiento nativo para guardar la nota del usuario esta accion de submit es antes de guardar la nota
        $messageResponse = "Usted ha aprobado el curso con nota " . round((float) base64_decode($sumgrade), 2) . ", le queda(n) " . ($attempsquiz - $attempsquizuser) . " intento(s) restante(s). Si desea realizar un nuevo intento presione 'Realizar otro intento', de lo contrario presione 'Finalizar curso' para finalizar el curso y enviar la calificación.";
        if($attempsquiz > 1) {
            $messageResponse = "Usted tiene el curso aprobado. De los intentos realizados su calificación más alta es " . round((float) base64_decode($sumgrade), 2) . ", le queda(n) " . ($attempsquiz - $attempsquizuser) . " intento(s). Si quiere finalizar el curso y enviar su calificación, presione 'FINALIZAR CURSO', de lo contrario, si quiere realizar otro intento presione 'REALIZAR OTRO INTENTO', recuerde que, si ha seleccionado 'realizar otro intento', su nota no será enviada hasta que se realice el nuevo intento o bien pasen los 30 días de plazo.";
        } 
        echo $OUTPUT->confirm(
            $messageResponse,
            //continue=si=Realizar otro intento
            new single_button($actionurl, 'Realizar otro intento', 'get'),
            //cancelar=no=Finalizar curso
            new single_button($cancelurl, 'Finalizar curso', 'get')
        );
        
    }
    echo $OUTPUT->footer();
} catch (dml_exception $e) {
    echo sprintf("errorcode: %s, message: %s", $e->errorcode, $e->getMessage());
    print_r($e->debuginfo);
} catch (coding_exception $e) {
    echo sprintf("errorcode: %s, message: %s", $e->errorcode, $e->getMessage());
    print_r($e->debuginfo);
} catch (moodle_exception $e) {
    echo sprintf("errorcode: %s, message: %s", $e->errorcode, $e->getMessage());
    print_r($e->debuginfo);
}
