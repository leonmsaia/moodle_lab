<?php

namespace message_emma\hook;

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

        if (is_siteadmin()) {
            
            $url = new moodle_url('/message/output/emma/view.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('reportes', 'message_emma'),
                    'action' => $url,
                    'key' => 'message-emma',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'message-output-emma-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
