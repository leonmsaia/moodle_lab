<?php

namespace local_cron\task;

class finalizarCapacitacionElearngingClearTable extends \core\task\scheduled_task
{

    public function __construct()
    {
    }

    public function get_name()
    {
        return get_string('clean_table_log', 'local_cron');
    }

    public function execute()
    {
        raise_memory_limit(MEMORY_EXTRA);
        \local_cron\utils::clean_cron_table();
    }
}
