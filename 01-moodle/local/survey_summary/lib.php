<?php


/* function local_survey_summary_before_http_headers() {
    global $USER, $SESSION;

    $context = context_system::instance();
    $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador_summary();

    if ((has_capability('local/survey_summary:view', $context, $USER->id) || $mostrarMenu) && isloggedin()) { 
        $hook = new \local_survey_summary\hook\before_http_headers();
        $hook->render_menu();
    }
} */

function local_survey_summary_extend_navigation(global_navigation $navigation) {   
    global $USER, $SESSION;

    $SESSION->facilitador = false;

    $context = context_system::instance();
    $mostrarMenu = (isset($SESSION->facilitador) && ($SESSION->facilitador)) ? true : check_facilitador_summary();

    if ((has_capability('local/survey_summary:view', $context, $USER->id) || $mostrarMenu) && isloggedin()) { 
        $url = new moodle_url('/local/survey_summary/index.php');
        $node = navigation_node::create(
            get_string('pluginname', 'local_survey_summary'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('icon', '', 'mod_feedback', array('title' => get_string('pluginname', 'local_survey_summary')))
        );
        $node->showinflatnavigation = true;
        $node->classes = array('local_survey_summary');
        $node->key = 'local_survey_summary';

        if (isset($node) && isloggedin()) {
            $navigation->add_node($node);
        } 
    }
}

function check_facilitador_summary(){
    global $USER, $SESSION;

    $SESSION->facilitador = false;
    $enrol_courses = enrol_get_all_users_courses($USER->id);

    foreach($enrol_courses as $enrol_course){
        $context = context_course::instance($enrol_course->id);
            if (has_capability('local/eabccalendar:view', $context, $USER->id)) { 
                $SESSION->facilitador = true;
                break;
            }
    }
    
    return $SESSION->facilitador;
}