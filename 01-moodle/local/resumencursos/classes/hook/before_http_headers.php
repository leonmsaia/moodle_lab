<?php

namespace local_resumencursos\hook;

use navigation_node;
use moodle_url;

class before_http_headers {
   
    /**
     * Render menu
     *
     * @return void
     */
    public static function render_menu() {
        /** @var \moodle_page $PAGE */
        global $PAGE, $COURSE, $USER;

        /** @var \core\navigation\views\primary $primarynav */
        $primarynav = $PAGE->primarynav;

        $url = new moodle_url('/local/resumencursos/view.php');

        if (isloggedin()) {
            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_resumencursos'),
                    'action' => $url,
                    'key' => 'local_resumencursos',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-resumencursos-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }

        
    }
}
