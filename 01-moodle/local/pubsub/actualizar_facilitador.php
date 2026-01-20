<?php

require_once('../../config.php');

$users = $DB->get_records_sql('SELECT u.*, fb.rut, fb.dv FROM {user} as u JOIN {facilitador_back} as fb ON u.id = fb.id_user_moodle');
$context = context_system::instance();

foreach($users as $user){
    // si el nombre de usuario actual no tiene un rut valido y tiene registro en rut y dv de facilitador_back y uniendo esos campos tiene un rut valido lo proceso
    if((\mod_eabcattendance\metodos_comunes::validar_rut($user->rut . "-" . $user->dv) == true) && !empty($user->rut) && !empty($user->dv) && (\mod_eabcattendance\metodos_comunes::validar_rut($user->username) == false) ){
        try {
            $transaction = $DB->start_delegated_transaction();
                $username_temp = $user->username;
                $rutsinguion = $user->rut.$user->dv;
                $rut = strtolower($user->rut . "-" . $user->dv);
                $user->username = $rut;
                $user->password = $rut;
                $user->oldusername = $username_temp;
                if(!$DB->record_exists('user', array('username' => $rut)) && strtolower($rutsinguion) == $username_temp){
                    user_update_user($user , true, false);
                    $msg = "<br> Usuario " . fullname($user) . " se a actualizado su username de " . $username_temp . " a " . $rut;
                
                    echo $msg;
                }
                
        } catch(Exception $e) {
            $transaction->rollback($e);
        }
    } else {
        echo "<br> Usuario " . fullname($user) . " con rut: " . $user->username . " No se a actualizado " ;
    }
}
    $transaction->allow_commit();


