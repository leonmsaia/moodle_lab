<?php
namespace local_mutualnotifications\task;

/**
 * Description of cron_course_completed
 *
 * @author Eimar Urbina
 */
class cron_course_completed extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('cron_course_completed', 'local_mutualnotifications');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/mutualnotifications/classes/utils.php');
        $last_run_time = self::get_last_run_time();
        $utils = new \local_mutualnotifications\utils();
        $utils::coursecompleted($last_run_time);
    }

}
