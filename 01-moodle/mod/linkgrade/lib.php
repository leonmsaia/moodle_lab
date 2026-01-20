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
 * Library of functions and constants for module linkgrade
 *
 * @package mod_linkgrade
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/** LIBKGRADE_MAX_NAME_LENGTH = 50 */
define("LIBKGRADE_MAX_NAME_LENGTH", 50);

/**
 * @uses LIBKGRADE_MAX_NAME_LENGTH
 * @param object $linkgrade
 * @return string
 */
function get_linkgrade_name($linkgrade) {
    $name = strip_tags(format_string($linkgrade->name,true));
    
    if (empty($name)) {
        // arbitrary name
        $name = get_string('modulename','linkgrade');
    }

    return $name;
}
/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @global object
 * @param object $linkgrade
 * @return bool|int
 */
function linkgrade_add_instance($linkgrade) {
    global $DB, $CFG;

    $linkgrade->name = get_linkgrade_name($linkgrade);
    $linkgrade->timemodified = time();
    $url = $CFG->wwwroot . "/local/rating_item/view_rating_item.php?courseid=".$linkgrade->course;
    $linkgrade->intro = html_writer::link($url, get_string('ratingitemrating', 'local_rating_item'));

    $id = $DB->insert_record("linkgrade", $linkgrade);

    $completiontimeexpected = !empty($linkgrade->completionexpected) ? $linkgrade->completionexpected : null;
    \core_completion\api::update_completion_date_event($linkgrade->coursemodule, 'linkgrade', $id, $completiontimeexpected);

    return $id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @global object
 * @param object $linkgrade
 * @return bool
 */
function linkgrade_update_instance($linkgrade) {
    global $DB, $CFG;

    $linkgrade->name = get_linkgrade_name($linkgrade);
    $linkgrade->timemodified = time();
    $linkgrade->id = $linkgrade->instance;

    $url = $CFG->wwwroot . "/local/rating_item/view_rating_item.php?courseid=".$linkgrade->course;
    $linkgrade->intro = html_writer::link($url, $linkgrade->name);
    
    $completiontimeexpected = !empty($linkgrade->completionexpected) ? $linkgrade->completionexpected : null;
    \core_completion\api::update_completion_date_event($linkgrade->coursemodule, 'linkgrade', $linkgrade->id, $completiontimeexpected);

    return $DB->update_record("linkgrade", $linkgrade);
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @global object
 * @param int $id
 * @return bool
 */
function linkgrade_delete_instance($id) {
    global $DB;

    if (! $linkgrade = $DB->get_record("linkgrade", array("id"=>$id))) {
        return false;
    }

    $result = true;

    $cm = get_coursemodule_from_instance('linkgrade', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'linkgrade', $linkgrade->id, null);

    if (! $DB->delete_records("linkgrade", array("id"=>$linkgrade->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function linkgrade_get_coursemodule_info($coursemodule) {
    global $DB;

    error_log("entro en info");
    $linkgrade = $DB->get_record('linkgrade', array('id'=>$coursemodule->instance), 'id, name, intro, introformat');
    if ($linkgrade) {
        error_log("entro en primer if");
        if (empty($linkgrade->name)) {
            error_log("entro en segundo if");
            // linkgrade name missing, fix it
            $linkgrade->name = "linkgrade{$linkgrade->id}";
            $DB->set_field('linkgrade', 'name', $linkgrade->name, array('id'=>$linkgrade->id));
        }
        error_log(print_r($linkgrade, true));
        $info = new cached_cm_info();
        
        // no filtering hre because this info is cached and filtered later
        $info->content = format_module_intro('linkgrade', $linkgrade, $coursemodule->id, false);
        $info->name  = $linkgrade->name;
        return $info;
    } else {
        error_log("entro en segundo if grade");
        return null;
    }
}

/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function linkgrade_reset_userdata($data) {

    // Any changes to the list of dates that needs to be rolled should be same during course restore and course reset.
    // See MDL-9367.

    return array();
}

function linkgrade_check_updates_since(cm_info $cm, $from, $filter = array()) {
    error_log("entro en since");
    $updates = course_check_module_updates_since($cm, $from, array(), $filter);
    return $updates;
}

/**
 * @uses FEATURE_IDNUMBER
 * @uses FEATURE_GROUPS
 * @uses FEATURE_GROUPINGS
 * @uses FEATURE_MOD_INTRO
 * @uses FEATURE_COMPLETION_TRACKS_VIEWS
 * @uses FEATURE_GRADE_HAS_GRADE
 * @uses FEATURE_GRADE_OUTCOMES
 * @param string $feature FEATURE_xx constant for requested feature
 * @return bool|null True if module supports feature, false if not, null if doesn't know
 */
function linkgrade_supports($feature) {
    switch($feature) {
        case FEATURE_IDNUMBER:                return false;
        case FEATURE_GROUPS:                  return false;
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_NO_VIEW_LINK:            return true;

        default: return null;
    }
}