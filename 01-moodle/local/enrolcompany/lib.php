<?php

/* function local_enrolcompany_before_http_headers() {
    $hook = new \local_enrolcompany\hook\before_http_headers();
    $hook->render_menu();
}
 */

function local_enrolcompany_extend_navigation(global_navigation $navigation) {   
    global $COURSE, $DB;
    $context = \context_system::instance();
    if(has_capability('local/enrolcompany:enrol', $context) ){
        $activeurl = new moodle_url('/local/enrolcompany/index.php');
        $activenode = navigation_node::create(
            'Flujo alternativo de inscripciones',
            $activeurl,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('icon', '', 'local_takeattendance')
        );
        $activenode->showinflatnavigation = true;
        $activenode->classes = array('localenrolcompany');
        $activenode->key = 'localenrolcompany';

        if (isset($activenode) && isloggedin()) {
            $navigation->add_node($activenode);
        }
    }
}

/**
 * @param $course
 * @param $cm
 * @param $context
 * @param $filearea
 * @param $args
 * @param $forcedownload
 * @param array $options
 */
function local_enrolcompany_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_enrolcompany', "inscripciones", $args[0], '/', $args[1]);

    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, $forcedownload, ["filename" => $args[1]]);

}
