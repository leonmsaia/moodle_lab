<?php

/* function local_feedback_facilitador_extend_navigation_frontpage(navigation_node $parentnode,stdClass $course,context_course $context)
{
    global $USER;
    $context = context_system::instance();

    if (has_capability('local/feedback_facilitador:view', $context, $USER->id)) { 
        $url = new moodle_url('/local/feedback_facilitador/view.php');
        $parentnode->add(
            get_string('pluginname', 'local_feedback_facilitador'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            "local_feedback_facilitador",
            new pix_icon('i/report', '')
        );
    }
} */

/* function local_feedback_facilitador_before_http_headers() {
    $hook = new \local_feedback_facilitador\hook\before_http_headers();
    $hook->render_menu();
}
 */
