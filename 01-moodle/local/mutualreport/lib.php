<?php

defined('MOODLE_INTERNAL') || die();

/* 
function local_mutualreport_before_http_headers() {
    $hook = new \local_mutualreport\hook\before_http_headers();
    $hook->render_menu();
} */

function local_mutualreport_extend_navigation(global_navigation $navigation)
{

    $url = \local_mutualreport\url::view_report_elsa_v2();
    $node = navigation_node::create(
        get_string('pluginname', 'local_mutualreport'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/settings', '')
    );
    $node->showinflatnavigation = true;
    $node->classes = array('localmutualreport');
    $node->key = 'localmutualreport';
    $capable = has_capability('local/mutualreport:view', \context_system::instance());

    if (isset($node) && isloggedin() && $capable) {
        $navigation->add_node($node);
    }
}
