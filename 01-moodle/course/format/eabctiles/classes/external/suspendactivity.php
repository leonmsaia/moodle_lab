<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web service courses definition
 * @package   mod_eabcattendance
 * @author    José Salgado <jose@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_eabctiles\external;

use format_eabctiles\utils\eabctiles_utils;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use dml_exception;
use Exception;

use stdClass;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

class suspendactivity extends external_api {

    
    public static function suspendactivity_parameters() {
        return new external_function_parameters(
            array(
                'groupid' => new external_value(
                    PARAM_INT
                ),
                'courseid' => new external_value(
                    PARAM_INT
                ),
                
                'motivo' => new external_value(
                    PARAM_RAW
                ),
                
                'textother' => new external_value(
                    PARAM_RAW
                ),
            )
        );
    }
    
    /**
     * Adds session to attendance instance.
     *
     * @param int $attendanceid
     * @param string $description
     * @param int $sessiontime
     * @param int $duration
     * @param int $groupid
     * @param bool $addcalendarevent
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function suspendactivity($groupid, $courseid, $motivo = '', $textother = '') {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/lib/grouplib.php');
        
        /** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
            self::suspendactivity_parameters(), 
                array(
                    'groupid' => $groupid,
                    'courseid' => $courseid,
                    'motivo' => $motivo,
                    'textother' => $textother,
                )
            );


        try {
            $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
            $group = groups_get_group($groupid, '*', IGNORE_MISSING);

            groups_delete_group($group);
            eabctiles_utils::create_event_suspend_group($groupid, $courseid, $motivo, $textother);
            eabctiles_utils::save_suspend($groupid, $courseid, $motivo, $textother);
            $response = \local_pubsub\metodos_comunes::suspend_sesion($groupid, $motivo);
            //guardar evento de respuesta
            eabctiles_utils::eabctiles_response_suspendsession( \context_course::instance($courseid), $response, $groupid, $motivo);
            if(!($response)){
                if($response > 299){
                    throw new Exception('Hubo un error al comunicarse con el Back, codigo de error: '.$response);
                }
            }

            $transaction->allow_commit();
        }catch (Exception $exception){
            $transaction->rollback($exception);
        }

            

        return array();
    }
    
    public static function suspendactivity_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                )
            )
        );
    }

}
