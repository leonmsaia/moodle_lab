<?php

require_once('../../config.php');

global $CFG, $DB;

include_once $CFG->libdir . '/filelib.php';


$payload = optional_param('payload', '', PARAM_RAW);

if(empty($payload)) {
    redirect(new \moodle_url('/'), 'Error al iniciar sesión');
}

$login_object = new \local_sso\login();

$validate_login = $login_object->sso_decrypt($payload);

echo "==================validate_login==========";
error_log(print_r($validate_login, true));
error_log('==================validate_login==========');

if(empty($validate_login)) {
    redirect(new \moodle_url('/'), 'Error al iniciar sesión');
}

if (abs(time() - $validate_login['timestamp']) > 60) {
    redirect(new \moodle_url('/'), 'Token vencido');
}

$user = authenticate_user_login($validate_login['username'], $validate_login['password']);


if ($user) {

    $login_user = complete_user_login($user);


    if ($login_user) {
        redirect(new \moodle_url('/'));
    } else {
        redirect(new \moodle_url('/'), 'error al iniciar sesión');
    }
    

}
