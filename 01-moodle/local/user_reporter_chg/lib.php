<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Injects a custom navigation link into Moodle's global navigation tree.
 *
 * This hook is automatically detected and executed by Moodle when
 * building the global navigation. It allows the plugin to expose
 * its main report page under the left-hand navigation menu.
 *
 * Behaviour:
 * - Checks whether the current user has the capability
 *   'local/user_reporter_chg:view'.
 * - If allowed, a new navigation node labeled with the plugin’s
 *   language string 'navigation_link' is added.
 * - The link points to /local/user_reporter_chg/index.php.
 *
 * Placement:
 * - The node is added to the root of the global navigation tree
 *   (left sidebar), appearing for authorized users only.
 *
 * @param global_navigation $nav   The full Moodle navigation tree.
 *
 * @see has_capability()
 * @see moodle_url
 * @see navigation_node
 *
 * @package     local_user_reporter_chg
 * @category    navigation
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */
function local_user_reporter_chg_extend_navigation(global_navigation $nav) {
    $context = context_system::instance();

    // Only users with permission can see the navigation link.
    if (!has_capability('local/user_reporter_chg:view', $context)) {
        return;
    }

    // Destination URL of the report.
    $url = new moodle_url('/local/user_reporter_chg/index.php');
    $text = get_string('navigation_link', 'local_user_reporter_chg');

    // Create a new navigation node.
    $node = navigation_node::create(
        $text,
        $url,
        navigation_node::TYPE_CUSTOM,
        null,
        'local_user_reporter_chg',
        new pix_icon('i/report', $text)
    );

    // Add to the root level of the navigation tree.
    $nav->add_node($node);
}
