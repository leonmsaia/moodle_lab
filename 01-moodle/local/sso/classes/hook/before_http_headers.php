<?php

namespace local_sso\hook;
use local_sso\login;

use navigation_node;
use moodle_url;

class before_http_headers {
   
    /**
     * Render menu
     ** Uso para version45 moodle
     * @return void
     */
    public static function render_menu() {
        /** @var \moodle_page $PAGE */
        global $PAGE, $SESSION, $USER, $DB;
        
        if (isloggedin() && get_config('local_sso', 'enable_menu_login_sso')) {

            //  echo '<br>==================is_active==========<br>';
            //         echo print_r($roles, true);
            //         echo '<br>==================is_active==========<br>';

            $login = new login();
            $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador) || isset($SESSION->rol_incluido) && ($SESSION->rol_incluido)) ? true : $login->check_facilitador_summary();
            if ($mostrarMenu || is_siteadmin() || $SESSION->rol_incluido) {
                
                /** @var \core\navigation\views\primary $primarynav */
                $primarynav = $PAGE->primarynav;
                $validate_external_user = $login->get_user_external_moodle($USER->username);

                if ($validate_external_user["success"] == true) {

                    $payload = $login->sso_encrypt([
                        'id' => $validate_external_user['id'],
                        'timestamp' => time()
                    ]);

                    $url = get_config('local_sso', 'url_moodle') . '/local/sso/login_external_menu.php?payload=' . $payload;

                    $node = $primarynav->add_node(
                        new navigation_node([
                            'text' => get_config('local_sso', 'title_external_site'),
                            'action' => $url,
                            'key' => 'local_sso',
                        ])
                    );

                    $node->showinflatnavigation = true;

                    if ($PAGE->pagetype == 'local-sso-index') {
                        if ($active = $primarynav->find_active_node()) {
                            $active->make_inactive();
                        }
                        $node->make_active();
                    }
                }
            }

            $migradologin = $DB->get_record('user_preferences', array(
                'userid' => $USER->id,
                'name' => 'migradologin',
                'value' => "1",
            ));

            if ($migradologin && $SESSION->show_migrado_alert_count == 0) {
                // $PAGE->requires->js_call_amd('local_sso/alertsvt', 'init', []);
            }
            
            $SESSION->show_migrado_alert_count++;


         
            
        }
    }
}
