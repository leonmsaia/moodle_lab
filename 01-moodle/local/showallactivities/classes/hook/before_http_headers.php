<?php

namespace local_showallactivities\hook;

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

        if (isloggedin() && has_capability('local/showallactivities:showallactivities', \context_system::instance())) {
            $url = new moodle_url('/local/showallactivities/index.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_showallactivities'),
                    'action' => $url,
                    'key' => 'local_showallactivities',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-showallactivities-index') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }

        
        
    }
}
