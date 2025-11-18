<?php
/**
 * Demo data seeder for the local_user_reporter_chg plugin.
 *
 * This CLI script creates:
 * - Four demo courses
 * - Fifty demo users
 * - Manual enrolments of all users into all demo courses
 *
 * The script is meant exclusively for development/testing environments
 * to quickly populate a clean Moodle installation with synthetic data.
 * It can be executed from the command line only.
 *
 * Usage example:
 *     $ php local/user_reporter_chg/cli/demo_seeder.php
 *
 * Requirements:
 * - Moodle must be installed and configured
 * - CLI execution must be allowed
 * - The manual enrolment plugin must be enabled
 *
 * @package     local_user_reporter_chg
 * @subpackage  cli
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');

cli_writeln("=== Local demo seeder: courses + users + enrolments ===");

// -----------------------------------------------------------------------------
// Safety checks
// -----------------------------------------------------------------------------

/**
 * Ensure Moodle installation is completed before running the seeder.
 * The presence of $CFG->rolesactive indicates a fully initialized environment.
 */
if (empty($CFG->rolesactive)) {
    cli_error("Moodle is not fully installed yet. Finish the web installation first.");
}

global $DB;

/**
 * Retrieve the student role definition.
 * Required for assigning the correct role during enrolment.
 */
$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', IGNORE_MISSING);
if (!$studentrole) {
    cli_error("Could not find 'student' role (shortname = student).");
}

// -----------------------------------------------------------------------------
// 1) Create demo courses
// -----------------------------------------------------------------------------

/**
 * List of demo courses to create.
 * Each course is identified by shortname + fullname.
 */
$democoursedata = [
    ['shortname' => 'DEMO_COURSE_1', 'fullname' => 'Demo Course 1'],
    ['shortname' => 'DEMO_COURSE_2', 'fullname' => 'Demo Course 2'],
    ['shortname' => 'DEMO_COURSE_3', 'fullname' => 'Demo Course 3'],
    ['shortname' => 'DEMO_COURSE_4', 'fullname' => 'Demo Course 4'],
];

$createdcourses = [];

foreach ($democoursedata as $data) {

    /**
     * Check for an existing course with the same shortname.
     * If found, reuse it instead of creating a duplicate.
     */
    if ($existing = $DB->get_record('course', ['shortname' => $data['shortname']])) {
        cli_writeln("Course already exists: {$data['shortname']} (id={$existing->id})");
        $createdcourses[] = $existing;
        continue;
    }

    // Prepare a new course record.
    $course = new stdClass();
    $course->fullname  = $data['fullname'];
    $course->shortname = $data['shortname'];
    $course->category  = 1; // Default category "Miscellaneous".
    $course->summary   = 'Demo course created by local_user_reporter_chg seeder.';
    $course->visible   = 1;

    // Create the course via Moodle API.
    $newcourse = create_course($course);
    cli_writeln("Created course: {$newcourse->shortname} (id={$newcourse->id})");
    $createdcourses[] = $newcourse;
}

if (empty($createdcourses)) {
    cli_error("No courses available to use for enrolments.");
}

// -----------------------------------------------------------------------------
// 2) Create demo users
// -----------------------------------------------------------------------------

/**
 * Loop to generate fifty demo user accounts.
 */
$createdusers = [];

for ($i = 1; $i <= 50; $i++) {
    $username = 'demo_user_' . $i;

    /**
     * Reuse the user if already existing.
     */
    if ($existinguser = $DB->get_record('user', ['username' => $username])) {
        cli_writeln("User already exists: {$username} (id={$existinguser->id})");
        $createdusers[] = $existinguser;
        continue;
    }

    // Prepare a new user object.
    $user = new stdClass();
    $user->username   = $username;
    $user->password   = 'DemoUser123!'; // user_create_user will hash this.
    $user->firstname  = 'Demo';
    $user->lastname   = 'User ' . $i;
    $user->email      = $username . '@example.com';
    $user->auth       = 'manual';
    $user->confirmed  = 1;
    $user->mnethostid = $CFG->mnet_localhost_id ?? 1;
    $user->city       = 'Demo City';
    $user->country    = 'AR'; // Argentina
    $user->lang       = 'es';
    $user->timecreated = time();

    // Create user in Moodle.
    $userid = user_create_user($user, false, false);
    $user->id = $userid;

    cli_writeln("Created user: {$user->username} (id={$user->id})");
    $createdusers[] = $user;
}

if (empty($createdusers)) {
    cli_error("No users created or found to enrol.");
}

// -----------------------------------------------------------------------------
// 3) Enrol all demo users into all demo courses
// -----------------------------------------------------------------------------

/**
 * Retrieve the manual enrolment plugin instance.
 */
$manualplugin = enrol_get_plugin('manual');
if (!$manualplugin) {
    cli_error("Manual enrolment plugin is not enabled.");
}

/**
 * For each course:
 * - Ensure a manual enrolment instance exists
 * - Enrol all demo users
 */
foreach ($createdcourses as $course) {
    
    // Try to find an existing manual enrol instance.
    $instances = enrol_get_instances($course->id, true);
    $manualinstance = null;

    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    // Create a new manual instance if none exists.
    if (!$manualinstance) {
        $instanceid = $manualplugin->add_default_instance($course);
        $manualinstance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        cli_writeln("Created manual enrol instance for course {$course->shortname} (id={$course->id}).");
    }

    // Enrol users into the course.
    foreach ($createdusers as $user) {
        
        // Skip if the user is already enrolled.
        $already = $DB->record_exists('user_enrolments', [
            'userid' => $user->id,
            'enrolid' => $manualinstance->id
        ]);

        if ($already) {
            cli_writeln("User {$user->username} already enrolled in {$course->shortname}.");
            continue;
        }

        // Enrol using the manual plugin.
        $manualplugin->enrol_user($manualinstance, $user->id, $studentrole->id);
        cli_writeln("Enrolled user {$user->username} to course {$course->shortname}.");
    }
}

cli_writeln("=== Seeding completed successfully. ===");
