<?php

defined('MOODLE_INTERNAL') || die();

/** @var object $CFG */
require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");
require_once($CFG->libdir . "/moodlelib.php");

/* function local_password_company_before_http_headers() {
    $hook = new \local_password_company\hook\before_http_headers();
    $hook->render_menu();
} */

function local_password_company_extend_navigation(global_navigation $navigation) {   
    $context = context_system::instance();
    $url = new moodle_url('/local/password_company/view.php');
    $node = navigation_node::create(
        get_string('pluginname', 'local_password_company'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/settings', '')
    );
    $node->showinflatnavigation = true;
    $node->classes = array('localpassword_company');
    $node->key = 'localpassword_company';

    
    if (isset($node) && isloggedin() && has_capability('mod/folder:managefiles', $context)) {
        $navigation->add_node($node);
    }
    
}
