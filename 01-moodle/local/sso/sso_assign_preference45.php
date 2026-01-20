<?php
require('../../config.php');
require_login();

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

    if(!empty($get_user)) {
        echo "Marcado usuario para 45: $value <br>";
        set_user_preference("migrado45", $status, $get_user);
    } else {
        echo "User not found: $value <br>";
    }
}