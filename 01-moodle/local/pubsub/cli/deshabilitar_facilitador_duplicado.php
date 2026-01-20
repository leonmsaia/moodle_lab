<?php

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
/** @var moodle_database $DB */
global $CFG, $DB;
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/user/lib.php');
$users = $DB->get_records_sql('SELECT u.* FROM {facilitador_back} as fb JOIN {user} as u ON fb.id_user_moodle = u.id WHERE u.suspended = 0');

foreach($users as $user){
    if(!is_siteadmin($user->id)){
        //si esta en la tabla de usuario facilitador
        if(\mod_eabcattendance\metodos_comunes::validar_rut($user->username) == false){
            $updateuser = new stdClass();
            $updateuser->id = $user->id;
            $updateuser->suspended = 1;
            echo "Deshabilitador facilitador: " . $user->username . ", ";
            user_update_user($updateuser, true, false);
            \core\session\manager::kill_user_sessions($user->id);
        }
    }
}
