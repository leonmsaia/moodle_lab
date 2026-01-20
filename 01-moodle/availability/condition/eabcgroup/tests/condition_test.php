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
 * Unit tests for the condition.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use availability_eabcgroup\condition;

/**
 * Unit tests for the condition.
 *
 * @package availability_eabcgroup
 * @copyright 2014 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class availability_eabcgroup_condition_testcase extends advanced_testcase {
    /**
     * Load required classes.
     */
    public function setUp() {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using condition.
     */
    public function test_usage() {
        global $CFG, $USER;
        $this->resetAfterTest();
        $CFG->enableavailability = true;

        // Erase static cache before test.
        condition::wipe_static_cache();

        // Make a test course and user.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $info = new \core_availability\mock_info($course, $user->id);

        // Make 2 test eabcgroups, one in a eabcgrouping and one not.
        $eabcgrouping = $generator->create_eabcgrouping(array('courseid' => $course->id));
        $eabcgroup1 = $generator->create_eabcgroup(array('courseid' => $course->id, 'name' => 'G1!'));
        eabcgroups_assign_eabcgrouping($eabcgrouping->id, $eabcgroup1->id);
        $eabcgroup2 = $generator->create_eabcgroup(array('courseid' => $course->id, 'name' => 'G2!'));

        // Do test (not in eabcgroup).
        $cond = new condition((object)array('id' => (int)$eabcgroup1->id));

        // Check if available (when not available).
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $this->assertRegExp('~You belong to.*G1!~', $information);
        $this->assertTrue($cond->is_available(true, $info, true, $user->id));

        // Add user to eabcgroups and refresh cache.
        eabcgroups_add_member($eabcgroup1, $user);
        eabcgroups_add_member($eabcgroup2, $user);
        get_fast_modinfo($course->id, 0, true);

        // Recheck.
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $this->assertRegExp('~do not belong to.*G1!~', $information);

        // Check eabcgroup 2 works also.
        $cond = new condition((object)array('id' => (int)$eabcgroup2->id));
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));

        // What about an 'any eabcgroup' condition?
        $cond = new condition((object)array());
        $this->assertTrue($cond->is_available(false, $info, true, $user->id));
        $this->assertFalse($cond->is_available(true, $info, true, $user->id));
        $information = $cond->get_description(false, true, $info);
        $this->assertRegExp('~do not belong to any~', $information);

        // Admin user doesn't belong to a eabcgroup, but they can access it
        // either way (positive or NOT).
        $this->setAdminUser();
        $this->assertTrue($cond->is_available(false, $info, true, $USER->id));
        $this->assertTrue($cond->is_available(true, $info, true, $USER->id));

        // eabcgroup that doesn't exist uses 'missing' text.
        $cond = new condition((object)array('id' => $eabcgroup2->id + 1000));
        $this->assertFalse($cond->is_available(false, $info, true, $user->id));
        $information = $cond->get_description(false, false, $info);
        $this->assertRegExp('~You belong to.*\(Missing eabcgroup\)~', $information);
    }

    /**
     * Tests the constructor including error conditions. Also tests the
     * string conversion feature (intended for debugging only).
     */
    public function test_constructor() {
        // Invalid id (not int).
        $structure = (object)array('id' => 'bourne');
        try {
            $cond = new condition($structure);
            $this->fail();
        } catch (coding_exception $e) {
            $this->assertContains('Invalid ->id', $e->getMessage());
        }

        // Valid (with id).
        $structure->id = 123;
        $cond = new condition($structure);
        $this->assertEquals('{eabcgroup:#123}', (string)$cond);

        // Valid (no id).
        unset($structure->id);
        $cond = new condition($structure);
        $this->assertEquals('{eabcgroup:any}', (string)$cond);
    }

    /**
     * Tests the save() function.
     */
    public function test_save() {
        $structure = (object)array('id' => 123);
        $cond = new condition($structure);
        $structure->type = 'eabcgroup';
        $this->assertEquals($structure, $cond->save());

        $structure = (object)array();
        $cond = new condition($structure);
        $structure->type = 'eabcgroup';
        $this->assertEquals($structure, $cond->save());
    }

    /**
     * Tests the update_dependency_id() function.
     */
    public function test_update_dependency_id() {
        $cond = new condition((object)array('id' => 123));
        $this->assertFalse($cond->update_dependency_id('frogs', 123, 456));
        $this->assertFalse($cond->update_dependency_id('eabcgroups', 12, 34));
        $this->assertTrue($cond->update_dependency_id('eabcgroups', 123, 456));
        $after = $cond->save();
        $this->assertEquals(456, $after->id);
    }

    /**
     * Tests the filter_users (bulk checking) function. Also tests the SQL
     * variant get_user_list_sql.
     */
    public function test_filter_users() {
        global $DB;
        $this->resetAfterTest();

        // Erase static cache before test.
        condition::wipe_static_cache();

        // Make a test course and some users.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, $roleids['editingteacher']);
        $allusers = array($teacher->id => $teacher);
        $students = array();
        for ($i = 0; $i < 3; $i++) {
            $student = $generator->create_user();
            $students[$i] = $student;
            $generator->enrol_user($student->id, $course->id, $roleids['student']);
            $allusers[$student->id] = $student;
        }
        $info = new \core_availability\mock_info($course);

        // Make test eabcgroups.
        $eabcgroup1 = $generator->create_eabcgroup(array('courseid' => $course->id));
        $eabcgroup2 = $generator->create_eabcgroup(array('courseid' => $course->id));

        // Assign students to eabcgroups as follows (teacher is not in a eabcgroup):
        // 0: no eabcgroups.
        // 1: in eabcgroup 1.
        // 2: in eabcgroup 2.
        eabcgroups_add_member($eabcgroup1, $students[1]);
        eabcgroups_add_member($eabcgroup2, $students[2]);

        // Test 'any eabcgroup' condition.
        $checker = new \core_availability\capability_checker($info->get_context());
        $cond = new condition((object)array());
        $result = array_keys($cond->filter_user_list($allusers, false, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[1]->id, $students[2]->id);
        $this->assertEquals($expected, $result);

        // Test it with get_user_list_sql.
        list ($sql, $params) = $cond->get_user_list_sql(false, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        // Test NOT version (note that teacher can still access because AAG works
        // both ways).
        $result = array_keys($cond->filter_user_list($allusers, true, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[0]->id);
        $this->assertEquals($expected, $result);

        // Test with get_user_list_sql.
        list ($sql, $params) = $cond->get_user_list_sql(true, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        // Test specific eabcgroup.
        $cond = new condition((object)array('id' => (int)$eabcgroup1->id));
        $result = array_keys($cond->filter_user_list($allusers, false, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[1]->id);
        $this->assertEquals($expected, $result);

        list ($sql, $params) = $cond->get_user_list_sql(false, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);

        $result = array_keys($cond->filter_user_list($allusers, true, $info, $checker));
        ksort($result);
        $expected = array($teacher->id, $students[0]->id, $students[2]->id);
        $this->assertEquals($expected, $result);

        list ($sql, $params) = $cond->get_user_list_sql(true, $info, true);
        $result = $DB->get_fieldset_sql($sql, $params);
        sort($result);
        $this->assertEquals($expected, $result);
    }
}
