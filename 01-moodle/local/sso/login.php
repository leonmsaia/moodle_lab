<?php

use local_sso\login;

require_once('../../config.php');

global $CFG, $DB;

$token = optional_param('token', '', PARAM_RAW);
$username = optional_param('username', '', PARAM_RAW);

$login = new login($token);

$validate_sso = $login->request_validate_sso();

if ($validate_sso) {
    if ($validate_sso->response) {
        if ($validate_sso->response->data->userId) {
            $username = $validate_sso->response->data->userId;
        }
    }
}

if (empty($username)) {
    redirect(new \moodle_url('/login/index.php'), 'Error al iniciar sesión');
}

$userValidate = $DB->get_record_sql("SELECT * FROM {user} WHERE username LIKE ? ", [$username . '-%']);

if ($userValidate) {
    $user = $userValidate;
} else {
    //creo el usuario en 35 y 45 si es necesario
    try {
        //busco el usuario en api de mutual
        //busco sus datos en el el api suministrada por mutual
        $buscar_usuario = $login->mutual_buscar_usuario($username);

        $apellidoMaterno = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->apellidoMaterno;
        $apellidoPaterno = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->apellidoPaterno;
        $contrasena = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->contrasena;
        $mail = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->mail;
        $nombre = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->nombre;
        $rut = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->rut;
        $nombre = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->nombre;

        $data = [];
        $data['username'] = $rut;
        $data['password'] = $contrasena;
        $data['firstname'] = $nombre;
        $data['lastname'] = $apellidoPaterno;
        $data['email'] = $mail;

        //guardo la informacion, valido que el api traiga la data necesaria, lo guardo en 35 y en 45
        $user = $login->create_user($data);
        // si esta marcado como migrado permantente lo envio a 45
        set_user_preference('migrado', 3, $user->id);
        set_user_preference('migradologin', "1", $user->id);


    } catch (Exception $e) {
        redirect(new \moodle_url('/login/index.php'), 'Error al buscar usuario');
    }

}

if (!empty($user)) {

    $user_obj = get_complete_user_data('username', $user->username);
    set_user_preference('auth_forcepasswordchange', 0, $user_obj);

    // si esta marcado como migrado permantente lo envio a 45
    set_user_preference('migrado', 3, $user->id);
    set_user_preference('migradologin', "1", $user->id);

    $login_user = complete_user_login($user_obj);

    if ($login_user) {
        redirect(new \moodle_url('/'));
    } else {
        redirect(new \moodle_url('/login/index.php'), 'error al iniciar sesión');
    }
    redirect(new \moodle_url('/login/index.php'), 'Usuario no existe');

} else {
    redirect(new \moodle_url('/login/index.php'), 'Usuario no existe');
}
