<?php
// Seed demo data: courses + users + enrolments.
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/enrol/manual/locallib.php');

cli_writeln("=== Local demo seeder: courses + users + enrolments ===");

// Check site is installed.
if (empty($CFG->rolesactive)) {
    cli_error("Moodle is not fully installed yet. Finish the web installation first.");
}

// Get student role.
global $DB;

$studentrole = $DB->get_record('role', ['shortname' => 'student'], '*', IGNORE_MISSING);
if (!$studentrole) {
    cli_error("Could not find 'student' role (shortname = student).");
}

// -------------------------------------------------------------------------
// 1) Create demo courses
// -------------------------------------------------------------------------

$democoursedata = [
    ['shortname' => 'DEMO_COURSE_1', 'fullname' => 'Demo Course 1'],
    ['shortname' => 'DEMO_COURSE_2', 'fullname' => 'Demo Course 2'],
    ['shortname' => 'DEMO_COURSE_3', 'fullname' => 'Demo Course 3'],
    ['shortname' => 'DEMO_COURSE_4', 'fullname' => 'Demo Course 4'],
];

$createdcourses = [];

foreach ($democoursedata as $data) {
    if ($existing = $DB->get_record('course', ['shortname' => $data['shortname']])) {
        cli_writeln("Course already exists: {$data['shortname']} (id={$existing->id})");
        $createdcourses[] = $existing;
        continue;
    }

    $course = new stdClass();
    $course->fullname  = $data['fullname'];
    $course->shortname = $data['shortname'];
    $course->category  = 1; // Default category "Miscellaneous".
    $course->summary   = 'Demo course created by local_user_reporter_chg seeder.';
    $course->visible   = 1;

    $newcourse = create_course($course);
    cli_writeln("Created course: {$newcourse->shortname} (id={$newcourse->id})");
    $createdcourses[] = $newcourse;
}

if (empty($createdcourses)) {
    cli_error("No courses available to use for enrolments.");
}

// -------------------------------------------------------------------------
// 2) Create demo users
// -------------------------------------------------------------------------

$createdusers = [];

for ($i = 1; $i <= 50; $i++) {
    $username = 'demo_user_' . $i;

    if ($existinguser = $DB->get_record('user', ['username' => $username])) {
        cli_writeln("User already exists: {$username} (id={$existinguser->id})");
        $createdusers[] = $existinguser;
        continue;
    }

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

    $userid = user_create_user($user, false, false);
    $user->id = $userid;

    cli_writeln("Created user: {$user->username} (id={$user->id})");
    $createdusers[] = $user;
}

if (empty($createdusers)) {
    cli_error("No users created or found to enrol.");
}

// -------------------------------------------------------------------------
// 3) Enrol all demo users into all demo courses (manual enrolment)
// -------------------------------------------------------------------------

$manualplugin = enrol_get_plugin('manual');
if (!$manualplugin) {
    cli_error("Manual enrolment plugin is not enabled.");
}

foreach ($createdcourses as $course) {
    // Find or create a manual enrol instance.
    $instances = enrol_get_instances($course->id, true);
    $manualinstance = null;

    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            $manualinstance = $instance;
            break;
        }
    }

    if (!$manualinstance) {
        $instanceid = $manualplugin->add_default_instance($course);
        $manualinstance = $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
        cli_writeln("Created manual enrol instance for course {$course->shortname} (id={$course->id}).");
    }

    foreach ($createdusers as $user) {
        // Check if already enrolled.
        $already = $DB->record_exists('user_enrolments', [
            'userid' => $user->id,
            'enrolid' => $manualinstance->id
        ]);

        if ($already) {
            cli_writeln("User {$user->username} already enrolled in {$course->shortname}.");
            continue;
        }

        $manualplugin->enrol_user($manualinstance, $user->id, $studentrole->id);
        cli_writeln("Enrolled user {$user->username} to course {$course->shortname}.");
    }
}

cli_writeln("=== Seeding completed successfully. ===");
