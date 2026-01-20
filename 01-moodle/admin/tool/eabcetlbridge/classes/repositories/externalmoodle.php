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

namespace tool_eabcetlbridge\repositories;

use tool_eabcetlbridge\request;

/**
 * External Moodle
 *
 * @package   tool_eabcetlbridge
 * @category  strategies
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class externalmoodle {

    /**
     * Get the external course ID from the external Moodle system.
     *
     * @param string $courseshortname The short name of the course.
     * @return int|false The external course ID if found, false otherwise.
     */
    protected static function get_external_courseid($courseshortname) {
        $payload = [
            'wsfunction' => 'core_course_get_courses_by_field',
            'moodlewsrestformat' => 'json',
            'field' => 'shortname',
            'value' => $courseshortname
        ];
        $request = new request(request::GET, true, false, true);
        $request = $request->request($payload);
        $externalcourseid = false;
        if ($request) {
            $courses = $request->courses ?? null;
            if ($courses) {
                $externalcourseid = $request->courses[0]->id;
            }
        }
        return $externalcourseid;
    }

    /**
     * Retrieves a mapping of external user IDs to their usernames from the external Moodle system.
     *
     * @param int $externalcourseid The ID of the course in the external Moodle system.
     * @return array A mapping of external user IDs to their usernames.
     */
    protected static function get_external_usernames($externalcourseid) {
        $payload = [
            'wsfunction' => 'core_enrol_get_enrolled_users',
            'moodlewsrestformat' => 'json',
            'courseid' => $externalcourseid
        ];
        $request = new request(request::GET, true, false, true);
        $request = $request->request($payload);

        $users = [];
        if ($request) {
            foreach ($request as $user) {
                $users[$user->id] = $user->username;
            }
        }
        return $users;
    }

    /**
     * Retrieves the grades for a given course and user mapping.
     *
     * Retrieves the grades for a given course and user mapping. The grades are
     * filtered to only include those with an item type of 'course', a raw grade
     * of 75 or higher, and a valid user ID in the given mapping.
     *
     * @param int $externalcourseid The ID of the course in the external Moodle system.
     * @param array $externalusermap A mapping of external user IDs to their usernames.
     * @return array An array containing 'headers' and 'usergrades'.
     */
    protected static function get_external_grades($externalcourseid, $externalusermap) {
        $payload = [
            'wsfunction' => 'gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'courseid' => $externalcourseid
        ];
        $request = new request(request::GET, true, false, true);
        $response = $request->request($payload);

        $allitemnames = [];
        $usergradesdata = [];

        if ($response && !empty($response->usergrades)) {
            foreach ($response->usergrades as $usergrade) {
                $userid = $usergrade->userid ?? null;
                if (!$userid || !isset($externalusermap[$userid])) {
                    continue;
                }

                $username = $externalusermap[$userid];
                $currentusergrades = [];

                foreach ($usergrade->gradeitems as $gradeitem) {
                    // Always check for the course total by its type first.
                    if (isset($gradeitem->itemtype) && $gradeitem->itemtype == 'course') {
                        $allitemnames['course'] = true;
                        $currentusergrades['course'] = $gradeitem->graderaw ?? '';
                    } else if (!empty($gradeitem->itemname)) {
                        // For all other grade items, use their name.
                        $itemname = $gradeitem->itemname;
                        $allitemnames[$itemname] = true; // Use keys for uniqueness.
                        $currentusergrades[$itemname] = $gradeitem->graderaw ?? '';
                    }
                }

                if (!empty($currentusergrades)) {
                    $usergradesdata[] = [
                        'username' => $username,
                        'grades' => $currentusergrades
                    ];
                }
            }
        }

        return [
            'headers' => array_keys($allitemnames),
            'usergrades' => $usergradesdata
        ];
    }

    /**
     * Retrieves the grades for a given course short name from the external Moodle system.
     *
     * @param string $courseshortname The short name of the course.
     * @return array An array containing 'headers' and 'usergrades'.
     */
    public static function get_grades_by_local_course_shortname($courseshortname) {

        $externalcourseid = self::get_external_courseid($courseshortname);
        mtrace("[externalmoodle] External course ID: {$externalcourseid}");
        if (!$externalcourseid) {
            return null;
        }

        $externalusermap = self::get_external_usernames($externalcourseid);
        mtrace("[externalmoodle] External user map: " . count($externalusermap));
        if (!$externalusermap) {
            return null;
        }

        $grades = self::get_external_grades($externalcourseid, $externalusermap);
        mtrace("[externalmoodle] External grades: " . count($grades));
        return $grades;

    }

    /**
     * Retrieves the grades for a given course short name from the external Moodle system.
     *
     * @param string $courseshortname The short name of the course.
     * @param int $page The page number to retrieve.
     * @param int $pagesize The number of users per page.
     */
    public static function get_grades_by_local_course_shortname_with_pagination(
            $courseshortname,
            $page = 0,
            $pagesize = 1000
        ) {

        $allitemnames = [];
        $usergradesdata = [];
        $pagination = null;

        $payload = [
            'wsfunction' => 'tool_eabcetlexporter_gradereport_user_get_grade_items',
            'moodlewsrestformat' => 'json',
            'shortname' => $courseshortname,
            'page' => $page,
            'pagesize' => $pagesize
        ];
        $request = new request(request::GET, true, false, true);
        $response = $request->request($payload);

        if ($response && !empty($response->usergrades)) {
            $pagination = $response->pagination;
            if (empty($pagination)) {
                return [
                    'headers' => array_keys($allitemnames),
                    'usergrades' => $usergradesdata,
                    'pagination' => null
                ];
            }

            foreach ($response->usergrades as $usergrade) {

                $username = $usergrade->username ?? false;
                if (!$username) {
                    continue;
                }
                $currentusergrades = [];

                foreach ($usergrade->gradeitems as $gradeitem) {
                    // Always check for the course total by its type first.
                    if (isset($gradeitem->itemtype) && $gradeitem->itemtype == 'course') {
                        $allitemnames['course'] = true;
                        $currentusergrades['course'] = $gradeitem->graderaw ?? '';
                    } else if (!empty($gradeitem->itemname)) {
                        // For all other grade items, use their name.
                        $itemname = $gradeitem->itemname;
                        $allitemnames[$itemname] = true; // Use keys for uniqueness.
                        $currentusergrades[$itemname] = $gradeitem->graderaw ?? '';
                    }
                }

                if (!empty($currentusergrades)) {
                    $usergradesdata[] = [
                        'username' => $username,
                        'grades' => $currentusergrades
                    ];
                }
            }
        }

        return [
            'headers' => array_keys($allitemnames),
            'usergrades' => $usergradesdata,
            'pagination' => $pagination
        ];
    }


}
