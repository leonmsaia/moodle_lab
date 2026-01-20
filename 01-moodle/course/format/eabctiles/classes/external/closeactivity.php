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

use dml_exception;
use format_eabctiles\utils\eabctiles_utils;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use Exception;

use invalid_parameter_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die;
define('EABCTILES_CLOSE_GROUP', 1);
define('EABCTILES_ACTIVE_GROUP', 0);

require_once($CFG->libdir . '/externallib.php');

class closeactivity extends external_api
{

    public static function closeactivity_parameters()
    {        
        return new external_function_parameters(
            array(
                'groupid' => new external_value(
                    PARAM_INT
                ),
                'courseid' => new external_value(
                    PARAM_INT
                ),
            )
        );
    }

    /**
     * Adds session to attendance instance.
     *
     * @param int $groupid
     * @param $courseid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function closeactivity($groupid = 0, $courseid)
    {
        global $DB;
        /** @var external_api $api */
        $api = new class extends external_api {};
        $params = $api::validate_parameters(
            self::closeactivity_parameters(),
            array(
                'groupid' => $groupid,
                'courseid' => $courseid,
            )
        );

        $group = $DB->get_record("format_eabctiles_closegroup", array("groupid" => $params['groupid']));
        if (empty($group) || $group->status == EABCTILES_ACTIVE_GROUP) {
            // Servicio que reporta las notas y las asitencia al Front

            $response = \local_pubsub\metodos_comunes::close_sesion($groupid);
            //guardar evento de respuesta
             eabctiles_utils::eabctiles_response_cloasesession( \context_course::instance($courseid), $response, $groupid);
             if (!$response || $response > 299) {
                throw new Exception('Ocurrio un error, respuesta: '.$response);
            }

            if (empty($group)) {
                eabctiles_utils::insert_data_close($params['groupid'], $courseid);
                $newgroup = $DB->get_record("format_eabctiles_closegroup", array("groupid" => $params['groupid']));
                eabctiles_utils::change_status_group($newgroup->id, EABCTILES_CLOSE_GROUP, $courseid);
            } else {
                if ($group->status == EABCTILES_ACTIVE_GROUP) {
                    eabctiles_utils::change_status_group($group->id, EABCTILES_CLOSE_GROUP, $courseid);
                }
            }
        }

        if (!empty($group)) {
            if ($group->status == EABCTILES_CLOSE_GROUP) {
                eabctiles_utils::change_status_group($group->id, EABCTILES_ACTIVE_GROUP, $courseid);
            }
        }
        return array();
    }

    public static function closeactivity_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                array()
            )
        );
    }
}
