<?php

namespace local_questionnaire_report\hook;

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

        if (has_capability('local/questionnaire_report:view', $context, $USER->id)) {
            
            $url = new moodle_url('/local/questionnaire_report/view.php');

            $node = $primarynav->add_node(
                new navigation_node([
                    'text' => get_string('pluginname', 'local_questionnaire_report'),
                    'action' => $url,
                    'key' => 'local_questionnaire_report',
                ])
            );

            $node->showinflatnavigation = true;

            if ($PAGE->pagetype == 'local-questionnaire_report-view') {
                if ($active = $primarynav->find_active_node()) {
                    $active->make_inactive();
                }
                $node->make_active();
            }
        }
    }
}
