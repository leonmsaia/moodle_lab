<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->libdir . '/authlib.php');

class local_sso_external extends external_api {

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
}
