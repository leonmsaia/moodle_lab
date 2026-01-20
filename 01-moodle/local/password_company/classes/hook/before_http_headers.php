<?php

namespace local_password_company\hook;

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

        if (has_capability('mod/folder:managefiles', $context)) {
            
            $url = new moodle_url('/local/password_company/view.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_password_company'),
                    'action' => $url,
                    'key' => 'local_password_company',
                ])
            );

            $node->showinflatnavigation = true;
            if ($PAGE->pagetype == 'local-password_company-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    
    }
}
