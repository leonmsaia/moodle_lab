<?php

namespace local_eabcpanelcursos\task;

class send_message extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('send_message', 'local_eabcpanelcursos');
    }
 
    /**
     * Execute the task.
     */
    public function execute() {
        // Apply fungus cream.
        // Apply chainsaw.
        // Apply olive oil.
    }

}
 