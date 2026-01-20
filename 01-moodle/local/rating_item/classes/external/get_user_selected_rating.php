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
 * External functions
 *
 * @package   local_report_completion
 * @copyright 2018 Osvaldo Arriola  <osvaldo@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rating_item\external;

use dml_exception;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;
use moodle_exception;
use local_rating_item\utils\rating_item_utils;
use context_course;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

class get_user_selected_rating extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function get_user_selected_rating_parameters()
    {
        /**
         * parametros que acepta el ws
         */
        return new external_function_parameters(
            [
                'userid' => new external_value(
                    PARAM_INT
                ),
            ]
        );
    }

    /**
     * @param int userid
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_user_selected_rating($userid = 1)
    {
        global $DB, $OUTPUT, $PAGE, $COURSE;
        require_login();
        $context = context_course::instance($COURSE->id);
        $PAGE->set_context($context);
        
        $return = array();
        $user = $DB->get_record('user', array('id' => $userid));
        $userimage = $OUTPUT->user_picture($user, array('size'=>50));

        try {
            $return[] = array(
                'image' => $userimage,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'userid' => $userid,
                'courseid' => $COURSE->id,
                
            );
        } catch (Exception $e) {
            throw new moodle_exception('errormsg', 'local_rating_item', '', $e->getMessage());
        }

        return $return;
    }

    /**
     * @return external_multiple_structure
     */
    public static function get_user_selected_rating_returns()
    {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'image' => new external_value(PARAM_RAW),
                    'firstname' => new external_value(PARAM_RAW),
                    'lastname' => new external_value(PARAM_RAW),
                    'email' => new external_value(PARAM_RAW),
                    'userid' => new external_value(PARAM_RAW),
                    'courseid' => new external_value(PARAM_RAW),
                ]
            )
        );
    }
}
