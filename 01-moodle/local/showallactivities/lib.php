<?php
/* 
function local_showallactivities_before_http_headers() {
    $hook = new \local_showallactivities\hook\before_http_headers();
    $hook->render_menu();
} */

function local_showallactivities_extend_navigation(global_navigation $navigation) {   
    $url = new moodle_url('/local/showallactivities/index.php');
    $node = navigation_node::create(
        get_string('pluginname', 'local_showallactivities'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/settings', '')
    );
    $node->showinflatnavigation = true;
    $node->classes = array('localshowallactivities');
    $node->key = 'localshowallactivities';

    if (isset($node) && isloggedin() && (has_capability('local/rating_item:view', \context_system::instance()))) {
        $navigation->add_node($node);
    }
    
}
