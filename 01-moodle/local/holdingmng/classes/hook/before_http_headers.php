<?php

namespace local_holdingmng\hook;

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

        if (is_siteadmin() or has_capability('local/holdingmng:create', $context)) {
            
            $url = new moodle_url('/local/holdingmng/index.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_holdingmng'),
                    'action' => $url,
                    'key' => 'local_holdingmng',
                ])
            );

            $node->showinflatnavigation = true;
            
            if ($PAGE->pagetype == 'local-holdingmng-holdings') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
