<?php

namespace mod_eabcattendance\utils;

use coding_exception;
use dml_exception;
use stdClass;
use context_module;
use mod_eabcattendance_structure;
use moodle_exception;

require_once($CFG->dirroot . '/lib/setuplib.php');
class frontutils {

    /**
     * @param $createschedule
     * @return mixed|object|stdClass
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public static function create_course_front($createschedule) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        $course = new stdClass();
        $courseexist = $DB->get_record('course', array('shortname' => $createschedule["coursename"]));
        if (!$courseexist) {
            $newcourse = new stdClass();
            $newcourse->category = 1;
            $newcourse->shortname = $createschedule["coursename"];
            $newcourse->fullname = $createschedule["coursename"];
            $newcourse->summary = '';
            $newcourse->format = 'eabctiles';
            // Create a new course.
            $course = create_course($newcourse);
            
            //evento de crear curso front
            $event = \mod_eabcattendance\event\eabcattendance_create_course::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'other' => array(
                                    'shortname' => $course->shortname,
                                    'fullname' => $course->fullname
                                ),
                                'courseid' => $course->id,
                            )
            );
            $event->trigger();
        } else {
            $course = $courseexist;
        }
        
        return $course;
    }

    public static function create_attendance($course) {
        global $DB, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/course/modlib.php');
        $module = $DB->get_record("modules", array('name' => 'eabcattendance'));
        $attendance = $DB->get_record("eabcattendance", array('course' => $course->id));
        if (empty($attendance)) {
            $newattendance = new \stdClass();
            $newattendance->introeditor = array('text' => '', 'format' => '1', 'itemid' => 745111724,);
            $newattendance->showdescription = 0;
            $newattendance->grade = 100;
            $newattendance->grade_rescalegrades = '';
            $newattendance->gradecat = 1;
            $newattendance->gradepass = '';
            $newattendance->visible = 1;
            $newattendance->visibleoncoursepage = 1;
            $newattendance->groupmode = 1;
            $newattendance->availabilityconditionsjson = '{"op":"&","c":[{"type":"eabcgroup"}],"showc":[true]}';
            $newattendance->course = $course->id;
            $newattendance->coursemodule = 0;
            $newattendance->section = 0;
            $newattendance->module = $module->id;
            $newattendance->modulename = 'eabcattendance';
            $newattendance->instance = 0;
            $newattendance->cmidnumber = '';
            $newattendance->add = 'eabcattendance';
            //crear attendance
            $attendance = add_moduleinfo($newattendance, $course);
            
            //evento de crear asistencia front
            $event = \mod_eabcattendance\event\eabcattendance_create_attendance::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'courseid' => $course->id,
                                'other' => array(
                                    'shortname' => $course->shortname,
                                    'fullname' => $course->fullname,
                                    'moduleid' => $module->id,
                                    'grade' => 100,
                                    'modulename' => 'eabcattendance',
                                )
                            )
            );
            $event->trigger();
        }
        return $attendance;
    }

    public static function create_group($createschedule, $course) {
        global $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');

        if (groups_get_group_by_name($course->id, $createschedule["creategroup"])) {
            throw new moodle_exception('errormsg', 'mod_eabcattendance', '', get_string('groupnameexists', 'group', $createschedule["creategroup"]));
        } else {
            $newgroupdata = new \stdClass();
            $newgroupdata->name = $createschedule["creategroup"];
            $newgroupdata->courseid = $course->id;
            $newgroupdata->description = '';
            $gid = groups_create_group($newgroupdata);
            
            //evento de crear grupo front
            $event = \mod_eabcattendance\event\eabcattendance_create_group::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'courseid' => $course->id,
                                'other' => array(
                                    'shortname' => $course->shortname,
                                    'fullname' => $course->fullname,
                                    'groupname' => $createschedule["creategroup"]
                                )
                            )
            );
            $event->trigger();
            
            return $gid;
        }
    }

    public static function create_session($attendance, $course, $createschedule, $gid) {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/lib/weblib.php');
        require_once($CFG->dirroot . '/lib/datalib.php');
        require_once($CFG->dirroot . '/mod/eabcattendance/classes/structure.php');
        
        $cm = get_coursemodule_from_instance('eabcattendance', $attendance->id, 0, false, MUST_EXIST);
        $attendance2 = $DB->get_record('eabcattendance', array('id' => $cm->instance), '*', MUST_EXIST);
        $context = context_module::instance($cm->id);
                // Get attendance.
        $attendance3 = new mod_eabcattendance_structure($attendance2, $cm, $course, $context);
        // Create session o sessiones.

        foreach ($createschedule["createsession"] as $createsession) {
            $session_description = (empty($createsession["description"])) ? "" : $createsession["description"];
            $sess = new \stdClass();
            $sess->sessdate = $createsession["sessdate"];
            $sess->duration = $createsession["duration"];
            $sess->descriptionitemid = 0;
            $sess->description = $session_description;
            $sess->descriptionformat = FORMAT_HTML;
            $sess->directionitemid = 0;
            $sess->direction = '';
            $sess->directionformat = FORMAT_HTML;
            $sess->calendarevent = (int) $createsession["calendarevent"];
            $sess->timemodified = time();
            $sess->studentscanmark = 0;
            $sess->autoassignstatus = 0;
            $sess->subnet = '';
            $sess->studentpassword = '';
            $sess->automark = 0;
            $sess->automarkcompleted = 0;
            $sess->absenteereport = get_config('eabcattendance', 'absenteereport_default');
            $sess->includeqrcode = 0;
            $sess->subnet = $attendance3->subnet;
            $sess->statusset = 0;
            $sess->groupid = $gid;
            $sess->guid = $createsession["guid"];
            $sessionid = $attendance3->add_session($sess);
            
            //evento de crear session front
            $event = \mod_eabcattendance\event\eabcattendance_create_session::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'courseid' => $course->id,
                                'other' => array('shortname' => $course->shortname,
                                    'fullname' => $course->fullname,
                                    'sessionid' => $sessionid,
                                    'sessdate' => $createsession["sessdate"],
                                    'duration' => $createsession["duration"],
                                )
                            )
            );
            $event->trigger();
        }
        return $sessionid;
    }

    public static function create_user_and_enrol($createschedule, $course, $gid) {
        global $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');

        foreach ($createschedule["createuser"] as $createuser) {
            $role = get_config("eabcattendance", "rolwscreateactivity");
            //id del rol que se creará se tomara de la configuración dle plugin
            //en caso de no configurar nada tomar profesor sin permiso de edición(3)
            $roleid = (!empty($role)) ? $role : 3;
            $newuserid = self::create_user($createuser, $course);
            self::enrol_user($course, $newuserid, $gid, $roleid);
        }
    }

    public static function create_user($createuser, $course) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        self::validate_rut($createuser["username"]);
        $create_user = new stdClass();
        $get_user = $DB->get_record("user", array("username" => $createuser["username"]));

        $create_user->username = $createuser["username"];
        $create_user->firstname = $createuser["firstname"];
        $create_user->lastname = $createuser["lastmame"];
        $create_user->mnethostid = 1;
        $create_user->confirmed = 1;
        $create_user->email = $createuser["email"];
        $create_user->password = $createuser["username"];

        if (!empty($get_user)) {
            //si el usuario ya existe lo actualizo
            $create_user->id = $get_user->id;
            user_update_user($create_user);
            $newuserid = $get_user->id;
            self::insert_extra_fields($createuser, $newuserid, true);
        } else {
            //si el usuario no existe lo creo
            $newuserid = user_create_user($create_user);
            
            //insertar campos adicionales del usuario
            self::insert_extra_fields($createuser, $newuserid);
            
            //evento de crear grupo front
            $event = \mod_eabcattendance\event\eabcattendance_create_user::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'courseid' => $course->id,
                                'other' => array(
                                    'shortname' => $course->shortname,
                                    'fullname' => $course->fullname,
                                    'username' => $createuser["username"],
                                )
                            )
            );
            $event->trigger();
        }

        return $newuserid;
    }

    public static function insert_extra_fields($createuser, $newuserid, $update = false){
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        
        if($update){
            $custom = profile_user_record($newuserid);
            $sexo = (!empty($createuser["sexo"])) ? $createuser["sexo"] : $custom->Sexo;
            $nombre_adherente = (!empty($createuser["nombre_adherente"])) ? $createuser["nombre_adherente"] : $custom->Nombre_adherente;
            $rut_adherente = (!empty($createuser["rut_adherente"])) ? self::validate_rut($createuser["rut_adherente"], true) : $custom->Rut_adherente;
            $fecha_nacimiento = (!empty($createuser["fecha_nacimiento"])) ? $createuser["fecha_nacimiento"] : $custom->Fecha_nacimiento;
            $roles = (!empty($createuser["roles"])) ? $createuser["roles"] : $custom->Roles;
            $rut = (!empty($createuser["rut"])) ? $createuser["username"] : $custom->RUT;
        } else {
            $sexo = (!empty($createuser["sexo"])) ? $createuser["sexo"] : '';
            $nombre_adherente = (!empty($createuser["nombre_adherente"])) ? $createuser["nombre_adherente"] : '';
            $rut_adherente = (!empty($createuser["rut_adherente"])) ? self::validate_rut($createuser["rut_adherente"], true) : '';
            $fecha_nacimiento = (!empty($createuser["fecha_nacimiento"])) ? $createuser["fecha_nacimiento"] : '';
            $roles = (!empty($createuser["roles"])) ? $createuser["roles"] : '';
            $rut = (!empty($createuser["rut"])) ? $createuser["username"] : '';
        }

        $array_aditional_files = array(
            "participantesexo" => $sexo,
            "Nombre_adherente" => $nombre_adherente,
            "empresarut" => $rut_adherente,
            "participantefechanacimiento" => $fecha_nacimiento,
            "Roles" => $roles,
            "RUT" => $rut,
        );
        require_once($CFG->dirroot . '/user/profile/lib.php');

        profile_save_custom_fields($newuserid, $array_aditional_files);
    }

    /* validacion de rut formato y codigo verificador
     * validacion de rut de usuarios y empresas, en caso de no tener 
     * el parametro company solo valida rut de usuarios
     */
    public static function validate_rut($rut, $company = false){
        self::validate_format_rut($rut, $company);
        if($company == false){
            self::validate_verificator_code($rut);
        }
    }
    
    /*
     * validacion formato del rut xxxxxxxx-y
     */
    public static function validate_format_rut($rut, $company = false){
        if (!preg_match("/^[0-9]{7,8}+-[0-9kK]{1}$/", $rut)) {
            if($company){
                $message = get_string('validaterutcompany', 'mod_eabcattendance');
            } else {
                $message = get_string('validaterutuser', 'mod_eabcattendance');
            }
            throw new moodle_exception('errormsg', 'mod_eabcattendance', '', $message);
        } 
    }

    /*
     * validate verificator code rut
     */
    public static function validate_verificator_code($rut){
        $rut = explode("-", $rut);
	$dv = $rut[1];
        $numero = $rut[0];

        $i = 2;
        $suma = 0;
        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8)
                $i = 2;

            $suma += $v * $i;
            ++$i;
        }

        $dvr = 11 - ($suma % 11);
        if ($dvr == 11)
            $dvr = 0;
        if ($dvr == 10)
            $dvr = 'K';
        if ($dvr == strtoupper($dv))
            return true;
        else
            throw new moodle_exception('errormsg', 'mod_eabcattendance', '', get_string('validatevericatorcode', 'mod_eabcattendance'));
    }

    static public function validate_rut_format($rut) {

        if (strlen($rut) > 10){
            return false;
        }

        if(strlen($rut) == 9) {
            $rut = '0' . $rut;
        }

        $rut = preg_replace('/[^k0-9]/i', '', $rut);
        $dv = substr($rut, -1);
        $numero = substr($rut, 0, strlen($rut) - 1);

        if (!is_numeric($numero)) {
            return false;
        }

        if (strlen($numero) < 8){
            return false;
        }

        $i = 2;
        $suma = 0;

        foreach (array_reverse(str_split($numero)) as $v) {
            if ($i == 8)
                $i = 2;

            $suma += $v * $i;
            ++$i;
        }

        $dvr = 11 - ($suma % 11);

        if ($dvr == 11)
            $dvr = 0;
        if ($dvr == 10)
            $dvr = 'K';

        if ($dvr == strtoupper($dv)) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function enrol_user($course, $newuserid, $gid, $role) {
        global $DB, $CFG;

        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');

        $enrolinstances = enrol_get_instances($course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }

        $enrol = enrol_get_plugin('manual');

        //validar si el usuario ya esta matriculado
        $enrolId = $DB->get_record('user_enrolments', array('enrolid' => $instance->id, 'userid' => $newuserid));
        if (!$enrolId) {
            //si no esta matriculado creo la matriculacion y lo asigno al grupo creado anteriormente
            $newuseridenrol = $enrol->enrol_user($instance, $newuserid, $role);
            groups_add_member($gid, $newuserid);
            
            //evento de crear grupo front
            $event = \mod_eabcattendance\event\eabcattendance_enrol_user::create(
                            array(
                                'context' => \context_course::instance($course->id),
                                'courseid' => $course->id,
                                'other' => array(
                                    'shortname' => $course->shortname,
                                    'fullname' => $course->fullname,
                                    'usernameid' => $newuserid,
                                    'groupid' => $gid,
                                    'roleid' => $role
                                )
                            )
            );
            $event->trigger();
            
        } else {
            
        }
    }

    /**
     * @return array
     */
    public static function get_my_groups_selector() {
        global $COURSE;
        $mygroups = \groups_get_user_groups($COURSE->id);
        $result = [];
        foreach ($mygroups as $groupings) {
            foreach ($groupings as $group) {
                $result[$group] = groups_get_group_name($group);
            }
        }
        return $result;
    }

}
