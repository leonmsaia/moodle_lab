<?php

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
use local_pubsub\utils;

/**
 * Class local_eabccalendar_external
 */

class local_eabccalendar_external extends external_api {

     /**
     * Get parameter list.
     * @return external_function_parameters
     */
    public static function set_bloqueo_parameters() {
        return new external_function_parameters (
            array( 
                'motivo'    => new external_value(PARAM_RAW, 'motivo.',  VALUE_DEFAULT, 0), 
                'hora_desde'  => new external_value(PARAM_RAW, 'hora_desde.',  VALUE_DEFAULT, 0),
                'hora_hasta'  => new external_value(PARAM_RAW, 'hora_hasta.',  VALUE_DEFAULT, 0),
                'fecha'  => new external_value(PARAM_RAW, 'fecha.',  VALUE_DEFAULT, 0),
            ));
    }

    /**
     * Get list of courses with active sessions for today.
     * @param string $descripcion
     * @return array
     */
    public static function set_bloqueo($motivo, $hora_desde, $hora_hasta, $fecha) {
        global $USER, $DB;

        $estatus = true;
        $fecha_desde = strtotime($fecha. " ".$hora_desde.":00");
        $fecha_hasta = strtotime($fecha. " ".$hora_hasta.":00");

        $groups = $DB->get_records('groups_members', array('userid'=> $USER->id));
        foreach($groups as $group){
            $params['groupid']      = $group->groupid; 
            $params['fecha_desde']  = $fecha_desde;
            $params['fecha_hasta']  = $fecha_hasta;
            $sql = 'SELECT * from {eabcattendance_sessions} where groupid = :groupid AND sessdate >= :fecha_desde AND (sessdate+duration) <=:fecha_hasta ';
            $sessions = $DB->get_record_sql($sql, $params);

            if ($sessions){
                $estatus = false;
                break;
            }            
        }
         
        if ($estatus){

            $endpoint = get_config('local_pubsub', 'bloqueoagenda');

            if(empty($endpoint)) {
                throw new moodle_exception("Debe configurar el endpoint de registro de participantes");
            }
                        
            $facilitador = $DB->get_record('facilitador_back', array('id_user_moodle' => $USER->id));
            
            if (!$facilitador){
                $resp['data'] = 'No se encontró el GUID del facilitador en la tabla facilitador_back';
                $resp['status']  = 404;
                throw new moodle_exception($resp);
            }

            $timestart  = utils::date_to_timestamp($fecha. " ".$hora_desde.":00");
            $timesend   = utils::date_to_timestamp($fecha. " ".$hora_hasta.":00");

            $data = [
                'IdRelator'     => $facilitador->id_facilitador,
                'FechaInicio'   =>  date('Y-m-d H:i', $timestart),
                'FechaTermino'  => date('Y-m-d H:i', $timesend),
                'Razon'         => $motivo ,
                'Canal'         => 'Front LMS eABC'
            ];

            $response = \local_pubsub\metodos_comunes::request($endpoint, $data, 'post');
                
            if ($response["status"] > 299) {
                throw new moodle_exception($response);
            } else {
                $record = new stdClass();
                $record->motivo = $motivo;
                $record->hora_desde = $hora_desde;
                $record->hora_hasta = $hora_hasta;                
                $record->fecha = $fecha;                
                $record->userid = $USER->id;
                $DB->insert_record('eabccalendar_agenda_bloqueo', $record); 
            }

        }
        
        return array(
            "estatus" => $estatus
        );
                
    }

    public static function set_bloqueo_returns() {
        return new external_single_structure(
            array(
                'estatus' => new external_value(PARAM_RAW, 'estatus'),
            )
        );
    }


    /**
     * Web Services para obtener las fechas de bloqueo de agenda del facilitador
     */

    public static function get_bloqueo_parameters() {
        return new external_function_parameters (
            array( 
                'userid'    => new external_value(PARAM_RAW, 'id del facilitador.',  VALUE_DEFAULT, 0), 
            ));
    }

    public static function get_bloqueo($userid) {
        global $DB;    
        return $DB->get_records('eabccalendar_agenda_bloqueo', array('userid' => $userid));
    }

    public static function get_bloqueo_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'    => new external_value(PARAM_RAW, 'id'),
                    'motivo'    => new external_value(PARAM_RAW, 'motivo'),
                    'fecha'        => new external_value(PARAM_RAW, 'fecha'),
                    'hora_desde'      => new external_value(PARAM_RAW, 'hora_desde'),
                    'hora_hasta'         => new external_value(PARAM_RAW, 'hora_hasta'),
                    'userid'     => new external_value(PARAM_RAW, 'userid')
                )
            )
        );
    }


    /**
     * Web Services para eliminar un bloqueo de agenda del facilitador
     */

    public static function delete_bloqueo_parameters() {
        return new external_function_parameters (
            array( 
                'id'    => new external_value(PARAM_RAW, 'id del bloqueo a eliminar.',  VALUE_DEFAULT, 0), 
            ));
    }

    public static function delete_bloqueo($id) {
        global $DB;            
        return $DB->delete_records('eabccalendar_agenda_bloqueo', array('id' => $id));
    }

    public static function delete_bloqueo_returns() {
        return new external_value(PARAM_TEXT, 'Http code');
    }
}
