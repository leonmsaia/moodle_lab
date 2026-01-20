<?php

/* function local_questionnaire_report_before_http_headers() {
    $hook = new \local_questionnaire_report\hook\before_http_headers();
    $hook->render_menu();
}
 */
function local_questionnaire_report_extend_navigation(global_navigation $navigation) {   
    global $USER;
    $context = context_system::instance();

    if (has_capability('local/questionnaire_report:view', $context, $USER->id)) {        
        $url = new moodle_url('/local/questionnaire_report/view.php');
        $node = navigation_node::create(
            get_string('pluginname', 'local_questionnaire_report'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('localquestionnaire_report');
        $node->key = 'localquestionnaire_report';

        if (isset($node) && isloggedin()) {
            $navigation->add_node($node);
        } 
    }
}



