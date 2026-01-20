<?php
namespace local_sso\task;

defined('MOODLE_INTERNAL') || die();

use local_sso\login;
class syncadmin extends \core\task\scheduled_task {

    /**
     * Nombre legible para mostrar en la administración del sitio.
     */
    public function get_name() {
        return get_string('syncadmin', 'local_sso');
    }

    /**
     * Lo que hace la tarea cuando corre.
     */
    public function execute() {
        mtrace("Iniciando job de sincronizacion de admins");

        $login = new login();
        // Aquí pones tu lógica.
        global $DB;
        $get_admins_data = $DB->get_record_sql("SELECT * FROM {config} WHERE name = 'siteadmins'");
        $get_admins = explode(',', $get_admins_data->value);

        foreach ($get_admins as $admin) {
            $get_user = $DB->get_record('user', ['id' => $admin]);

            if($admin != 2) {
                if(!empty($get_user)) {
                    $validate_external_user = $login->get_user_external_moodle($get_user->username);
                    if ($validate_external_user && $validate_external_user['success'] == false) {
                        $login->create_user_external_moodle($get_user->username, $get_user->password, $get_user->firstname, $get_user->lastname, $get_user->email, true, true);
                    }
                }
            }
       
        }

        mtrace("Job finalizado correctamente.");
    }
}
