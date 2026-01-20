<?php

/**
 * Este MENU corresponde al Plugin de EMMA
 */

function local_mutual_extend_navigation(global_navigation $navigation) {   
    global $USER;
    $context = context_system::instance();

    if (has_capability('message/emma:viewreports', $context, $USER->id)) { 
        $url = new moodle_url('/message/output/emma/view.php');        
        $node = navigation_node::create(
            'Reportes EMMA',
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
function local_mutual_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_mutual', "mutual", $args[0], '/', $args[1]);
    \core\session\manager::write_close();
    send_stored_file($file, 0, 0, $forcedownload, ["filename" => $args[1]]);
}