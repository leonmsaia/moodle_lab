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

namespace tool_eabcetlbridge\tasks;

use core\task\scheduled_task;
use tool_eabcetlbridge\persistents\batch_files;
use tool_eabcetlbridge\persistents\planners\course_grade_migration\course_planner as course_planner;
use tool_eabcetlbridge\persistents\planners\user_grade_migration\users_by_course as user_planner;

/**
 * Resets completed course planners to pending if new users have been enrolled.
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_planners_status_task extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('update_planners_status_task', 'tool_eabcetlbridge');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        static::execute_for_users_by_file();
    }

    /**
     * Execute the task.
     */
    public static function execute_for_users_by_file() {
        [
            $userplanners,
            $courseplanners,
            $batchfiles,
            $usertimecreated
        ] = user_planner::get_records_for_status_update_with_users_by_file();

        $updates = 0;
        $withoutupdates = 0;
        $resetbatchfiles = [];
        $resetbatchfilesids = [];
        /** @var user_planner $userplanner */
        foreach ($userplanners as $key => $userplanner) {

            /** @var batch_files $batchfile */
            $batchfile = $batchfiles[$key];

            /** @var course_planner $courseplanner */
            $courseplanner = $courseplanners[$key];

            if ($batchfile->get('timemodified') > $usertimecreated[$key]) {
                $userplanner->set('status', user_planner::STATUS_PROCESSING);
                $userplanner->set('parenttaskid', $courseplanner->get('id'));
                $userplanner->save();
                $updates++;
            } else {
                if (!in_array($batchfile->get('id'), $resetbatchfilesids)) {
                    $resetbatchfiles[] = $batchfile;
                    $resetbatchfilesids[] = $batchfile->get('id');
                }
                $withoutupdates++;
            }

        }

        mtrace("User planners actualizados: {$updates}, sin actualizar: {$withoutupdates}");

        foreach ($resetbatchfiles as $batchfile) {
            mtrace("Reestableciendo el estado del planner {$batchfile->get('id')}");
            $batchfile->set('status', batch_files::STATUS_PENDING);
            $batchfile->save();
        }
    }

    /**
     * Execute the task.
     */
    public function execute_only_when_not_usesing_pagination() {

        return;

        [$userplanners, $courseplanners, $batchfiles, $usertimecreated] = user_planner::get_records_for_status_update();

        $updates = 0;
        $withoutupdates = 0;
        $resetbatchfiles = [];
        $resetbatchfilesids = [];
        /** @var user_planner $userplanner */
        foreach ($userplanners as $key => $userplanner) {

            /** @var batch_files $batchfile */
            $batchfile = $batchfiles[$key];

            /** @var course_planner $courseplanner */
            $courseplanner = $courseplanners[$key];

            if ($batchfile->get('timemodified') > $usertimecreated[$key]) {
                $userplanner->set('status', user_planner::STATUS_PROCESSING);
                $userplanner->set('parenttaskid', $courseplanner->get('id'));
                $userplanner->save();
                $updates++;
            } else {
                if (!in_array($batchfile->get('id'), $resetbatchfilesids)) {
                    $resetbatchfiles[] = $batchfile;
                    $resetbatchfilesids[] = $batchfile->get('id');
                }
                $withoutupdates++;
            }

        }

        mtrace("User planners actualizados: {$updates}, sin actualizar: {$withoutupdates}");

        foreach ($resetbatchfiles as $batchfile) {
            mtrace("Reestableciendo el estado del planner {$batchfile->get('id')}");
            $batchfile->set('status', batch_files::STATUS_PENDING);
            $batchfile->save();
        }

    }
}
