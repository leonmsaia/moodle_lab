<?php

namespace local_eabcprogramas\hook;

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
            
            $url = new moodle_url('/local/eabcprogramas/manage.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_eabcprogramas'),
                    'action' => $url,
                    'key' => 'local_eabcprogramas',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-eabcprogramas-manage') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
