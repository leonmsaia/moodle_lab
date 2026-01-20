<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lib file.
 *
 * @package   holdingmng
 * @copyright e-ABC Learning 2020 (contacto@e-abclearning.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This function extends the navigation with the tool items
 *
 * @param global_navigation $navigation The navigation node to extend
 */

/*  function local_holdingmng_before_http_headers() {
    $hook = new \local_holdingmng\hook\before_http_headers();
    $hook->render_menu();
}
 */

function local_holdingmng_extend_navigation(global_navigation $navigation) {
    global $CFG;

    if (is_siteadmin() or has_capability('local/holdingmng:create', context_system::instance())) {
        $url = new moodle_url('/local/holdingmng/holdings.php');
        
        $settingsnode = navigation_node::create(
            get_string('pluginname', 'local_holdingmng'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/settings', '')
        );
        $settingsnode->showinflatnavigation = true;
        $settingsnode->classes = array('holdingmng');
        $settingsnode->key = 'holdingmng';

        if (isset($settingsnode)) {
            $navigation->add_node($settingsnode);
        }
    }
}
