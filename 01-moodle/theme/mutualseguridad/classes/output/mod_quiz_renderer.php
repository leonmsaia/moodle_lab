<?php
namespace theme_mutualseguridad\output;
defined('MOODLE_INTERNAL') || die;

use quiz_attempt;

class mod_quiz_renderer extends \mod_quiz_renderer {

    /**
     * Generates the view page
     *
     * @param stdClass $course the course settings row from the database.
     * @param stdClass $quiz the quiz settings row from the database.
     * @param stdClass $cm the course_module settings row from the database.
     * @param context_module $context the quiz context.
     * @param view_page $viewobj
     * @return string HTML to display
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        global $USER, $DB;
        $output = '';
        
        $attempts = quiz_get_user_attempts((array) $quiz->id, $USER->id, 'finished', true);
        $lastfinishedattempt = end($attempts);

        $get_sends = $DB->get_record('format_eabctiles_send_course', ['userid' => $USER->id, 'courseid' => $course->id]);
        //si es curso elearning y no se a enviado
        if((\local_mutual\front\utils::is_course_elearning($course->id) == true) && (empty($get_sends))){
            $grade_item_course = \local_sendgrade\utils::get_grade_item($course->id, 'course');
            $grade_user_course = \local_sendgrade\utils::get_grade_user_course($course->id, $USER->id);
            $grade = $grade_user_course;

            if($grade == $grade_item_course->grademax){
                \local_sendgrade\utils::send_grade($USER->id, $course->id, $grade, $grade_item_course);
                $params = array(
                    'userid'    => $USER->id,
                    'course'  => $course->id
                );
                $ccompletion = new \completion_completion($params);
                $ccompletion->mark_complete();
            } else {
                //valido si es el ultimo intento o si no le quedan mas intentos 
                //en caso de no quedar mas intentos envio la nota mayor
                if($quiz->attempts == $lastfinishedattempt->attempt){
                    \local_sendgrade\utils::send_grade($USER->id, $course->id, $grade, $grade_item_course);
                } else {
                    $get_last_attemp = $DB->get_record('format_eabctiles_sendco_log', ['userid' => $USER->id, 'courseid' => $course->id, 'quiz_attempts_id' => $lastfinishedattempt->id]);
        
                    //en caso de que le quedan intentos restantes
                    //si la nota del usuario es mayor o igual a la nota para aprobar el curso le muestro la alerta
                    //en caso contrariono hago nada 
                    if(($grade  >= $grade_item_course->gradepass) && empty($get_last_attemp)){
                            redirect(new \moodle_url('/local/sendgrade/confirm_send.php', ['action' => "send", 'attempt' => $lastfinishedattempt->id, 'attempsquiz' => $quiz->attempts, 'attempsquizuser' => $lastfinishedattempt->attempt, 'cmid' => $quiz->cmid, 'courseid' => $course->id, 'quizid' => $quiz->id, 'sumgrade' => base64_encode($grade) ]));
                    } 
                }
            }
        }

        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);
        $output .= $this->render($viewobj->attemptslist);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }
}