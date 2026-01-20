<?php

namespace local_pubsub\output;

class before_standard_top_of_body_html_generation {

    public function execute() {
        global $COURSE, $USER, $PAGE;
        
        if (\context_course::instance($COURSE->id)) {
            $url = $PAGE->url;
            $search = 'course/view';
            
            if (strpos($url, $search)) {
                if (\local_mutual\front\utils::is_course_elearning($COURSE->id)) {
                    $days = \local_eabcourses\utils::get_remainingdays_user_course($USER, $COURSE);
                    if ($days == 'Vencido') {
                        \core\notification::warning('Días restantes: ' . $days);
                    } else {
                        \core\notification::info('Días restantes: ' . $days);
                    }
                }
            }
        }
    }
}
