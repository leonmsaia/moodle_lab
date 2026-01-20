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

namespace mod_eabcattendance\external;

use Exception;
use mod_eabcattendance\utils\frontutils;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use core_user;
use stdClass;
use moodle_exception;

defined('MOODLE_INTERNAL') || die;


class register_users extends external_api {

    
    public static function register_users_parameters() {
        return new external_function_parameters(
            array(
                'registerusers' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'groupid' => new external_value(PARAM_RAW, 'Nombre del curso'),
                            'createuser' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'username' => new external_value(core_user::get_property_type('username'), 'Nombre de usuario'),
                                        'firstname' => new external_value(core_user::get_property_type('firstname'), 'Nombre del usuario'),
                                        'lastmame' => new external_value(core_user::get_property_type('lastname'), 'Apellido del usuario'),
                                        'email' => new external_value(core_user::get_property_type('email'), 'Correo del usuario'),
                                        'sexo' => new external_value(PARAM_RAW, 'sexo del usuario', VALUE_OPTIONAL),
                                        'rut_empresa' => new external_value(PARAM_RAW, 'Rut de la empresa', VALUE_OPTIONAL),
                                        'fecha_nacimiento' => new external_value(PARAM_RAW, 'Fecha de nacimiento en timestamp', VALUE_OPTIONAL),
                                        'roles' => new external_value(PARAM_RAW, 'Roles', VALUE_OPTIONAL),
                                        'rut' => new external_value(PARAM_RAW, 'Rut del usuario', VALUE_OPTIONAL),
                                    )
                                ),
                                'Crear usuarios'
                            ),
                        )
                    )
                )
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
     */
    public static function register_users($registerusers) {
        global $DB;
        $params = self::validate_parameters(self::register_users_parameters(), array('registerusers' => $registerusers));

        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
        //validar si el curso existe
        foreach ($params['registerusers'] as $registerusers) {
            try {
                //crear usuario y matricularlo en el curso
                foreach($registerusers["createuser"] as $createuser){
                    $get_group = $DB->get_record("groups", array("id" => $registerusers["groupid"]));
                    $course = new stdClass();
                    $course->id = $get_group->courseid;
                    $newuserid = frontutils::create_user($createuser, $course);
                    frontutils::enrol_user($course, $newuserid, $registerusers["groupid"], 5);
                }
                
            } catch (\Exception $e) {
                throw new moodle_exception('errormsg', 'mod_eabcattendance', '', $e->getMessage());
            }
        }
        $transaction->allow_commit();
        return array();
    }
    
    public static function register_users_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                )
            )
        );
    }

}
