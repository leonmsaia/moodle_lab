<?php

namespace local_download_cert;

use mod_quiz\question\qubaids_for_users_attempts;

class download_cert_utils
{

    public static function save_configyear_certificate($data)
    {
        global $DB;
        $dataobjects = new \stdClass();
        $dataobjects->courseid = $data->courseid;
        $dataobjects->expiration_year = $data->expiration_year;

        $sql = $DB->get_record('download_cert_expiration', array('courseid' => $data->courseid));
        if (!empty($sql)) {
            $dataobjects->id = $sql->id;
            $DB->update_record('download_cert_expiration', $dataobjects);
        } else {
            $DB->insert_record('download_cert_expiration', $dataobjects);
        }
    }

    public static function completion_cert($courseid)
    {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/constants.php');
        $completion = false;
        //nota de aprobado de curso
        $gradeitemparamscourse = [
            'itemtype' => 'course',
            'courseid' => $courseid,
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);


        if (!empty($grade_course)) {
            $grades_user = \grade_grade::fetch_users_grades($grade_course, array($USER->id), false);

            if (!empty($grades_user)) {
                $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                if (!empty($finalgradeuser)) {

                    if (floatval($finalgradeuser) >= floatval($grade_course->gradepass)) {
                        //aprobado
                        return true;
                    } else {
                        //nota menor reprobado
                        return false;
                    }
                }
            } else {
                //no tiene configurada la nota
                return false;
            }
        } else {
            return false;
        }
    }

    public static function completion_attendance($courseid)
    {
        global $DB, $USER, $CFG;
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/constants.php');
        $completion = false;
        //nota de aprobado de curso
        $gradeitemparamscourse = [
            'itemtype' => 'mod',
            'courseid' => $courseid,
            'itemmodule' => 'eabcattendance'
        ];
        $grade_course = \grade_item::fetch($gradeitemparamscourse);


        if (!empty($grade_course)) {
            $grades_user = \grade_grade::fetch_users_grades($grade_course, array($USER->id), false);

            if (!empty($grades_user)) {
                $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                if (!empty($finalgradeuser)) {
                    $complete_val = get_config('local_download_cert', 'completion_attendance');
                    $complete_val = (!empty($complete_val)) ? $complete_val : 100;
                    if (floatval($finalgradeuser) >= $complete_val) {
                        //aprobado
                        return true;
                    } else {
                        //nota menor reprobado
                        return false;
                    }
                }
            } else {
                //no tiene configurada la nota
                return false;
            }
        } else {
            return false;
        }
    }


    public static function completion_course_type($courseid, $type)
    {
        global $CFG, $USER;
        require_once($CFG->libdir . '/grade/grade_item.php');
        require_once($CFG->libdir . '/grade/grade_grade.php');
        require_once($CFG->libdir . '/grade/constants.php');
        $abrobado = false;

        if ($type == 'elerning') {
            //nota de aprobado de curso presencial pasado en encuesta (questionaire)
            $gradeitemparamsmod = [
                'itemtype' => 'mod',
                'courseid' => $courseid,
                'itemmodule' => 'feedback',
            ];

            $grade_mods = \grade_item::fetch_all($gradeitemparamsmod);

            foreach ($grade_mods as $grade_mod) {
                if (!empty($grade_mod)) {
                    $grades_user = \grade_grade::fetch_users_grades($grade_mod, array($USER->id), false);
                    if (!empty($grades_user)) {
                        $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                        if (!empty($finalgradeuser)) {
                            if (floatval($finalgradeuser) >= floatval($grade_mod->gradepass)) {
                                //aprobado
                                $abrobado = true;
                            } else {
                                //nota menor reprobado
                                $abrobado = false;
                            }
                        } else {
                            //no tiene nota
                            $abrobado = null;
                        }
                    } else {
                        //no tiene configurada la nota
                        $abrobado = null;
                    }
                } else {
                    $abrobado = null;
                }
            }
        } elseif ($type == 'presencial') {
            $configpresencial = get_config('local_download_cert', 'completion_mod_presencial');
            $configpresencial = (!empty($configpresencial)) ? $configpresencial : 'quiz';

            //nota de aprobado de curso presencial pasado en quiz
            $gradeitemparamsmod = [
                'itemtype' => 'mod',
                'courseid' => $courseid,
                'itemmodule' => $configpresencial,
            ];
            //$grade_mod = \grade_item::fetch($gradeitemparamsmod);
            $grade_mods = \grade_item::fetch_all($gradeitemparamsmod);

            foreach ($grade_mods as $grade_mod) {
                if (!empty($grade_mod)) {

                    $grades_user = \grade_grade::fetch_users_grades($grade_mod, array($USER->id), false);
                    if (!empty($grades_user)) {
                        $finalgradeuser = $grades_user[key($grades_user)]->finalgrade;
                        if (!empty($finalgradeuser)) {
                            if (floatval($finalgradeuser) >= floatval($grade_mod->gradepass)) {
                                //aprobado
                                $abrobado = true;
                            } else {
                                //nota menor reprobado
                                $abrobado = false;
                            }
                        } else {
                            //no tiene nota
                            $abrobado = null;
                        }
                    } else {
                        //no tiene configurada la nota
                        $abrobado = null;
                    }
                } else {
                    $abrobado = null;
                }
            }
        }
        return $abrobado;
    }


    /**
     * Delete all the attempts belonging to a user in a particular quiz.
     *
     * @param object $quizid int
     * @param object $userid int
     */
    public static function quiz_delete_user_attempts_user(int $quizid, int $userid): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->dirroot . '/question/engine/lib.php');

        $qubaids = new qubaids_for_users_attempts($quizid, $userid);

        // Elimina todos los question_usages_by_activity asociados a los intentos del usuario
        // question_engine::delete_questions_usage_by_activities($qubaids);

        $params = ['quiz' => $quizid, 'userid' => $userid];
        $DB->delete_records('quiz_attempts', $params);
        $DB->delete_records('quiz_grades', $params);
    }

    public static function clear_attemps_course_user($userid, $courseid)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');
        $key = $userid . '_' . $courseid;
        $completioncache = \cache::make('core', 'completion');
        $completioncache->delete($key);

        $cache = \cache::make('core', 'coursecompletion');
        $cache->delete($key);

        //limpiar criterios de completado de actividad por curso
        $completioncrit = $DB->get_records('course_completion_crit_compl', array('userid' => $userid, 'course' => $courseid));
        if ($completioncrit) {
            $DB->delete_records("course_completion_crit_compl", array('userid' => $userid, 'course' => $courseid));
        }

        $modinfo = \get_fast_modinfo($courseid);
        $cms = $modinfo->get_cms();
        foreach ($cms as $cm) {
            //limpiar intentos de completado de actividad scomr
            if ($cm->modname == "scorm") {
                $atemps_scroms = scorm_get_all_attempts($cm->instance, $userid);
                $scorm = $DB->get_record('scorm', array('id' => $cm->instance));
                foreach ($atemps_scroms as $atemps_scrom) {
                    scorm_delete_attempt($userid, $scorm, $atemps_scrom);
                }
            }
            //limpiar intentos de completado de actividad quiz
            if ($cm->modname == "quiz") {
                self::quiz_delete_user_attempts_user($cm->instance, $userid);
            }
        }
        //limpiar datos envio manual
        $send_course = $DB->get_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        if(!empty($send_course)){
            $DB->delete_records('format_eabctiles_send_course', array("userid" => $userid, "courseid" => $courseid));
        }
    }
}
