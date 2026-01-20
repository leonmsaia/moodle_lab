<?php

namespace local_enrolcompany\hook;

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

        $context = \context_system::instance();

        if(has_capability('local/enrolcompany:enrol', $context) ){
            
            $url = new moodle_url('/local/enrolcompany/index.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('enrolcompany:enrol', 'local_enrolcompany'),
                    'action' => $url,
                    'key' => 'local_enrolcompany',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-enrolcompany-index') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
