<?php

namespace local_eabcourses;

class utils {
    public static function get_user_course_enroldate($user, $course) {
        /** @var \mysqli_native_moodle_database $DB */
        global $DB;
        $instances = enrol_get_instances($course->id, true);     
        foreach($instances as $instance) {
            $user_enrollment = $DB->get_record('user_enrolments', array('userid' => $user->id, 'enrolid' => $instance->id), 'timecreated, timestart');
            if(!empty($user_enrollment)) {
                return (!empty($user_enrollment->timestart)) ? (int)$user_enrollment->timestart : (int)$user_enrollment->timecreated; 
            }
        }
    }

    public static function get_remainingdays_user_course($user, $course){
        
        $limit = get_config('local_cron', 'days');
        if(empty($limit)) {
            $limit = 30; 
        }
        $time = new \DateTime("now", \core_date::get_user_timezone_object());
        $enrolldate = self::get_user_course_enroldate($user, $course);
        $nowtimestamp = $time->getTimestamp();
        $limittime = $enrolldate + $limit * 60 * 60 * 24;
        $timesend =  ($limittime >= $nowtimestamp) ? date('Y-m-d', $limittime) : '';
        $dEnd  = new \DateTime($timesend);
        $dDiff = $time->diff($dEnd);
        
        //convierto la fecha para buscar el dia, mes y año
        $finaldateStr = userdate($limittime, '%Y-%m-%d');
        //despues de encontrear la fecha la fecha le agrego 23:59:59 
        //para que sea el final del dia y lo convierto a timestamp
        $limittime = strtotime($finaldateStr . 'T23:59:59z');

        $remainingdays = ($limittime >= $nowtimestamp) ? $dDiff->days . ' Días' : 'Vencido';

        return $remainingdays;

    }

    public static function get_remainingdays_presencial_streamning($userid, $courseid){
        global $DB;

        $results_groups = groups_get_all_groups($courseid, $userid);
        $remainingdays = 'Vencido';

        if(!empty($results_groups)){
            foreach($results_groups as $results_group){
                $grupo = $DB->get_record('format_eabctiles_closegroup', array('groupid'=>$results_group->id));
                if((empty($grupo)) || ($grupo->status == 0)) {
                    $remainingdays = 'Disponible';
                    break;
                }
            }
        }
        return $remainingdays;
    }
}
