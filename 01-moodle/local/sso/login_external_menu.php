<?php

require_once('../../config.php');

global $CFG, $DB;



// $payload = optional_param('payload', '', PARAM_RAW);



$payload = optional_param('payload', '', PARAM_RAW);
$redirectto  = optional_param('redirectto', '', PARAM_RAW);

// echo "<br>==================payload==========<br>";
// echo(print_r($payload, true));   
// echo "<br>==================payload==========<br>";

$login_object = new \local_sso\login();



$validate_login = $login_object->sso_decrypt($payload);


if(empty($validate_login)) {
    redirect(new \moodle_url('/'), 'Error al iniciar sesión');
}

if (abs(time() - $validate_login['timestamp']) > 300) {
    redirect(new \moodle_url('/'), 'Token vencido');
}

$user = get_complete_user_data('id', $validate_login['id']);


set_user_preference('auth_forcepasswordchange', 0, $user);

// echo "<br>==================validate_login==========<br>";
// echo(print_r($validate_login, true));   
// echo "<br>==================validate_login==========<br>";
if ($user) {
    $SESSION->redirect_user = true;
    $login_user = complete_user_login($user);


    if ($login_user) {
        if (!empty($redirectto)) {
            // Sanitizar el path para evitar open redirects
            $url = new \moodle_url($redirectto);
            redirect($url);
        } else {
            redirect(new \moodle_url('/'));
        }
    } else {
        redirect(new \moodle_url('/'), 'error al iniciar sesión');
    }
    

}