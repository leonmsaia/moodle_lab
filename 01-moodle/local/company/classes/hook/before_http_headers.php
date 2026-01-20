<?php

namespace local_company\hook;

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
        global $PAGE, $USER;

        /** @var \core\navigation\views\primary $primarynav */
        $primarynav = $PAGE->primarynav;
        $context = \context_system::instance();
        if (isloggedin() && is_siteadmin()) {
            $url = new moodle_url('/local/company/index.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_company'),
                    'action' => $url,
                    'key' => 'local_company',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-company-index') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    
    }
}
