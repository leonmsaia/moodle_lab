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

namespace tool_eabcetlbridge\completion;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/completionlib.php');
use tool_eabcetlbridge\local_external_db_connection as external_db_connection;
use tool_eabcetlbridge\completion\activity_mapper;

use core\exception\moodle_exception;
use completion_info;
use moodle_database;
use stdClass;

/**
 * Completion migrator for transferring activity completions from Moodle 3.5 to 4.5
 *
 * This class handles the migration of user activity completions while preserving
 * the original completion dates and states.
 *
 * @package    local_restoration
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion_migrator {

    /** @var bool Enable verbose output */
    private $verbose = false;

    /** @var bool Dry run mode - don't actually write data */
    private $dry_run = false;

    /** @var bool Force overwrite existing completions */
    private $force = false;

    /** @var object Output handler for logging */
    private $output_handler = null;

    /** @var bool Migrate course completion date */
    private $migrate_course_date = false;

    /** @var bool Force course completion date */
    private $force_course_date = false;

    /** @var bool Clear cache after migration */
    private $clear_cache = true;

    /** @var array Statistics for reporting */
    private $stats = [
        'courses_processed' => 0,
        'activities_found_35' => 0,
        'activities_migrated' => 0,
        'activities_skipped' => 0,
        'activities_protected' => 0,
        'activities_not_found' => 0,
        'errors' => 0,
        'courses_migrated' => 0,
        'courses_skipped' => 0,
        'courses_notvalid' => 0,
    ];

    /**
     * Constructor
     *
     * @param array $options Options array with keys: verbose, dry_run, force, output_handler
     */
    public function __construct($options = []) {
        $this->verbose = isset($options['verbose']) ? $options['verbose'] : false;
        $this->dry_run = isset($options['dry_run']) ? $options['dry_run'] : false;
        $this->force = isset($options['force']) ? $options['force'] : false;
        $this->output_handler = isset($options['output_handler']) ? $options['output_handler'] : null;
        $this->migrate_course_date = isset($options['migrate_course_date']) ? $options['migrate_course_date'] : false;
        $this->force_course_date = isset($options['force_course_date']) ? $options['force_course_date'] : false;
        $this->clear_cache = $options['clear_cache'] ?? true;
    }

    /**
     * Output a message (respects verbose mode)
     *
     * @param string $message Message to output
     * @param bool $force Force output even if not verbose
     */
    private function output($message, $force = false) {
        if ($this->verbose || $force) {
            if ($this->output_handler) {
                $this->output_handler->output($message);
            } else {
                echo $message . "\n";
            }
        }
    }

    /**
     * Migrate all completions for a user
     *
     * @param string $username Username to migrate
     * @return array Statistics about the migration
     * @throws moodle_exception If user not found or connection fails
     */
    public function migrate_user_completions($username) {
        global $DB;

        $this->output("[" . date('Y-m-d H:i:s') . "] Starting completion migration for user: {$username}");

        // Get Moodle 3.5 connection
        $this->output("[" . date('Y-m-d H:i:s') . "] Connecting to Moodle 3.5...");
        $db35 = external_db_connection::get_moodle35_connection();

        if (!external_db_connection::validate_connection($db35)) {
            throw new moodle_exception('error', 'local_restoration', '', null,
                'Failed to establish connection to Moodle 3.5 database');
        }

        $this->output("[" . date('Y-m-d H:i:s') . "] ✓ Connection successful");

        // Get user from both systems
        $user45 = $DB->get_record('user', ['username' => $username], '*', MUST_EXIST);
        $userid35 = external_db_connection::get_user_id_by_username($db35, $username);

        if (!$userid35) {
            throw new moodle_exception('error', 'local_restoration', '', null,
                "User '{$username}' not found in Moodle 3.5");
        }

        $this->output("[" . date('Y-m-d H:i:s') . "] User found - ID local: {$user45->id}, ID origin: {$userid35}");

        // Get user's courses in Moodle 4.5
        $courses = $this->get_user_courses($user45->id);
        $this->output("[" . date('Y-m-d H:i:s') . "] Courses to process: " . count($courses));

        if ($this->dry_run) {
            $this->output("[" . date('Y-m-d H:i:s') . "] Dry run mode enabled - no changes will be made");
        }

        // Process each course
        foreach ($courses as $course45) {
            $this->process_course($db35, $course45, $user45->id, $userid35);
        }

        // Close connection
        external_db_connection::close_connection($db35);

        // Output final report
        $this->output_final_report();

        $this->output("[" . date('Y-m-d H:i:s') . "] Completed completion migration for user: {$username}");

        return $this->stats;
    }

    /**
     * Get all courses a user is enrolled in
     *
     * @param int $userid User ID
     * @return array Array of course objects
     */
    private function get_user_courses($userid) {
        global $DB;

        $sql = "SELECT DISTINCT c.*
                FROM {course} c
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid
                  AND c.id != :siteid
                ORDER BY c.shortname";

        return $DB->get_records_sql($sql, ['userid' => $userid, 'siteid' => SITEID]);
    }

    /**
     * Process completions for a single course
     *
     * @param moodle_database $db35 Connection to Moodle 3.5
     * @param stdClass $course45 Course object from Moodle 4.5
     * @param int $userid45 User ID in Moodle 4.5
     * @param int $userid35 User ID in Moodle 3.5
     */
    private function process_course($db35, $course45, $userid45, $userid35) {
        $this->output("Processing course: {$course45->shortname} (ID: {$course45->id})");

        // Find corresponding course in Moodle 3.5
        $courseid35 = external_db_connection::get_course_id_by_shortname($db35, $course45->shortname);

        if (!$courseid35) {
            $this->output("  → Course not found in Moodle 3.5 - skipping");
            return;
        }

        $this->output("  → Found in Moodle 3.5 (ID: {$courseid35})");

        // Get completions from Moodle 3.5
        $completions = activity_mapper::get_completions_from_35($db35, $userid35, $courseid35);

        if (empty($completions)) {
            $this->output("  → No completions found in Moodle 3.5");
            return;
        }

        $this->output("  → Completions in 3.5: " . count($completions));

        $this->stats['courses_processed']++;
        $this->stats['activities_found_35'] += count($completions);

        // Get completion_info for this course
        $completion = new completion_info($course45);

        $enrollmentdate = activity_mapper::get_user_enrollment_date($userid45, $course45->id);

        // Process each completion
        foreach ($completions as $comp) {
            $this->process_activity_completion($completion, $course45, $userid45, $comp, $enrollmentdate);
        }

        // Process Course Completion Date if requested.
        if ($this->migrate_course_date) {
            $this->process_course_completion($db35, $course45, $userid45, $courseid35, $userid35, $enrollmentdate);
        }

    }

    /**
     * Determine if a completion should be migrated based on state comparison
     *
     * Hierarchy: COMPLETION_COMPLETE_PASS (2) > COMPLETION_COMPLETE (1) > COMPLETION_COMPLETE_FAIL (3)
     *
     * @param int $existing_state State in Moodle 4.5
     * @param int $new_state State from Moodle 3.5
     * @return bool True if should migrate, false if should protect existing
     */
    private function should_migrate_completion($existing_state, $new_state) {
        // Define state priority (higher is better)
        $state_priority = [
            COMPLETION_INCOMPLETE => 0,      // 0
            COMPLETION_COMPLETE => 5,        // 1 - completed but no grade
            COMPLETION_COMPLETE_PASS => 10,  // 2 - completed and passed
            COMPLETION_COMPLETE_FAIL => 3    // 3 - completed but failed
        ];

        $existing_priority = isset($state_priority[$existing_state]) ? $state_priority[$existing_state] : 0;
        $new_priority = isset($state_priority[$new_state]) ? $state_priority[$new_state] : 0;

        // Only migrate if new state is better than or equal to existing
        // This prevents overwriting Pass with Fail
        return $new_priority >= $existing_priority;
    }

    /**
     * Process a single activity completion
     *
     * @param completion_info $completion Completion info object
     * @param stdClass $course45 Course object
     * @param int $userid45 User ID in Moodle 4.5
     * @param array $comp Completion data from Moodle 3.5
     * @param int|null $enrollmentdate Enrollment date
     */
    private function process_activity_completion(
            $completion,
            $course45,
            $userid45,
            $comp,
            $enrollmentdate = null
        ) {

        // VALIDACIÓN CRÍTICA DE FECHAS
        // Si la actividad se completó en 3.5 ANTES de que el usuario se matriculara en 4.5,
        // significa que pertenece a un intento previo (recertificación). NO debemos migrarla
        // a la matriculación actual activa.
        if ($enrollmentdate && $comp['timemodified'] < $enrollmentdate) {
            $date_act = date('Y-m-d', $comp['timemodified']);
            $date_enrol = date('Y-m-d', $enrollmentdate);

            $this->output("  ⊘ SKIPPED (Historical): {$comp['activity_name']} completed on {$date_act}, but user enrolled on {$date_enrol}. Preserving current attempt.");
            $this->stats['activities_protected']++;
            return;
        }

        // Find the activity in Moodle 4.5
        $cm45 = activity_mapper::find_activity_by_name(
            $course45->id,
            $comp['module_type'],
            $comp['activity_name']
        );

        if (!$cm45) {
            $this->output("  ✗ {$comp['module_type']} '{$comp['activity_name']}' - Not found in Moodle 4.5");
            $this->stats['activities_not_found']++;
            return;
        }

        // Check if already completed
        $existing = activity_mapper::get_existing_completion($cm45->id, $userid45);

        if ($existing && !$this->force) {
            // Check if we should migrate based on state comparison
            if (!$this->should_migrate_completion($existing->completionstate, $comp['completionstate'])) {
                $existing_state_str = $this->get_completion_state_name($existing->completionstate);
                $new_state_str = $this->get_completion_state_name($comp['completionstate']);
                $existing_date = date('Y-m-d H:i:s', $existing->timemodified);

                $this->output(
                    "  🛡️ {$comp['module_type']} '{$comp['activity_name']}' - Protected (4.5: {$existing_state_str}, 3.5: {$new_state_str})"
                );
                $this->stats['activities_protected']++;
                return;
            }

            // Same or better state - can migrate with upgraded status
            $existing_state_str = $this->get_completion_state_name($existing->completionstate);
            $new_state_str = $this->get_completion_state_name($comp['completionstate']);

            if ($existing->completionstate === $comp['completionstate']) {
                // Same state, skip to preserve 4.5 date
                $existing_date = date('Y-m-d H:i:s', $existing->timemodified);
                $this->output("  ⊘ {$comp['module_type']} '{$comp['activity_name']}' - Same state ({$existing_state_str})");
                $this->stats['activities_skipped']++;
                return;
            }
            // If we reach here, new state is better - will upgrade below
        }

        // Mark as completed with original date
        try {
            $date_str = date('Y-m-d H:i:s', $comp['timemodified']);
            $state_str = $this->get_completion_state_name($comp['completionstate']);
            $is_upgrade = $existing && $existing->completionstate !== $comp['completionstate'];

            if ($this->dry_run) {
                $upgrade_msg = $is_upgrade ? " (would upgrade from {$existing_state_str})" : "";
                $this->output("  [DRY RUN] Would migrate: {$comp['module_type']} '{$comp['activity_name']}'{$upgrade_msg}");
                $this->output("             State: {$state_str} ({$comp['completionstate']})");
                $this->output("             Original date: {$date_str}");
            } else {
                $this->mark_activity_complete_with_date(
                    $completion,
                    $cm45,
                    $userid45,
                    $comp['completionstate'],
                    $comp['timemodified'],
                    $comp['viewed'],
                    $existing ? $existing->id : 0
                );

                if ($is_upgrade) {
                    $this->output("  ⬆️ {$comp['module_type']} '{$comp['activity_name']}' - Upgraded to {$state_str}");
                    $this->output("    Previous: {$existing_state_str}");
                } else {
                    $this->output("  ✓ {$comp['module_type']} '{$comp['activity_name']}'");
                }
                $this->output("    State: {$state_str} ({$comp['completionstate']})");
                $this->output("    Original date: {$date_str}");
            }

            $this->stats['activities_migrated']++;

        } catch (Exception $e) {
            $this->output("  ✗ Error migrating {$comp['module_type']} '{$comp['activity_name']}': " . $e->getMessage());
            $this->stats['errors']++;
        }
    }


    /**
     * Process course completion for a user
     *
     * 1. Get historical course completion from Moodle 3.5.
     * 2. Get course completion in 4.5.
     * 3. Get user enrollment date in Moodle 4.5.
     * 4. Validate historical completion date against enrollment date.
     * 5. Migration logic:
     *      - If course is already completed, update completion date using --force.
     *      - If completion record exists but is not marked as complete, set completion date.
     *      - If no completion record exists, create a new one.
     *
     * @param moodle_database $db35 Connection to Moodle 3.5
     * @param stdClass $course45 Course object from Moodle 4.5
     * @param int $userid45 User ID in Moodle 4.5
     * @param int $courseid35 Course ID in Moodle 3.5
     * @param int $userid35 User ID in Moodle 3.5
     * @param int $enrollmentdate Enrollment date in Moodle 4.5
     */
    private function process_course_completion($db35, $course45, $userid45, $courseid35, $userid35, $enrollmentdate = null) {
        global $DB;

        // 1. Get historical course completion from Moodle 3.5.
        $cc35 = activity_mapper::get_course_completion($db35, $userid35, $courseid35);

        if (!$cc35 || !$cc35->timecompleted) {
            $this->output("  ⊘ No historical course completion date found.");
            $this->stats['courses_notvalid']++;
            return;
        }

        $timestamp35 = $cc35->timecompleted;
        $date35str = date('Y-m-d H:i:s', $timestamp35);

        // 2. Get course completion in 4.5.
        $cc45 = activity_mapper::get_course_completion($DB, $userid45, $course45->id);

        // 3. Get user enrollment date in Moodle 4.5.
        if (!$enrollmentdate) {
            $this->output("  ⊘ User not enrolled in course in Moodle 4.5. Cannot migrate course completion.");
            $this->stats['courses_notvalid']++;
            return;
        }

        // 4. Validate historical completion date against enrollment date.
        if ($timestamp35 < $enrollmentdate) {
            $this->output("  ⊘ Historical completion date ({$date35str}) is before enrollment date. Cannot migrate course completion.");
            $this->stats['courses_notvalid']++;
            return;
        }

        // 5. Migration logic.
        if ($cc45 && $cc45->timecompleted > 0) {
            // Scenario: Course is already completed in Moodle 4.5.
            $shouldmigrate = $this->force || $this->force_course_date;
            $areequal = $cc45->timecompleted == $timestamp35;
            if ($areequal) {
                $this->stats['courses_notvalid']++;
                $this->output(" ⊘ Course with correct completion date on " . date('Y-m-d H:i:s', $cc45->timecompleted));
            } else if ($shouldmigrate) {
                $this->stats['courses_migrated']++;
                $this->update_course_completion_date($cc45, $timestamp35, "Forced update");
            } else {
                $this->output("  -- Course already completed on " . date('Y-m-d H:i:s', $cc45->timecompleted) . ". New completion date: " . date('Y-m-d H:i:s', $timestamp35) . ". Use --force or --force_course_date to overwrite.");
                $this->stats['courses_skipped']++;
            }
        } else if ($cc45) {
            // Scenario: Completion record exists but is not marked as complete.
            $this->stats['courses_migrated']++;
            $this->update_course_completion_date($cc45, $timestamp35, "Set completion");
        } else {
            // Scenario: No completion record exists in Moodle 4.5.
            $this->stats['courses_migrated']++;
            $this->create_course_completion_record($course45->id, $userid45, $timestamp35, $enrollmentdate);
        }
    }

    /**
     * Updates the course completion date in Moodle 4.5 for a given user and course
     *
     * @param stdClass $cc Course completion object
     * @param int $timestamp New timestamp for course completion
     * @param string $actionlabel Action label (e.g. "Forced update", "Set completion")
     */
    private function update_course_completion_date($cc, $timestamp, $actionlabel) {
        global $DB;
        $date_str = date('Y-m-d H:i:s', $timestamp);
        if ($this->dry_run) {
            $this->output("  [DRY RUN] Would {$actionlabel} course completion date to {$date_str}");
        } else {
            $cc->timecompleted = $timestamp;
            $DB->update_record('course_completions', $cc);
            $this->output("  ✓ {$actionlabel} course completion date to {$date_str}");
        }
    }

    /**
     * Marca el curso como completado SIN disparar eventos ni enviar correos.
     *
     * @param int $courseid ID del curso en Moodle 4.5
     * @param int $userid ID del usuario en Moodle 4.5
     * @param int $timestamp Fecha original de completado (Unix timestamp)
     * @param int $enrollmentdate Fecha de enrolamiento (Unix timestamp)
     */
    private function create_course_completion_record($courseid, $userid, $timestamp, $enrollmentdate) {
        global $DB;

        $date_str = date('Y-m-d H:i:s', $timestamp);

        if ($this->dry_run) {
            $this->output("  [DRY RUN] (SILENT) Would set course completion date to {$date_str}");
            return;
        }

        try {
            // 1. Preparar el objeto de datos
            $cc = new \stdClass();
            $cc->course = $courseid;
            $cc->userid = $userid;
            $cc->timecompleted = $timestamp;
            $cc->timeenrolled = $enrollmentdate;
            $cc->timestarted = $enrollmentdate;
            $cc->reaggregate = 0;

            $DB->insert_record('course_completions', $cc);

            // 3. GESTIÓN DE CACHÉ (Crítico para que el usuario vea el cambio)
            if ($this->clear_cache) {
                $coursecompletioncache = \core_cache\cache::make('core', 'coursecompletion');
                $coursecompletioncache->delete($userid . '_' . $courseid);
            }

            $this->output("  ✓ (SILENT) Created course completion date to {$date_str}");

        } catch (\Exception $e) {
            $this->output("  ✗ Error in silent completion: " . $e->getMessage());
            $this->stats['errors']++;
        }
    }

    /**
     * Mark an activity as complete with a specific date using native Moodle methods
     *
     * @param completion_info $completion Completion info object
     * @param stdClass $cm Course module object
     * @param int $userid User ID
     * @param int $completionstate Completion state (1=complete, 2=complete_pass, etc.)
     * @param int $timemodified Original timestamp from Moodle 3.5
     * @param int $viewed Whether activity was viewed (0 or 1)
     * @param int $existing_id Existing record ID (0 for new record)
     */
    private function mark_activity_complete_with_date($completion, $cm, $userid, $completionstate,
                                                      $timemodified, $viewed, $existing_id = 0) {
        // Prepare completion data object
        $data = new stdClass();
        $data->id = $existing_id;
        $data->coursemoduleid = $cm->id;
        $data->userid = $userid;
        $data->completionstate = $completionstate;
        $data->timemodified = $timemodified;  // Preserve original date
        $data->viewed = $viewed;
        $data->overrideby = null;

        // Use native Moodle method with bulk update flag for performance
        $completion->internal_set_data($cm, $data, true);
    }

    /**
     * Get human-readable name for completion state
     *
     * @param int $state Completion state
     * @return string State name
     */
    private function get_completion_state_name($state) {
        switch ($state) {
            case COMPLETION_INCOMPLETE:
                return 'Incomplete';
            case COMPLETION_COMPLETE:
                return 'Complete';
            case COMPLETION_COMPLETE_PASS:
                return 'Complete (Pass)';
            case COMPLETION_COMPLETE_FAIL:
                return 'Complete (Fail)';
            default:
                return 'Unknown';
        }
    }

    /**
     * Output final migration report
     */
    private function output_final_report() {
        $this->output("=== Final Report ===");
        $this->output("Courses processed: {$this->stats['courses_processed']}");
        $this->output("Activities found in 3.5: {$this->stats['activities_found_35']}");

        if ($this->dry_run) {
            $this->output("✓ Would migrate (dry run): {$this->stats['activities_migrated']}");
        } else {
            $this->output("✓ Migrated successfully: {$this->stats['activities_migrated']}");
        }

        $this->output("⊘ Already completed (skipped): {$this->stats['activities_skipped']}");

        if ($this->stats['activities_protected'] > 0) {
            $this->output("🛡️ Protected (better state in 4.5): {$this->stats['activities_protected']}");
        }

        $this->output("✗ Not found in 4.5: {$this->stats['activities_not_found']}");

        if ($this->stats['errors'] > 0) {
            $this->output("⚠ Errors: {$this->stats['errors']}");
        }

        $this->output("Course Completion Stats:");
        $this->output("  ✓ Migrated: {$this->stats['courses_migrated']}");
        $this->output("  ⊘ Skipped (already complete): {$this->stats['courses_skipped']}");
        $this->output("  ✗ Not valid for migration: {$this->stats['courses_notvalid']}");

        $this->output("===================");
    }

    /**
     * Get current statistics
     *
     * @return array Statistics array
     */
    public function get_stats() {
        return $this->stats;
    }
}
