<?php

namespace local_checkconfig;

use stdClass;

class utils
{
    /**
     * Metodo que devuelve las verificaciones de configuracion de un curso
     * @param stdClass $course
     * @return array
     */
    public static function check_config($course)
    {
        if (!is_object($course)) {
            $course = get_course($course);
        }

        $ret = [];
        $enddate = self::check_enddate($course);
        if ($enddate !== []) {
            $ret[] = $enddate;
        }

        $enddatethreshold = self::check_enddatethreshold($course);
        if ($enddatethreshold !== []) {
            $ret[] = $enddatethreshold;
        }

        $completionenabled = self::check_completionenabled($course);
        if ($completionenabled !== []) {
            $ret[] = $completionenabled;
        }

        $criteriaenabled = self::check_criterias($course);
        if ($criteriaenabled !== [] && $completionenabled == []) {
            $ret[] = $criteriaenabled;
        }

        $gradepass = self::check_gradepass($course);
        if ($gradepass !== []) {
            $ret[] = $gradepass;
        }

        $endedcourse = self::check_endedcourse($course);
        if ($endedcourse !== []) {
            $ret[] = $endedcourse;
        }

        $alertthreshold = self::check_endedcoursethreshold($course);
        if ($alertthreshold !== []) {
            $ret[] = $alertthreshold;
        }

        return $ret;
    }

    /**
     * Metodo que verifica si el curso tiene configurada la finalizacion del curso
     * @param stdClass $course
     * @return array
     */
    public static function check_enddate($course)
    {
        global $CFG;
        $active = get_config('local_checkconfig', 'checkenddate');
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!empty($active) && empty($course->enddate) && $hascapability) {
            return [
                'message' => get_string('enddatecourse', 'local_checkconfig'),
                'url' => sprintf("%s/course/edit.php?id=%d", $CFG->wwwroot, $course->id)
            ];
        }
        return [];
    }

    /**
     * metodo que verifica si el curso tiene configurada fecha de finalizacion en el umbral configurado
     * @param stdClass $course
     * @return array
     */
    public static function check_enddatethreshold($course)
    {
        global $CFG;
        $threshold = get_config('local_checkconfig', 'checkenddatethreshold');
        $active = get_config('local_checkconfig', 'checkenddate');
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        if (!empty($active) && !empty($threshold)) {
            $mustenddateuntil = (int)$course->startdate + (int)$threshold;
            // si la fecha de finalizacion supera el umbral de dias, muestra una alerta
            if ((int)$course->enddate > $mustenddateuntil || empty($course->enddate)) {
                $date = userdate($mustenddateuntil, get_string('strftimedatetimeshort', 'langconfig'));
                return [
                    'message' => get_string('enddatethreshold', 'local_checkconfig', $date),
                    'url' => sprintf("%s/course/edit.php?id=%d", $CFG->wwwroot, $course->id)
                ];
            }
        }
        return [];
    }

    /**
     * Verifica si el curso tiene el restreo de finalización activado
     * @param stdClass $course
     * @return array
     */
    public static function check_completionenabled($course)
    {
        global $CFG;
        $active = get_config('local_checkconfig', 'checkcompletionenabled');
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        if (!empty($active) && empty($course->enablecompletion)) {
            return [
                'message' => get_string('completionenabledmessage', 'local_checkconfig'),
                'url' => sprintf("%s/course/edit.php?id=%d", $CFG->wwwroot, $course->id)
            ];
        }

        return [];
    }

    /**
     * Verifica si existen criterios para finalizar el curso
     * @param stdClass $course
     * @return array
     */
    public static function check_criterias($course)
    {
        global $CFG;
        $active = get_config('local_checkconfig', 'checkhascompletioncriterias');
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        if (!empty($active)) {
            $completion = new \completion_info($course);
            $hascriteria = $completion->has_criteria();
            if (!$hascriteria) {
                return [
                    'message' => get_string('hascriteriamessage', 'local_checkconfig'),
                    'url' => sprintf("%s/course/completion.php?id=%d", $CFG->wwwroot, $course->id)
                ];
            }
        }
        return [];
    }

    /**
     * Verifica si el curso tiene configurado un gradepass
     * @param object $course
     * @return array
     */
    public static function check_gradepass($course)
    {
        /** @var \mysqli_native_moodle_database $DB */
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/grade/constants.php');
        include_once($CFG->dirroot . '/lib/grade/grade_item.php');
        include_once($CFG->dirroot . '/lib/grade/grade_category.php');

        $active = get_config('local_checkconfig', 'checkgradepass');
        $hascapability = has_capability('moodle/grade:manage', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        if (!empty($active)) {
            $coursegradeitem = \grade_item::fetch_course_item($course->id);
            $grade_categories = $DB->get_records('grade_categories', ['courseid' => $course->id]);
            if (empty((int)$coursegradeitem->gradepass)) {
                return [
                    'message' => get_string('gradepassmessage', 'local_checkconfig'),
                    'url' => sprintf(
                        "%s/grade/edit/tree/category.php?courseid=%d&id=%d&gpr_type=edit&gpr_plugin=tree&gpr_courseid=%d",
                        $CFG->wwwroot,
                        $course->id,
                        reset($grade_categories)->id,
                        $course->id
                    )
                ];
            }
        }

        return [];
    }

    /**
     * Verfica fecha de finalizacion de un curso 
     * @param object course
     * @return array
     */
    public static function check_endedcourse($course)
    {
        global $CFG;
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        $active = get_config('local_checkconfig', 'checkendedcourse');
        if (!empty($active)) {
            $enddate = $course->enddate;
            $today = time();
            if ($today > $enddate) {
                return [
                    'message' => get_string('endedcoursemessage', 'local_checkconfig'),
                    'url' => sprintf("%s/course/edit.php?id=%d", $CFG->wwwroot, $course->id)
                ];
            }
        }
        return [];
    }

    /**
     * Verifica si esta por terminar un curso segun el umbral configurado
     * @param object course
     * @return array
     */
    public static function check_endedcoursethreshold($course)
    {
        global $CFG;
        $hascapability = has_capability('moodle/course:update', \context_course::instance($course->id));
        if (!$hascapability) {
            return [];
        }
        $threshold = get_config('local_checkconfig', 'checkendedcoursethreshold');
        if (!empty($threshold)) {
            $alerttime = $course->enddate - (int)$threshold;
            $today = time();
            if ($today > $alerttime && $today < $course->enddate) {
                $date = userdate($course->enddate, get_string('strftimedatetimeshort', 'langconfig'));
                return [
                    'message' => get_string('endedcoursethresholdmessage', 'local_checkconfig', $date),
                    'url' => sprintf("%s/course/edit.php?id=%d", $CFG->wwwroot, $course->id)

                ];
            }
        }

        return [];
    }
}
