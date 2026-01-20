<?php

namespace local_cron\task;

use mysqli_native_moodle_database;

require_once($CFG->dirroot . '/local/cron/lib.php');
class mark_reaggregate_completion extends \core\task\scheduled_task {

    public $datefrom;
    public $dateto;

    public function __construct() {
        $this->datefrom = get_config('local_cron', 'reaggregate_date_from');

        $dateto = get_config('local_cron', 'reaggregate_date_to');
        
        if(empty($dateto)){
            $this->dateto = time();
        } else {
            $this->dateto = $dateto;
        }
    }

    public function get_name() {
        return get_string('mark_reaggregate', 'local_cron');
    }


    public function execute() {
        /** @var mysqli_native_moodle_database $DB */
        global $DB;
        mtrace(get_string('mark_reaggregate', 'local_cron'));
        $now = time();
        mtrace(date('m/d/Y H:i:s', $now));
        $wherein_course = '';
        $wherein_user = '';
        try {
            $context = \context_system::instance();
            if(!empty($this->datefrom)){
                $courses = get_config('local_cron', 'courses');
                if(!empty($courses)){
                    $wherein_course = ' and cc.course IN (' . $courses . ')';
                }

                $users = get_config('local_cron', 'users');
                if(!empty($users)){
                    $wherein_user = ' and cc.userid IN (' . $users . ')';
                }
                $sql = 'SELECT * FROM {course_completions} cc WHERE cc.timecompleted IS NULL AND cc.reaggregate = 0 AND (cc.timeenrolled >= ' . $this->datefrom . ' or cc.timeenrolled = 0) AND cc.timeenrolled <= ' . $this->dateto . $wherein_course . $wherein_user;
                $coursecompletions = $DB->get_records_sql($sql);
                //a la hora actual le quitamos 3 horas y se lo seteamos a los reaggregate de coursecompleted
                //que cumplen las condiciones
                $nowmenosthreehours = $now - (3 * 60 * 60);
                foreach ($coursecompletions as $key => $coursecompletion) {
                    //actualizamos los que cumplen con las condiciones
                    $update = "UPDATE {course_completions} as cc SET cc.reaggregate =  " 
                       .$nowmenosthreehours. " WHERE cc.id = ". $coursecompletion->id;
                    $DB->execute($update);
                    echo "<br>El usuario con id " . $coursecompletion->userid . " en el curso ".
                    $coursecompletion->course . " fue actualizado el reaggregate a " . $nowmenosthreehours . "<br>";
                    //guardo evento
                    $data = array(
                        "timeenrolled" => $coursecompletion->timeenrolled,
                        "userid" => $coursecompletion->userid,
                        "course" => $coursecompletion->course,
                        "reaggregate" => $nowmenosthreehours,
                    );
                    $event = \local_cron\event\mark_reaggregate_completion::create(
                            array(
                                'context' => $context,
                                'other' => $data,
                            )
                    );
                    $event->trigger();
                }
            }
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
}
