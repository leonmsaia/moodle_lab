<?php
/* 
function local_eabcprogramas_before_http_headers() {
    $hook = new \local_eabcprogramas\hook\before_http_headers();
    $hook->render_menu();
}
 */

function local_eabcprogramas_extend_navigation(global_navigation $navigation)
{
    if (is_siteadmin()) {
        $url = new moodle_url('/local/eabcprogramas/manage.php');
        $node = navigation_node::create(
            get_string('pluginname', 'local_eabcprogramas'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/settings', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('manage-programas');
        $node->key = 'manage-programas';

        if (isset($node)) {
            $navigation->add_node($node);
        }
    } else if (
        has_capability('local/eabcprogramas:holding', context_system::instance())
        || has_capability('local/eabcprogramas:trabajador', context_system::instance())
    ) {
        $url = new moodle_url('/local/eabcprogramas/programs/list_programs.php');
        $node = navigation_node::create(
            get_string('programas_otorgados', 'local_eabcprogramas'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/settings', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('list-programas');
        $node->key = 'list-programas';

        if (isset($node)) {
            $navigation->add_node($node);
        }
    }
}

/**
 *
 * @param stdClass $course 
 * @param stdClass $cm 
 * @param stdClass $context
 * @param string $filearea
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_eabcprogramas_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    $itemid = array_shift($args);

    $filename = array_pop($args); 
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/' . implode('/', $args) . '/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_eabcprogramas', 'local_eabcprogramas_img', $itemid, $filepath, $filename);
    if (!$file) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
