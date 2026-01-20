<?php

/* function message_emma_before_http_headers() {
    $hook = new \message_emma\hook\before_http_headers();
    $hook->render_menu();
}
 */

/* 
function message_emma_extend_navigation(global_navigation $navigation) {   
    global $USER;
    $context = context_system::instance();
    
        $url = new moodle_url('/message/output/emma/view.php');
        $node = navigation_node::create(
            get_string('pluginname', 'message_emma'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/report', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('message_emma');
        $node->key = 'message_emma';

        if (isset($node) && isloggedin()) {
            $navigation->add_node($node);
        }     
} */

