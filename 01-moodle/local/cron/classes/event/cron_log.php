<?php

namespace local_cron\event;

class cron_log extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    public function get_description() {
        return get_string('error_answer', 'local_cron');
    }

    public static function get_name() {
        return get_string('error_answer', 'local_cron');
    }

    public function get_url() {
        return new \moodle_url('/');
    }
}