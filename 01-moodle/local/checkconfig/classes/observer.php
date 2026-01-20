<?php

namespace local_checkconfig;

class observer
{
    public static function course_viewed(\core\event\course_viewed $event)
    {
        /** @var \moodle_page $PAGE */
        /** @var \mysqli_native_moodle_database $DB */
        global $PAGE, $DB, $USER;
        $eventdata = $event->get_data();
        $courseid = $eventdata["courseid"];
        $active = get_config('local_checkconfig', 'activechecks');
        $allowexclude = get_config('local_checkconfig', 'activeexclude');
        if (empty($active)) {
            return;
        }
        if ((int)$courseid > SITEID) {
            $course = get_course($courseid);
            if (!empty($allowexclude)) {
                $excluded = $DB->record_exists('local_checkconfig', ['userid' => $USER->id, 'courseid' => $course->id]);
            } else {
                $excluded = false;
            }
            $checks = \local_checkconfig\utils::check_config($course);

            if (count($checks) > 0 && !$excluded) {
                $PAGE->requires->js_call_amd('local_checkconfig/main', 'init', [
                    "params" => [
                        'checks' => $checks,
                        'courseid' => $course->id,
                        'allowexclude' => $allowexclude
                    ]
                ]);
            }
        }
    }
}
