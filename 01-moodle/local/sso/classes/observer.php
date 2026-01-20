<?php
use local_sso\login;

defined('MOODLE_INTERNAL') || die();

class local_sso_observer
{
    public static function user_loggedin(\core\event\user_loggedin $event)
    {
        global $DB, $SESSION;

        // $actionurl = new moodle_url('https://example.com/confirm_action.php'); // Cambia esto a la URL de tu acción
        // $cancelurl  = new moodle_url('/');
        // //despues de confirmar o rechazar debo simular el comportamiento nativo para guardar la nota del usuario esta accion de submit es antes de guardar la nota
        // echo $OUTPUT->confirm(
        //     "confirmar",
        //     new single_button($actionurl, get_string('yes'), 'get'),
        //     new single_button($cancelurl, get_string('no'), 'get')
        // );

        // redirect(new moodle_url('/local/sso/view_login_msg.php'));
        // return ;
        // if (!empty(get_config('local_sso', 'enable_manual_login_sso'))) {

        //     $userid = $event->get_data()["userid"];

        //     $user_obj = get_complete_user_data('id', $userid);
        //     // set_user_preference('auth_forcepasswordchange', 0, $user_obj);

        //     //consulto si esta marcado para 45
        //     $preference = get_user_preferences('migrado', null, $userid);

        //     if (!empty($preference) || $preference != 0) {

        //         $login = new login();
        //         $is_active = $login->is_in_course_user_site($userid);

        //         //si todavia esta activo lo dejo en 35
        //         if (!$is_active) {
        //             set_user_preference('migrado', 2, $userid);
        //             //si no esta activo valido si es de 45 y lo envio a 45
        //             $validate_external_user = $login->get_user_external_moodle($user_obj->username);

        //             // error_log("validate_external_user: " . print_r($validate_external_user, true)); 
        //             if ($validate_external_user['success']) {

        //                 $sesskey = sesskey();
        //                 //$url_internal_validate = new moodle_url('/local/sso/login_manual_external.php?username=' . $validate_external_user['username'] . '&sesskey=' . $sesskey);
        //                 $url_internal_validate = new moodle_url('/local/sso/login_external.php?username=' . $validate_external_user['username'] . '&sesskey=' . $sesskey);
        //                 $SESSION->wantsurl = $url_internal_validate;
        //                 //redirect($url_internal_validate);
        //                 echo '<script>window.location.href = "' . $url_internal_validate . '";</script>';
        //             }
        //         }

        //     }

        // }

        $SESSION->show_migrado_alert_count = 0;

        $login = new login();

        // if (!empty(get_config('local_sso', 'enable_manual_login_sso'))) {

        $userid = $event->get_data()["userid"];

        $preference = get_user_preferences('migrado', null, $userid);

        if (empty($preference) && $preference != 3) {
            set_user_preference('migrado', 2, $userid);
        }

        $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : $login->check_facilitador_summary();
        if ($mostrarMenu || is_siteadmin()) {

        } else {

            $user_obj = get_complete_user_data('id', $userid);

            $validate_external_user_prev = $login->get_user_external_moodle($user_obj->username);
            

            if ($validate_external_user_prev['false'] == false) {
                //guardo la informacion, valido que el api traiga la data necesaria, lo guardo en 35 y en 45
                $external_user = $login->create_user_external_moodle($user_obj->username, $user_obj->password, $user_obj->firstname, $user_obj->lastname, $user_obj->email, true);
            } 

        }



        // }


        // }



    }

    public static function page_viewed() {
    //     global $USER, $CFG;

    // error_log("==================page_viewed==========");
    //     error_log("==================page_viewed==========");        
    // echo "<br>==================page_viewed==========<br>";
    // echo "<br>==================page_viewed==========<br>";
    // echo "<br>==================page_viewed==========<br>";
    //     error_log("==================page_viewed==========");

    //     echo "<br>==================page_viewed==========<br>";
    //     echo "<br>==================page_viewed==========<br>";
    //     echo "<br>==================page_viewed==========<br>";
    //     echo "<br>==================page_viewed==========<br>";
    //     if(isloggedin()){
    //         if (!empty(get_config('local_sso', 'enable_manual_login_sso'))) {

    //             $userid = $USER->id;

    //             $user_obj = get_complete_user_data('id', $userid);
    //             // set_user_preference('auth_forcepasswordchange', 0, $user_obj);

    //             //consulto si esta marcado para 45
    //             $preference = get_user_preferences('migrado', null, $userid);

    //             if (!empty($preference) || $preference != 0) {

    //                 $login = new login();
    //                 $is_active = $login->is_in_course_user_site($userid);

    //                 //si todavia esta activo lo dejo en 35
    //                 if (!$is_active) {
    //                     set_user_preference('migrado', 2, $userid);
    //                     //si no esta activo valido si es de 45 y lo envio a 45
    //                     $validate_external_user = $login->get_user_external_moodle($user_obj->username);

    //                     // error_log("validate_external_user: " . print_r($validate_external_user, true)); 
    //                     if ($validate_external_user['success']) {

    //                         $sesskey = sesskey();
    //                         // $url_internal_validate = new moodle_url('/local/sso/login_manual_external.php?username=' . $validate_external_user['username'] . '&sesskey=' . $sesskey);
    //                         // $SESSION->wantsurl = $url_internal_validate;
    //                         redirect( get_config('local_sso', 'url_moodle') . '/local/sso/login_external.php?username=' . $validate_external_user['username']);
    //                     }
    //                 }

    //             }

    //         }
    //     }
        
    }

    public static function user_loggedout(\core\event\user_loggedout $event) {
        // $url = get_config('local_sso', 'url_moodle') . '/login/logout.php';
        // $options = ['ignoresecurity' => true];
        // download_file_content($url, null, $options);
        // redirect(new moodle_url(get_config('local_sso', 'url_moodle') . '/login/logout.php'));
    }
    
    public static function login_failed(\core\event\user_login_failed $event)
    {
        global $DB, $USER;

        $data = $event->get_data();
        if(get_config('local_sso', 'enable_login_sso_msg')) {
            $username = $data['other']['username'];
            $get_user = $DB->get_record_sql("SELECT * FROM {user} WHERE username LIKE ? ", [$username . '%']);

            if ($get_user) {
                $preference = get_user_preferences('migrado', null, $get_user->id);
                $migrado_login = $DB->get_record('user_preferences', array(
                    'userid' => $get_user->id,
                    'name' => 'migradologin',
                    'value' => "1",
                ));
                
                if ($migrado_login) {
                    //solo mostrar mensaje para usaurios migradologin 22/09/2025
                    redirect(new moodle_url('/local/sso/view_login_msg.php'));
                }
            }
        }

    }

}