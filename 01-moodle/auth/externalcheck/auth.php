<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
use local_sso\login;

class auth_plugin_externalcheck extends \auth_plugin_base {
    // Propiedad para almacenar datos de usuario obtenidos del API (para get_userinfo)
    // private static $external_userinfo = null;

    public function __construct() {
        $this->authtype = 'externalcheck';
        $this->config = get_config('auth_externalcheck');  // Carga configuración si la hubiera
    }

    function user_login($username, $password) {
        global $CFG, $DB, $USER;
        if (!$user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return false;
        }
        if (!validate_internal_user_password($user, $password)) {
            return false;
        }
        if ($password === 'changeme') {
            // force the change - this is deprecated and it makes sense only for manual auth,
            // because most other plugins can not change password easily or
            // passwords are always specified by users
            set_user_preference('auth_forcepasswordchange', true, $user->id);
        }
        return true;
    }

    public function loginpage_hook()
    {

        global $SESSION, $CFG, $DB;

        $SESSION->oauth2matic = false;
        $username_format = "";

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Captura los datos enviados por el formulario de login
            $username = isset($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            // Puedes hacer lo que necesites con $username y $password aquí
            // error_log("Login submit: username=$username");
            // error_log("Login submit: password=$password");
            $user = $DB->get_record("user", ['username' => $username]);

            if (!$user) {
                return false;
            }

            $migrado_permantente = $DB->get_record('user_preferences', array(
                'userid' => $user->id,
                'name' => 'migradologin',
                'value' => "1",
            ));

            if ($migrado_permantente) {
                $login = new login();
                // Si el username tiene formato 11111-1, tomar solo la parte antes del guion
                if (strpos($username, '-') !== false) {
                    $username_format = explode('-', $username)[0];
                }
                $login_sso = $login->request_login_sso($username_format, $password);
                
                if (is_array($login_sso) && array_key_exists('response', $login_sso)) {

                    $array_response = $login_sso["response"];
                    if (array_key_exists('data', $array_response)) {
                        $user_obj = get_complete_user_data('id', $user->id);

                        $login_user = complete_user_login($user_obj);
                        // $SESSION->show_migrado_alert = true;
                        redirect(new \moodle_url('/'));
                    }

                    // return true;
                }
                return false;
            }
            return false;
        }
        return false;
    }

}
