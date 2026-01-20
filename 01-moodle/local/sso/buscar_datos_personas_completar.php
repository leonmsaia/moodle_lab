<?php
require('../../config.php');
require_login();
use local_sso\login;

// $param = required_param('param', PARAM_RAW);
$status = optional_param('status', '2', PARAM_TEXT);

$context = context_system::instance();
require_capability('moodle/site:config', $context);
// if (!is_string($param)) {
//     throw new moodle_exception('Invalid parameter: expected comma-separated string.');
// }

$get_users = $DB->get_records_sql('SELECT * FROM {user} WHERE firstname is null or firstname = ""');
$get_users_count = $DB->get_record_sql('SELECT count(*) as cantidad FROM {user} WHERE firstname is null or firstname = ""');

// echo print_r($get_users, true);
$login = new login();
if (!empty($get_users)) {
    echo "Cantidad de usuarios sin username: " . $get_users_count->cantidad . "<br>";

    foreach ($get_users as $user_obj) {
        try {

            $username_format = $user_obj->username;
            if (strpos($user_obj->username, '-') !== false) {
                $username_format = explode('-', $user_obj->username)[0];
            }

            echo "<br>";
            echo print_r($username_format, true);
            echo "<br>";

            $buscar_usuario = $login->mutual_buscar_usuario($username_format);
            $apellidoMaterno = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->apellidoMaterno;
            $apellidoPaterno = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->apellidoPaterno;
            $contrasena = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->contrasena;
            $mail = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->mail;
            $nombre = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->nombre;
            $rut = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->rut;
            $nombre = (string) $buscar_usuario->response->buscaTrabajadorResponse->trabajador->nombre;


            echo print_r($nombre, true);
            echo "<br>";
            echo print_r($apellidoPaterno, true);
            echo "<br>";
            echo print_r($mail, true);
            echo "<br>";

            if ($nombre && $apellidoPaterno && $mail) {
                //guardo la informacion, valido que el api traiga la data necesaria, lo guardo en 35 y en 45
                // Actualiza los campos indicados en el usuario existente
                $user_obj_new = new stdClass();
                $user_obj_new->id = $user_obj->id; // ID obligatorio para identificar el usuario
                $user_obj_new->firstname = $nombre;
                $user_obj_new->lastname = $apellidoPaterno;
                $user_obj_new->email = $mail;

                echo print_r($user_obj_new, true);
                $DB->update_record('user', $user_obj_new);

                $user = $user_obj_new;

                if ($user) {
                    echo "<br>Datos completados para el usuario: " . $user_obj->username . "<br>";
                }
            }

        } catch (\Exception $th) {
            echo "Error al completar datos para el usuario: " . $user_obj->username . "<br>";
            echo "Error al completar datos para el usuario: " . $th->getMessage() . "<br>";
        }
    }
} else {
    echo "User not found <br>";
}