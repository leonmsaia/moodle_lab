<?php
/**
 * Admin settings integration for the local_user_reporter_chg plugin.
 *
 * This file registers an entry under:
 *
 *     Site administration → Reports
 *
 * allowing administrators and users with the capability
 * 'local/user_reporter_chg:view' to access the report page.
 *
 * Behaviour:
 * - Checks if the current user has site configuration rights ($hassiteconfig).
 * - Adds an admin_externalpage pointing to the plugin’s index.php.
 *
 * Notes:
 * - This plugin does not expose configurable settings; it only adds a
 *   navigation entry inside the admin tree.
 *
 * @package     local_user_reporter_chg
 * @category    admin
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add(
        'reports',
        new admin_externalpage(
            'local_user_reporter_chg',
            get_string('navigation_link', 'local_user_reporter_chg'),
            new moodle_url('/local/user_reporter_chg/index.php'),
            'local/user_reporter_chg:view'
        )
    );
}
