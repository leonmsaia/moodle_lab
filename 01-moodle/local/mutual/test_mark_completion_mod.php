<?php

include_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);


require_once($CFG->dirroot . '/lib/completionlib.php');
$course = get_course($courseid);

$info = new completion_info($course);
$activities = $info->get_activities();
foreach($activities as $cm) {
    if($cm->id == 1121) {
        $info->update_state($cm, COMPLETION_UNKNOWN, $userid);
    }
}
