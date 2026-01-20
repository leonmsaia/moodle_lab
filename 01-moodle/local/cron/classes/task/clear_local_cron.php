<?php

namespace local_cron\task;

require_once($CFG->dirroot . '/local/cron/lib.php');
class clear_local_cron extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('clear_local_cron', 'local_cron');
    }


    public function execute() {
        global $DB, $CFG, $PAGE;
        mtrace(get_string('clear_local_cron', 'local_cron'));
        $now = time();
        mtrace(date('m/d/Y H:i:s', $now));

        try {
            $context = \context_system::instance();
            $sql = 'SELECT * FROM {mutual_log_local_cron} AS lc 
            WHERE ( lc.status = "Aprobado" AND lc.gradeuser < 75) 
            OR ( lc.status = "Reprobado" AND lc.gradeuser > 74) 
            OR ( lc.status = "Reprobado por inasistencia" AND lc.gradeuser > 74)';
            $get_datas = $DB->get_records_sql($sql);
            foreach ($get_datas as $key => $get_data) {
                $DB->delete_records("mutual_log_local_cron", array('id' => $get_data->id));
                echo "<br>Usuario con id " . $get_data->userid . " registrado en el curso " . $get_data->courseid . " con estatus " . $get_data->status . " y nota " . $get_data->gradeuser . " fue borrado del registro";
                $data = array('userid' => $get_data->userid, 'courseid' => $get_data->courseid);
                //guardo evento
                $event = \local_cron\event\local_cron_clear_log::create(
                        array(
                            'context' => $context,
                            'other' => (array)$get_data,
                        )
                );
                $event->trigger();
            }
        } catch (Exception $e) {
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
}
