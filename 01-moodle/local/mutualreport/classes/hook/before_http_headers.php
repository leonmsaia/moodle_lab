<?php

namespace local_mutualreport\hook;

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
        if (isloggedin() && has_capability('local/mutualreport:view', $context)) {
            // Get the first report that is visible to the current user.
            $url = \local_mutualreport\utils::get_first_visible_report_url();

            // If no report is visible, don't show the menu item.
            if (!$url) {
                return;
            }

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_mutualreport'),
                    'action' => $url,
                    'key' => 'local_mutualreport',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-mutualreport-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    
    }
}
