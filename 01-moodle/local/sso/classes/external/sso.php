<?php

namespace local_sso\external;

use coding_exception;
use dml_exception;
use external_function_parameters;
use external_single_structure;
use invalid_parameter_exception;
use moodle_exception;
use external_api;
use external_value;
use local_sso\login;

require_once($CFG->libdir . '/externallib.php');

class sso extends external_api
{
    /**
     * @return external_function_parameters
     */
    public static function get_company_parameters()
    {
        return new external_function_parameters(
            array(
                'rut' => new external_value(PARAM_RAW, 'Rut del participante'),
            )
        );
    }

    /**
     * @param $data
     * @return array
     * @throws coding_exception
     * @throws moodle_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_company($rut)
    {
        global $DB;
        /** @var external_api $api */
        $params = self::validate_parameters(
            self::get_company_parameters(),
            array(
                'rut' => $rut
            )
        );
        // self::validate_parameters(self::get_company_parameters(), ['rut' => $rut]);
        // $externaluser = $params;
        $rut = strtolower(trim($rut));
        // $get_user = $DB->get_record_sql("SELECT * FROM {user} WHERE username = ?", [$rut]);
        // if ($get_user) {
        //     $newuserid = $get_user->id;
        // } else {
        //     $newuserid = null;
        // }

        $user_obj = get_complete_user_data('username', $rut );
        if(empty($user_obj)) {
            throw new moodle_exception('El rut no existe en el sistema');
        }
        // error_log(print_r($user_obj->profile['empresarut'], true));
        // error_log(print_r($user_obj->profile['empresarazonsocial'], true));
        // error_log(print_r($user_obj->profile['empresacontrato'], true));

        return [
            "rut" => $rut,
            "userid" => $user_obj->id,
            "company_name" => $user_obj->profile['empresarazonsocial'],
            "rut_company" => $user_obj->profile['empresarut'],
            "contract" => $user_obj->profile['empresacontrato'],
            ];
    }

    /**
     * @return external_single_structure
     */
    public static function get_company_returns()
    {
        return new external_single_structure(
            array(
                'rut' => new \external_value(PARAM_RAW, 'rut inscripto'),
                'userid' => new \external_value(PARAM_RAW, 'userid o null si el usuario ya estaba creado'),
                'company_name' => new \external_value(PARAM_RAW, 'Nombre de la empresa'),
                'rut_company' => new \external_value(PARAM_RAW, 'Rut de la empresa'),
                'contract' => new \external_value(PARAM_RAW, 'Contrato de la empresa'),
            )
        );
    }

    public static function validate_login_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username'),
            'password' => new external_value(PARAM_RAW, 'Password'),
        ]);
    }

    public static function validate_login($username, $password) {
        global $DB;

        self::validate_parameters(self::validate_login_parameters(), [
            'username' => $username,
            'password' => $password,
        ]);

        if ($user = authenticate_user_login($username, $password)) {
            return [
                'success' => true,
                'id' => $user->id,
                'fullname' => fullname($user),
                'email' => $user->email,
            ];
        } else {
            return ['success' => false];
        }
    }

    public static function validate_login_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'id' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'Full name', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'Email', VALUE_OPTIONAL),
        ]);
    }

    public static function get_user_username_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username'),
            'strict' => new external_value(PARAM_RAW, 'strict (opcional)', VALUE_OPTIONAL, false, true),
        ]);
    }

    public static function get_user_username($username, $strict = false) {
        global $DB;
        $user = null;
        self::validate_parameters(self::get_user_username_parameters(), [
            'username' => $username,
        ]);

        if($strict) {
            $user = $DB->get_record_sql("SELECT * FROM {user} WHERE username = '$username'");
        } else {
            $user = $DB->get_record_sql("SELECT * FROM {user} WHERE username like '$username%'");
        }
        if ($user) {
            $user_obj = get_complete_user_data('id', $user->id);

            return [
                'success' => true,
                'id' => $user->id,
                'fullname' => fullname($user),
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'maternalSurname' => $user_obj->profile['contactoapellidomaterno'] ?? ''
                // 'migrado' => get_user_preferences('migrado', null, $user->id) ?? '0',
            ];
        } else {
            return ['success' => false];
        }
    }

    public static function get_user_username_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'id' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'Full name', VALUE_OPTIONAL),
            'username' => new external_value(PARAM_TEXT, 'username', VALUE_OPTIONAL),
            'firstname' => new external_value(PARAM_TEXT, 'firstname', VALUE_OPTIONAL),
            'lastname' => new external_value(PARAM_TEXT, 'lastname', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'Email', VALUE_OPTIONAL),
            'maternalSurname' => new external_value(PARAM_TEXT, 'Apellido materno', VALUE_OPTIONAL),
            // 'migrado' => new external_value(PARAM_TEXT, 'Email', VALUE_OPTIONAL),
        ]);
    }

    
    public static function create_user_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username'),
            'password' => new external_value(PARAM_RAW, 'password'),
            'firstname' => new external_value(PARAM_RAW, 'firstname'),
            'lastname' => new external_value(PARAM_RAW, 'lastname'),
            'email' => new external_value(PARAM_RAW, 'email'),
            'raw_password' => new external_value(PARAM_RAW, 'raw_password (opcional)', VALUE_OPTIONAL, false, true),
            'is_admin' => new external_value(PARAM_RAW, 'is_admin (opcional)', VALUE_OPTIONAL, false, true),
        ]);
    }

    public static function create_user($username, $password, $firstname, $lastname, $email, $raw_password = false, $is_admin = false) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/lib/adminlib.php');

        self::validate_parameters(self::create_user_parameters(), [
            'username' => $username,
            'password' => $password,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'raw_password' => $raw_password,
            'is_admin' => $is_admin,
        ]);

        
        // error_log("paso de la validacion");
        
        if ($user = $DB->get_record_sql("SELECT * FROM {user} WHERE username like '$username%'")) {
            $user_obj = get_complete_user_data('id', $user->id);

            return [
                'success' => true,
                'id' => $user->id,
                'fullname' => fullname($user),
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'maternalSurname' => $user_obj->profile['contactoapellidomaterno'] ?? ''
            ];
        } else {
            $data = [];
            $data['username'] = $username;
            $data['password'] = $password;
            $data['firstname'] = $firstname;
            $data['lastname'] = $lastname;
            $data['email'] = $email;
                
            $login = new login();
            $user = $login->create_user($data);

            if($raw_password) {
                error_log('paso por el raw_password');
                $DB->execute("UPDATE {user} SET password = ? WHERE id = ?", [$password, $user->id]);
            }

            if($is_admin) {
                $admins = explode(',', get_config('moodle', 'siteadmins'));

                $newid = $user->id; // id del usuario que quieres asignar

                if (!in_array($newid, $admins)) {
                    $admins[] = $newid;
                    set_config('siteadmins', implode(',', $admins));
                }
            } else {
                //creo el usaurio en 45 y lo marco migrado 45
                set_user_preference('migrado45', 2, $user->id);
            }

            $user_obj = get_complete_user_data('id', $user->id);
            return [
                'success' => true,
                'id' => $user->id,
                'fullname' => fullname($user),
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'maternalSurname' => $user_obj->profile['contactoapellidomaterno'] ?? ''
            ];
        }
    }

    public static function create_user_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'id' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL),
            'fullname' => new external_value(PARAM_TEXT, 'Full name', VALUE_OPTIONAL),
            'username' => new external_value(PARAM_TEXT, 'username', VALUE_OPTIONAL),
            'firstname' => new external_value(PARAM_TEXT, 'firstname', VALUE_OPTIONAL),
            'lastname' => new external_value(PARAM_TEXT, 'lastname', VALUE_OPTIONAL),
            'email' => new external_value(PARAM_TEXT, 'Email', VALUE_OPTIONAL),
            'maternalSurname' => new external_value(PARAM_TEXT, 'Apellido materno', VALUE_OPTIONAL),
        ]);
    }

    public static function update_password_user_parameters() {
        return new external_function_parameters([
            'username' => new external_value(PARAM_RAW, 'Username'),
            'password' => new external_value(PARAM_RAW, 'password'),
        ]);
    }

    public static function update_password_user($username, $password) {
        global $DB, $CFG;

        self::validate_parameters(self::update_password_user_parameters(), [
            'username' => $username,
            'password' => $password,
        ]);

        if ($user = $DB->get_record_sql("SELECT * FROM {user} WHERE username = '$username'")) {

            if ($user) {
                $migrado = $DB->get_record('user_preferences', array(
                    'userid' => $user->id,
                    'name' => 'migrado45',
                    'value' => 2,
                ));

                if (empty($migrado)) {
                    //si el usuario NO fue migrado lo marco como migrado
                    set_user_preference('migrado45', 2, $user->id);
                }

                if (!empty(get_config('local_sso', 'enable_login_recover_pass'))) {

                    $DB->execute("UPDATE {user} SET password = ? WHERE id = ?", [$password, $user->id]);
                }

            }
            return ['success' => true];
        } else {
            return ['success' => false];
        }
    }

    public static function update_password_user_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
    
}