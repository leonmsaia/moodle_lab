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
use moodle_exception;

defined('MOODLE_INTERNAL') || die;


class create_schedules extends external_api {

    /**
     * Describes the parameters for .
     *
     * @return external_function_parameters
     */
    public static function create_schedules_parameters() {
        return new external_function_parameters(
            array(
                'createschedules' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'coursename' => new external_value(PARAM_RAW, 'Nombre del curso'),
                            'creategroup' => new external_value(PARAM_RAW, 'Nombre del quiz'),
                            'createsession' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'sessdate' => new external_value(PARAM_RAW, 'Fecha de inicio de la sesión en timestamp'),
                                        'duration' => new external_value(PARAM_RAW, 'Duración en segundos'),
                                        'description' => new external_value(PARAM_RAW, 'Descripción de la sesión', VALUE_OPTIONAL),
                                        'calendarevent' => new external_value(PARAM_INT, 'Agregar al calendario 1 si ó 0 ', VALUE_OPTIONAL),
                                        'guid' => new external_value(PARAM_RAW, 'Guid de la sesión', VALUE_OPTIONAL,'')
                                    )
                                ),
                                'Crear sessiones de assistencia'
                            ),
                            'createuser' => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'username' => new external_value(core_user::get_property_type('username'), 'Nombre de usuario'),
                                        'firstname' => new external_value(core_user::get_property_type('firstname'), 'Nombre del usuario'),
                                        'lastmame' => new external_value(core_user::get_property_type('lastname'), 'Apellido del usuario'),
                                        'email' => new external_value(core_user::get_property_type('email'), 'Correo del usuario'),
                                        'sexo' => new external_value(PARAM_RAW, 'sexo del usuario', VALUE_OPTIONAL),
                                        'nombre_adherente' => new external_value(PARAM_RAW, 'Nombre del adherente', VALUE_OPTIONAL),
                                        'rut_adherente' => new external_value(PARAM_RAW, 'Rut del adherente', VALUE_OPTIONAL),
                                        'fecha_nacimiento' => new external_value(PARAM_RAW, 'Fecha de nacimiento en timestamp', VALUE_OPTIONAL),
                                        'roles' => new external_value(PARAM_RAW, 'Roles', VALUE_OPTIONAL),
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
    public static function create_schedules($createschedules) {
        global $DB;
        $params = self::validate_parameters(self::create_schedules_parameters(), array('createschedules' => $createschedules));

        $transaction = $DB->start_delegated_transaction(); // Rollback all enrolment if an error occurs
        
        //validar si el curso existe
        foreach ($params['createschedules'] as $createschedule) {
            
            try {
                //crear curso
                $course = frontutils::create_course_front($createschedule);
                //crear actividad
                $attendance = frontutils::create_attendance($course);
                //crear grupo
                $gid = frontutils::create_group($createschedule, $course);
                //crear sesion
                frontutils::create_session($attendance, $course, $createschedule, $gid);
                //crear usuario y matricularlo en el curso
                frontutils::create_user_and_enrol($createschedule, $course, $gid);
            } catch (Exception $e) {
                throw new moodle_exception('errormsg', 'mod_eabcattendance', '', $e->getMessage());
            }
        }
        
        $transaction->allow_commit();
        return array();
    }
    /**
     * Describes add_session return values.
     *
     * @return external_multiple_structure
     */
    public static function create_schedules_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                )
            )
        );
    }

}
