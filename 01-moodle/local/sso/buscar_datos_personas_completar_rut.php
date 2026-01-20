<?php
require('../../config.php');
require_login();
use local_sso\login;

$param = required_param('param', PARAM_RAW);
$status = optional_param('status', '2', PARAM_TEXT);

$context = context_system::instance();
require_capability('moodle/site:config', $context);
if (!is_string($param)) {
    throw new moodle_exception('Invalid parameter: expected comma-separated string.');
}

$param_array = array_map('trim', explode(',', $param));

foreach ($param_array as $key => $value) {

    $get_user = $DB->get_record('user', array('username' => $value));

    $login = new login();

    $buscar_usuario = $login->mutual_buscar_usuario($value);


    echo "<br>Datos completados para el usuario: " . $value . "<br>";
    echo print_r($buscar_usuario, true);
    echo "<br>";
    echo "<br>";


    // if(!empty($get_users)) {
    //     echo "<br>Datos completados para el usuario: " . $value . "<br>";
    //     echo print_r($buscar_usuario, true);
    //     echo "<br>";
    //     echo "<br>";
    // } else {
    //     echo "User not found: $value <br>";
    // }
}


