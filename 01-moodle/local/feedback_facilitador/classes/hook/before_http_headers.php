<?php

namespace local_feedback_facilitador\hook;

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

        if (has_capability('local/feedback_facilitador:view', $context, $USER->id)) {
            
            $url = new moodle_url('/local/feedback_facilitador/view.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_feedback_facilitador'),
                    'action' => $url,
                    'key' => 'feedback_facilitador',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-feedback-facilitador') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
