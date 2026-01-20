<?php

namespace local_historicocertificados\hook;
use navigation_node;
use moodle_url;

class before_http_headers
{

    /**
     * Render menu
     ** Uso para version45 moodle
     * @return void
     */
    public static function render_menu()
    {
        /** @var \moodle_page $PAGE */
        global $PAGE, $SESSION, $USER, $DB, $CFG;

        if (isloggedin()) {

            if (get_config('local_historicocertificados', 'enable_historic_cert')) {
                /** @var \core\navigation\views\primary $primarynav */
                $primarynav = $PAGE->primarynav;

                $node = $primarynav->add_node(
                    new \navigation_node([
                        'text' => get_string('title_menu', 'local_historicocertificados'),
                        'action' => new \moodle_url('/'),
                        'key' => 'local_historicocertificados',
                    ])
                );

                $node->showinflatnavigation = true;

                if (get_config('local_historicocertificados', 'enable_historic_cert_elearning')) {
                    // add submenu items under the main node
                    $node->add(
                        get_string('local_historicocertificados_elearning', 'local_historicocertificados'),
                        new \moodle_url('/local/historicocertificados/certificado_elearning.php'),
                        null,
                        null,
                        'local_historicocertificados_elearning'
                    )->showinflatnavigation = true;
                }
                

                if (get_config('local_historicocertificados', 'enable_historic_cert_syp')) {
                    // add submenu items under the main node
                    $node->add(
                        get_string('local_historicocertificados_sys', 'local_historicocertificados'),
                        new \moodle_url('/local/historicocertificados/certificado_syp.php'),
                        null,
                        null,
                        'local_historicocertificados_syp'
                    )->showinflatnavigation = true;
                }
                

                if ($PAGE->pagetype == 'local-local_historicocertificados') {
                    if ($active = $primarynav->find_active_node()) {
                        $active->make_inactive();
                    }
                    $node->make_active();
                }
            }

        }

    }
}
