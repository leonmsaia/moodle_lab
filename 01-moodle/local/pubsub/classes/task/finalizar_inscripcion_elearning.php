<?php

namespace local_pubsub\task;

use local_pubsub\back\inscripcion_elearning;

class finalizar_inscripcion_elearning extends \core\task\scheduled_task
{

    public $days;
    public $last_execute;

    public function __construct()
    {
        global $DB;
        $config = get_config('local_pubsub', 'days');
        $days = (int) $config;
        if (empty($days)) {
            $days = 30;
        }
        $this->days = $days;

        $lastruntime = $DB->get_record('task_scheduled', array('classname' => '\local_pubsub\task\finalizar_inscripcion_elearning'), 'lastruntime');
        $this->last_execute = $lastruntime->lastruntime;
    }

    public function get_name()
    {
        return get_string('task_end_training', 'local_pubsub');
        //return 'Finalizar capacitacion elearning';
    }

    public function execute()
    {        
        /* 
         *  Se llama funcion para los "******* CULMINADOS *************" 
         *  Participantes que tienen timecompleted distinto de NULL en la tabla course_completions
         */
        inscripcion_elearning::finalizar_elearning($this->last_execute,$this->days,'culminados');
    }
    
}
