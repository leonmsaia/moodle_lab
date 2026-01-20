<?php

require_once('../../config.php');

global $CFG, $DB;

include_once $CFG->libdir . '/filelib.php';


$payload = optional_param('payload', '', PARAM_RAW);
$username = optional_param('username', '', PARAM_RAW);

$user = $DB->get_record_sql("SELECT * FROM {user} WHERE username like '$username%'");

// $user = end($get_datas);

// echo('==================validate_external_user==========');
// echo(print_r($user, true));
// echo('==================validate_external_user==========');
if (!empty($user)) {
    $user_obj = get_complete_user_data('username', $user->username);
    set_user_preference('auth_forcepasswordchange', 0, $user_obj);
    set_user_preference('migrado45', 2, $user_obj);
    $login_user = complete_user_login($user_obj);


    if ($login_user) {
        redirect(new \moodle_url('/'));
    } else {
        redirect(new \moodle_url('/login/index.php'), 'error al iniciar sesión');
    }
}