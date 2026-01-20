<?php

namespace local_pubsub\back;

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/mod/eabcattendance/locallib.php';
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->libdir . '/grade/grade_item.php');
require_once($CFG->libdir . '/grade/grade_grade.php');
require_once($CFG->libdir . '/grade/constants.php');

class inscripciones_masivas{

    public static function nota_asistencia($userId,$GuidSesion,$IdentificadorParticipante,$IdRegistroDynamics)
    {
        global $DB, $USER;
        
        $conditions = [
            'numero_documento' => $IdentificadorParticipante,
            'guid_sesion' => $GuidSesion,
        ];

        $record = $DB->get_record('eabcattendance_carga_masiva', $conditions);

        if($record){
            $courseId   = $record->id_curso_moodle;
            $sesionId   = $record->id_sesion_moodle;
            $cmId       = $record->cmid;
            
            $course = $DB->get_record('course', array('id' => $courseId));
            $transaction = $DB->start_delegated_transaction();

            try {
                // SE ASIGNA NOTA Y ASISTENCIA
                $sql = "SELECT a.id, a.name
                        FROM {assign} a
                        JOIN {course_modules} cm ON cm.instance = a.id
                        JOIN {modules} m ON m.id = cm.module
                        WHERE m.name = 'assign' AND cm.course = :courseid";
                $paramsAssig = array('courseid' => $courseId);
                $assignments = $DB->get_records_sql($sql, $paramsAssig);

                $assignment = reset($assignments);
                $assignid = $assignment->id;


                $cmAssign = get_coursemodule_from_instance('assign', $assignid, $courseId, false, MUST_EXIST);
                $contextAssign = \context_module::instance($cmAssign->id);

                // Carga la instancia de la tarea
                $assign = new \assign($contextAssign, $cmAssign, $course);
                
                $grade_data = $assign->get_user_grade($userId, true);

                if (!$grade_data) {
                    $grade_data = new \stdClass();
                    $grade_data->userid = $userId;
                    $grade_data->assignment = $assignid;
                    $grade_data->grade = $record->calificacion;
                    $grade_data->timecreated = time();
                    $grade_data->timemodified = time();
                    $grade_data->grader = $USER->id;
                    $grade_data->attemptnumber = 0;
                    $assign->update_grade($grade_data);
                } else {
                    $grade_data->grade = $record->calificacion;
                    $grade_data->timemodified = time();
                    $grade_data->grader = $USER->id;
                    $assign->update_grade($grade_data);
                }

                $paramsCompletion = array(
                    'userid'    => $userId,
                    'course'  => $course->id
                );
                $ccompletion = new \completion_completion($paramsCompletion);
                $ccompletion->mark_complete();

                $attendance_sesions = $DB->get_record('eabcattendance_sessions', ['id' => $sesionId]);

                $module = $DB->get_record("modules", ["name" => "eabcattendance"]);
                $attendance = $DB->get_record('eabcattendance', array('id' => $attendance_sesions->eabcattendanceid));
                $mod_attendance = $DB->get_record('course_modules', array('course' => $attendance->course, 'instance' => $attendance->id, "module" => $module->id));

                //ASIGNAR asistencia
                $takeData = new \stdClass();
                $takeData->{"user$userId"} = $record->user_statuses_att_id;
                $takeData->{"remarks$userId"} = '';
                $takeData->grouptype = $attendance_sesions->groupid;
                $takeData->id = $mod_attendance->id;

                $cm     = get_coursemodule_from_id('eabcattendance', $cmId, 0, false, MUST_EXIST);
                $attr   = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
                $context = \context_module::instance($cm->id);
                $pageparams = new \stdClass();
                $pageparams->sessionid = $sesionId;
                $att = new \mod_eabcattendance_structure($attr, $cm, $course, $context, $pageparams);
                $att->take_from_form_data($takeData);

                $grade_data2 = $assign->get_user_grade($userId, true);
                // VUELVO A ACTUALIZAR LA NOTA
                $grade_data2->grade = $record->calificacion;
                $grade_data2->timemodified = time();
                $grade_data2->grader = $USER->id;
                $assign->update_grade($grade_data2);
                
                // Actualiza la nota en el libro de calificaciones
                $grade_item = \grade_item::fetch(array('iteminstance' => $assignid, 'itemmodule' => 'assign', 'courseid' => $course->id));
                if ($grade_item) {
                    $grade_item->update_raw_grade($userId, $record->calificacion, 'mod/assign');
                    //$grade_item->update_final_grade($usrId, $record->calificacion, 'mod/assign');
                }
            
                $transaction->allow_commit();
            }
            catch (moodle_exception $e) {
                $transaction->rollback($e);
                $errormsg = 'Error writing to database: ' . $e->getMessage();
                debugging($errormsg, DEBUG_DEVELOPER);
            
                $combinedmsg = $errormsg . "\nDebug info: " . $e->debuginfo;
                throw new moodle_exception("error_incripcion_masiva_nota_asistencia", "local_pubsub", '', $combinedmsg);
            }
            
            
            $recordUpdate = new \stdClass;
            $recordUpdate->id = $record->id;
            $recordUpdate->recibido = 1;
            $recordUpdate->fecha_recibido = date("Y-m-d H:i:s");
            $recordUpdate->id_inscripcion_dynamics = $IdRegistroDynamics;
            $recordUpdate->user_id = $userId;

            $DB->update_record('eabcattendance_carga_masiva', $recordUpdate);
        }
    }
}