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
 * Settings used by the eabctiles course format
 *
 * @package local_rating_item
 * @copyright  2020 José Salgado jose@e-abclearning.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;


function local_rating_item_extend_navigation(global_navigation $navigation) {
    global $COURSE;
    $context = context_course::instance($COURSE->id, MUST_EXIST);
    if (has_capability('local/rating_item:view', $context) && ($COURSE->id > 1)) {
        $url = new moodle_url('/local/rating_item/manage_rating_item.php', array('courseid' => $COURSE->id));
        $node = navigation_node::create(
                get_string('pluginname', 'local_rating_item'), 
                $url, navigation_node::TYPE_SETTING, null, null, new 
                pix_icon('i/manual_item', '')
        );
        $node->showinflatnavigation = true;
        $node->classes = array('local_rating_item');
        $node->key = 'local_rating_item';

        $url_view = new moodle_url('/local/rating_item/view_rating_item.php', array('courseid' => $COURSE->id, 'action' => 'grader'));
        $nodeview = navigation_node::create(
                get_string('ratingitemrating', 'local_rating_item'), 
                $url_view, navigation_node::TYPE_SETTING, null, null, new 
                pix_icon('i/settings', '')
        );
        $nodeview->showinflatnavigation = true;
        $nodeview->classes = array('local_rating_item_view');
        $nodeview->key = 'local_rating_item_view';
        
        if (isset($nodeview) && isloggedin()) {
            $navigation->add_node($node);
            $navigation->add_node($nodeview);
        }
    }
}



