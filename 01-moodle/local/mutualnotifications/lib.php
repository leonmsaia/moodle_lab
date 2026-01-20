<?php

/*
 * e-ABC
 * Notificaciones
 *
 */

define('ZERO', '0');
define('FIFTY', '50');
define('SEVENTY_FIVE', '75');
define('HUNDRED', '100');

/**
 * Permite enviar a los usuarios una notificación por correo inicio de curso
 */
function notification_start_course() {
    global $DB, $USER, $PAGE;

    $courses = get_courses();
    $current_date = date_create();
    $today = date_timestamp_get($current_date);

    try {
        $transaction = $DB->start_delegated_transaction();
        foreach ($courses as $course) {
            if ($course->id == 1) {
                continue;
            }
            $started_active = get_config('local_mutualnotifications', 'startcourse' . $course->id);
            if ($started_active) {
                $users = get_enrolled_users(\context_course::instance($course->id));
                foreach ($users as $user) {
                    $startdate = $course->startdate;
                    $currentdate = date_create();
                    $today = date_timestamp_get($currentdate);

                    $start_date = date("d-m-Y", $startdate);
                    $current_date = date("d-m-Y", $today);

                    $time1 = strtotime($start_date);
                    $time2 = strtotime($current_date);
                    if ($time1 == $time2) {
                        $log_notification = $DB->get_record('mutual_log_notifications', array('notification' => 'startcourse' . $course->id, 'userid' => $user->id, 'courseid' => $course->id));
                        if ($log_notification || !empty($log_notification))
                            continue;
                        else {
                            $message_lang = new \stdClass();
                            $message_lang->user = $user->firstname;
                            $message_lang->course = $course->fullname;

                            $PAGE->set_context(\context_system::instance());
                            $message = new \core\message\message();
                            $message->component = 'local_mutualnotifications';
                            $message->name = 'posts';
                            $message->userfrom = $USER;
                            $message->userto = $user;
                            $message->fullmessageformat = FORMAT_PLAIN;
                            $message->courseid = $course->id;
                            $messagehtml = get_string('messagehtmlstartcourse', 'local_mutualnotifications', $message_lang);
                            $subject = get_string('subjectstartcourse', 'local_mutualnotifications', $message_lang);
                            $message->subject = $subject;
                            $message->fullmessagehtml = $messagehtml;

                            $messageid = \message_send($message);
                            if ($messageid) {
                                $notification = new \stdClass();
                                $notification->notification = 'startcourse' . $course->id;
                                $notification->userid = $user->id;
                                $notification->courseid = $course->id;
                                $notification->timemodified = $today;
                                $DB->insert_record('mutual_log_notifications', $notification);
                            }                             
                        }
                    }
                }
            }
        }
        $transaction->allow_commit();
    } catch (Exception $e) {
        error_log($e);
        $transaction->rollback($e);
    }
}

/**
 * Permite enviar a los usuarios una notificación por correo de avance de curso desde la fecha de matriculación
 */
function advance_from_enrolment() {
    global $DB, $USER, $PAGE;


    $courses = get_courses();
    $time = new DateTime("now", core_date::get_user_timezone_object());
    $today = $time->getTimestamp();

    //try {
    //    $transaction = $DB->start_delegated_transaction();

        foreach ($courses as $course) {

            if ($course->id == 1) {
                continue;
            }
            if (\local_mutual\front\utils::is_course_elearning($course->id)) {
                $util = new \local_mutualnotifications\utils();
                $percent_fifty_str = 'fiftypercentfromenrolment';
                $util->course_advance_from_enrole($course, $USER, $percent_fifty_str, FIFTY, SEVENTY_FIVE, $PAGE, $today, $DB);
                $percent_seventy_five_str = 'seventyfivepercentfromenrolment';
                $util->course_advance_from_enrole($course, $USER, $percent_seventy_five_str, SEVENTY_FIVE, HUNDRED, $PAGE, $today, $DB);
            }
        }
    //    $transaction->allow_commit();
    //} catch (Exception $e) {
    //    error_log($e);
    //    $transaction->rollback($e);
    //}
}

/**
 * Permite enviar a los usuarios una notificación por correo de avance de curso desde la fecha de inicio
 */
function advance_from_start_course() {
    global $DB, $USER, $PAGE;


    $courses = get_courses();
    $current_date = date_create();
    $today = date_timestamp_get($current_date);

    try {
        $transaction = $DB->start_delegated_transaction();

        foreach ($courses as $course) {
            if ($course->id == 1) {
                continue;
            }
            $util = new \local_mutualnotifications\utils();
            $percent_fifty_str = 'fiftypercent';
            $util->course_advance_from_start($course, $USER, $percent_fifty_str, FIFTY, SEVENTY_FIVE, $PAGE, $today, $DB);
            $percent_seventy_five_str = 'seventyfivepercent';
            $util->course_advance_from_start($course, $USER, $percent_seventy_five_str, SEVENTY_FIVE, HUNDRED, $PAGE, $today, $DB);
        }
        $transaction->allow_commit();
    } catch (Exception $e) {
        error_log($e);
        $transaction->rollback($e);
    }
}

