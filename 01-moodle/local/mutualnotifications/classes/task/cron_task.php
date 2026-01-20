<?php
namespace local_mutualnotifications\task;

/**
 * Description of cron_end_course
 *
 * @author Eimar Urbina
 */
class cron_task extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('crontask', 'local_mutualnotifications');
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/mutualnotifications/lib.php');
        //send_notification_advance();
        advance_from_start_course();
    }

}
