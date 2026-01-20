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
 * Plugin Encuesta Movilidad
 * @package   local_encuesta_movilidad
 * @author    Efrain Rodriguez <efrain@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_encuesta_movilidad;

class utils
{

  /**
     * Devuelve parametros adicionales .
     *
     * @return String
     * @param
     */
    public static function  get_aditionals_params()
    {
        global $USER, $DB;
        $sql = "SELECT u.username as usuariorut, d1.data as empresarut
                FROM mdl_user AS u
                JOIN mdl_user_info_data d1 ON d1.userid = u.id
                JOIN mdl_user_info_field f1 ON d1.fieldid = f1.id AND f1.shortname = 'empresarut'
                where u.id = " .$USER->id;
        $currentUser = $DB->get_record_sql($sql);
        $date = date_create();
        $currentTime = $date->getTimestamp();
        $aditionalParams = array(
               'usuariorut' => $currentUser->usuariorut,
               'empresarut' => $currentUser->empresarut,
               'currentTime' => $currentTime
        );
        return $aditionalParams;     
    }

}
