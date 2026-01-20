<?php

namespace local_mutualnotifications\task;

/**
 * Permite crear una tarea programada que permita enviar una notificación por correo de inicio de curso
 *
 * @author Eimar Urbina
 */
class cron_start_course extends \core\task\scheduled_task {

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/local/mutualnotifications/lib.php');
        notification_start_course();
    }

    public function get_name() {
        return get_string('cron_start_course', 'local_mutualnotifications');
    }

}
