<?php

require_once('../../config.php');

global $CFG, $DB;
require_login(); // siempre asegura que el usuario esté autenticado
$token = required_param('sesskey', PARAM_RAW);
$username = required_param('username', PARAM_RAW);

if (confirm_sesskey($sesskey)) {
    redirect( get_config('local_sso', 'url_moodle') . '/local/sso/login_external.php?username=' . $username);
} else {
    // Clave de sesión inválida: posible intento CSRF o sesión expirada
    throw new moodle_exception('invalidsesskey');
}