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
 *
 * @package    local_eabcprogramas
 * @copyright  2020 e-abclearning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_eabcprogramas;

use context_course;
use DateTime;
use moodle_url;
use SplFileInfo;
use stdClass;

define('ACTIVO', 1);
define('INACTIVO', 0);
define('ENPREPARACION', 2);
define('VIGENTE', 1);
define('CADUCADO', 0);
/**
 * 
 */
class utils
{
    public static function activo()
    {
        return ACTIVO;
    }
    public static function inactivo()
    {
        return INACTIVO;
    }
    public static function enpreparacion()
    {
        return ENPREPARACION;
    }
    public static function vigente()
    {
        return VIGENTE;
    }
    /**
     * return string filters
     */

    public static function get_filters($filters)
    {
        $str = "";
        foreach ($filters as $key => $value) {
            $str .= "" . $value . ",";
        }
        $str = substr($str, 0, (strlen($str) - 1));
        return $str;
    }

    /**
     * return courses
     */

    public static function get_courses($array_cat)
    {
        global $DB, $CFG;
        include_once("$CFG->libdir/completionlib.php");
        $courselist = [];
        foreach ($array_cat as $key => $value) {
            $courses = $DB->get_records('course', ['category' => $value]);
            if (!$courses) {
                continue;
            }
            foreach ($courses as $key => $course) {
                $completion = new \completion_info($course);
                if ($completion->is_enabled()) {
                    if ($course->enddate && $course->visible) {
                        //if ($course->enddate > time()) {
                        $courselist[$course->id] = $course->fullname;
                        //}
                    }
                }
            }
        }
        return $courselist;
    }

    /** Permite obtener el arbol de ids de categorias válidas a partir de la categoría configurada
     * @global type $DB
     * @param type $categoryid
     * @return array
     */
    public static function get_list_valid_category($categoriesid)
    {
        global $DB;
        $valid_category_list = [];

        foreach ($categoriesid as $key => $categoryid) {
            $category = $DB->get_record('course_categories', ['id' => $categoryid]);
            if (empty($category)) {
                continue;
            }
            // if ($category->parent) {
            $valid_category_list[] = $category->id;
            // }
            $list_sub_cat = $DB->get_records('course_categories');
            if ($list_sub_cat) {
                foreach ($list_sub_cat as $cat) {
                    $str = $cat->path;
                    if (strlen(stristr($str, '/' . $categoryid . '/')) > 0) {
                        $valid_category_list[] = $cat->id;
                    }
                }
            }
        }

        return $valid_category_list;
    }


    public static function validate_dates($fromdate, $todate, $todate_old, $fromdate_old)
    {

        // If both start and end dates are set end date should be later than the start date.
        if (!empty($fromdate) && !empty($todate) && ($todate < $fromdate)) {
            return 'todatebeforebeforefromdate';
        }

        // if ($fromdate < time() || $todate < time()) {
        //     return 'beforetoday';
        // }

        if ($todate_old > 0) {
            if (($fromdate > $fromdate_old && $fromdate < $todate_old) || ($todate > $fromdate_old && $todate < $todate_old)) {
                return 'solapan';
            }
        }

        return false;
    }
    /**
     * Observer
     * Permite verificar si un usuario está en todos 
     * los cursos de un programa, entonces se agrega a la tabla local_eabcprogramas_usuarios
     * @param type $event
     */
    public static function enrol_observer($event)
    {
        /**
        global $DB;
        $data = $event->get_data(); //relateduserid, courseid
        $userenrolid = $data['relateduserid'];
        $user = $DB->get_record('user', ['id' => $userenrolid]);
        $programs = $DB->get_records('local_eabcprogramas', ['status' => 1]);
        if ($programs) {
            foreach ($programs as $key => $program) {
                $exist_program_user = $DB->get_record(
                    'local_eabcprogramas_usuarios',
                    ['userid' => $userenrolid, 'programid' => $program->id]
                );
                if ($exist_program_user) {
                    continue;
                } else {
                    $cursos = $program->cursos;
                    $cursos = explode(',', $cursos);
                    $flag = false;
                    foreach ($cursos as $key => $id) {
                        $context = context_course::instance($id);
                        if (is_enrolled($context, $user))
                            $flag = true;
                        else {
                            $flag = false;
                            break;
                        }
                    }
                    if ($flag) {
                        $program_user = new stdClass();
                        $program_user->userid = $userenrolid;
                        $program_user->programid = $program->id;
                        $program_user->status = 0; //Programa finalizado
                        $program_user->cursos = $program->cursos;
                        $program_user->timecreated = time();
                        $program_user->timemodified = time();

                        $DB->insert_record('local_eabcprogramas_usuarios', $program_user);
                    }
                }
            }
        }
         */
    }
    /**
     * Observer
     * Permite verificar si un usuario está en todos 
     * los cursos de un programa, entonces se dshabilita de la tabla local_eabcprogramas_usuarios
     * @param type $event
     */
    public static function unenrol_observer($event)
    {
        /**
        global $DB;
        $data = $event->get_data(); //relateduserid, courseid
        $userenrolid = $data['relateduserid'];
        $programs = $DB->get_records('local_eabcprogramas', ['status' => 1]);
        if ($programs) {
            foreach ($programs as $key => $program) {
                $program_user = $DB->get_record('local_eabcprogramas_usuarios', ['userid' => $userenrolid, 'programid' => $program->id]);
                if ($program_user) {
                    $DB->delete_records('local_eabcprogramas_usuarios', ['id' => $program_user->id]);
                }
            }
        }
         */
    }

    /**
     * 
     */
    public static function struuid($entropy)
    {
        $s = uniqid("", $entropy);
        $num = hexdec(str_replace(".", "", (string) $s));
        $index = '123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $base = strlen($index);
        $out = '';
        for ($t = floor(log10($num) / log10($base)); $t >= 0; $t--) {
            $a = floor($num / pow($base, $t));
            $out = $out . substr($index, $a, 1);
            $num = $num - ($a * pow($base, $t));
        }
        return $out;
    }

    /**
     * 
     */
    public static function agregar_programa_usuario($userid, $program, $endfirstcourse, $cursos)
    {
        global $DB;

        $programa = $DB->get_record('local_eabcprogramas_usuarios', ['userid' => $userid, 'programid' => $program->id]);
        if ($programa) {
            return;
        }

        $program_user = new stdClass();
        $program_user->userid = $userid;
        $program_user->programid = $program->id;
        $program_user->status = utils::vigente();

        $strcursos = trim($cursos, ':');

        $program_user->cursos = $strcursos;

        $program_user->timecreated = time();
        $program_user->timemodified = time();
        $program_user->codigo = self::struuid(false);
        $program_user->codigo_programa = $program->codigo;

        $program_user->description = $program->description;
        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user) {
            $program_user->usuario = fullname($user);
            $program_user->rut_usuario = utils::get_data_user_field_text('participantedocumento', $userid);
        }
        $program_user->empresa = utils::get_data_user_field_text('empresarazonsocial', $userid);
        $program_user->rut_empresa = utils::get_data_user_field_text('empresarut', $userid);
        $program_user->end_firstcourse = $endfirstcourse;
        $program_user->fecha_otorgamiento = time();
        $date = (new DateTime())->setTimestamp(usergetmidnight($endfirstcourse));
        $date = $date->modify($program->caducidad . ' month'); //endfirstcourse + program->caducidad
        $program_user->fecha_vencimiento = $date->getTimestamp();

        $diploma =  $DB->get_records('local_diplomas', ['status' => 1]);
        if ($diploma)
            $program_user->diplomaid = end($diploma)->id;
        else
            return;
        $program_user->codigo_diploma = self::struuid(false);

        $certificado = $DB->get_records('local_certificados', ['status' => 1]);
        if ($certificado)
            $program_user->certificadoid = end($certificado)->id;
        else
            return;
        $program_user->codigo_certificado = self::struuid(false);
        $program_user->horas = $program->horas;
        $program_user->caducidad = $program->caducidad;
        $program_user->adherente = utils::get_data_user_field_text('empresacontrato', $userid);
        echo "<br><spam style='color:green'>Otorgando programa (" . $program->description . ") para usuario_id = " . $userid . "</spam><br>";

        $DB->insert_record('local_eabcprogramas_usuarios', $program_user);
    }

    /**
     * Returns field values
     * @param int $field
     */
    public static function get_data_user_field_text($field, $userid)
    {
        global $DB;
        $sql = "SELECT d1.data AS 'value'
                FROM {user} u
                JOIN {user_info_data} d1 ON d1.userid = u.id
                JOIN {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = :field
                WHERE u.id = :userid";

        $field = $DB->get_record_sql($sql, ['field' => $field, 'userid' => $userid]);
        if ($field) {
            return $field->value;
        }
        return '';
    }

    public static function get_courses_by_ids($ids)
    {
        global $DB;
        $ids = self::get_filters($ids);
        $sql = "SELECT *
                FROM {course} c
                WHERE c.id IN ($ids)";

        $courses = $DB->get_records_sql($sql);
        if ($courses) {
            $list = [];
            foreach ($courses as $id => $course) {
                if ($course->startdate) {
                    $course->startdate = date('d-m-Y', $course->startdate);
                }
                if ($course->enddate) {
                    $course->enddate = date('d-m-Y', $course->enddate);
                }
                $list[] = $course;
            }
            return $list;
        }
        return [];
    }

    public static function get_file_by_itemid($itemid)
    {
        global $DB, $CFG;
        $sql = "SELECT *
                FROM {files} f                
                WHERE f.itemid = :itemid
                AND f.filesize > 0";

        $record = $DB->get_record_sql($sql, ['itemid' => $itemid]);
        if ($record) {
            $fileurl = \moodle_url::make_pluginfile_url(
                $record->contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename
            );
            // $fileurl = \moodle_url::make_draftfile_url( //Draft file se usa para usuarios logueados
            //     $record->itemid,
            //     $record->filepath,
            //     $record->filename
            // );
            return $fileurl->out();
        }
        return '';
    }

    public static function courses_names($ids)
    {
        global $DB;
        $str = '';
        foreach ($ids as $key => $id) {
            $course = $DB->get_record('course', ['id' => $id]);
            if ($course) {
                $str = $str . $course->fullname . ', ';
            }
        }
        $str = substr($str, 0, (strlen($str) - 2));
        return $str;
    }

    /**
     * Calcular nota
     */
    public static function calc_nota($nota, $gradepass)
    {
        if ($nota < $gradepass) {
            return ((($nota - 1) * 3) / 74) + 1;
        } else {
            return (($nota - $gradepass) * 0.12) + 4;
        }
    }

    /**
     * Obtine los holdings asociados a un usuario
     */
    public static function get_holdings_user($userid)
    {
        global $DB;
        $sql = "SELECT holdingid
            FROM {holding_users} 
            WHERE userid =:userid
        ";
        $result = $DB->get_records_sql($sql, ['userid' => $userid]);
        if ($result) {
            $str = "(";
            foreach ($result as $key => $value) {
                $str .= "" . $value->holdingid . ",";
            }
            $str = substr($str, 0, (strlen($str) - 1));
            $str .= ")";
            return $str;
        }
        return '';
    }

    /**
     * Obtiene las compañias asociadas a los holdings asociados a un usuario
     */
    public static function get_companies($userid, $holdings)
    {
        global $DB;
        $sql = "SELECT comp.name AS compname
        from {company} as comp
        LEFT JOIN {holding_companies} hc ON hc.companyid = comp.id
        LEFT JOIN {holding} h ON h.id = hc.holdingid
        LEFT JOIN {holding_users} hu ON hu.userid =:userid
        WHERE h.id in $holdings
        group by comp.name
        ";

        $result = $DB->get_records_sql($sql, ['userid' => $userid]);
        if ($result) {
            $str = "(";
            foreach ($result as $key => $value) {
                $str .= "'" . $value->compname . "',";
            }
            $str = substr($str, 0, (strlen($str) - 1));
            $str .= ")";
            return $str;
        }
        return '';
    }

    public static function activity_completed($courseid, $userid, $activity)
    {
        global $DB;
        $sql = " SELECT cmc.completionstate AS completionstate
        FROM {course_modules_completion} AS cmc
        LEFT JOIN {course_modules} AS cm ON cm.id = cmc.coursemoduleid
        LEFT JOIN {modules} AS m ON m.id = cm.module
        LEFT JOIN {course} AS c ON c.id = cm.course
        LEFT JOIN {user} AS u ON u.id = cmc.userid
        WHERE 1
        AND c.id = :courseid
        AND u.id = :userid
        AND m.name = :activity";
        $response = $DB->get_record_sql($sql, ['courseid' => $courseid, 'userid' => $userid, 'activity' => $activity]);
        if ($response) {
            return (int)$response->completionstate;
        }
        return 0;
    }
}
