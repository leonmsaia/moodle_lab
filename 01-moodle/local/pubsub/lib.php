<?php

defined('MOODLE_INTERNAL') || die();

/** @var object $CFG */
require_once($CFG->dirroot . "/backup/util/includes/restore_includes.php");
require_once($CFG->libdir . "/moodlelib.php");

/**
 * @param $coursedata
 * @param $backupdir
 * @return int
 * @throws moodle_exception
 */
 function make_course_by_template($coursedata, $backupdir) {
    global $USER;
	
	$source = $backupdir . '/' . $coursedata['template'];
    
    $packer = get_file_packer('application/vnd.moodle.backup');

// Get a backup temp directory name and create it.
    $tempdir = restore_controller::get_tempdir_name(SITEID, $USER->id);
    try {
        $fulltempdir = make_backup_temp_directory_eabc($tempdir);
    } catch (coding_exception $e) {
        throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage());
    } catch (invalid_dataroot_permissions $e) {
        throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage());
    }


    $packer->extract_to_pathname($source, $fulltempdir);

    $coursedata["id"] = restore_dbops::create_new_course(
                    $coursedata["fullname"], $coursedata["shortname"], $coursedata["categoryid"]
    );
    $rc = new restore_controller(
            $tempdir, $coursedata["id"], backup::INTERACTIVE_NO, backup::MODE_AUTOMATED, $USER->id, backup::TARGET_NEW_COURSE
    );

// Check if the format conversion must happen first.
    if ($rc->get_status() == backup::STATUS_REQUIRE_CONV) {
        try {
            $rc->convert();
        } catch (restore_controller_exception $e) {
            throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage());
        }
    }
    if ($rc->execute_precheck()) {
        try {
            $rc->execute_plan();
        } catch (Exception $e) {
            throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage());
        }
    } else {
        throw new moodle_exception('errorwhilerestoringthecourse', 'tool_uploadcourse');
    }
    $rc->destroy();

    return $coursedata['id'];
}

/**
 * @param $directory
 * @param bool $exceptiononerror
 * @return false|string
 * @throws coding_exception
 * @throws invalid_dataroot_permissions
 */
function make_backup_temp_directory_eabc($directory, $exceptiononerror = true) {
    global $CFG;
    protect_directory($CFG->tempdir);
    return make_writable_directory($CFG->tempdir . "/backup/" . $directory, $exceptiononerror);
}


function restore_templatecourse($course){
    global $DB, $CFG;
    include_once($CFG->dirroot . '/admin/tool/uploadcourse/classes/course.php');
    $courseformat = '';
    $numsections = 0;

    if (empty($courseformat)) {
        $tempcourse = $DB->get_record('course', array('shortname' => $course['templatecourse']));
        if (!empty($tempcourse)) {
            $course["format"] = $tempcourse->format;
            if (empty($numsections)) {
                $course["numsections"] = $DB->count_records('course_sections', array('course' => $tempcourse->id));
            }
        } else {
            throw new moodle_exception('errormsg', 'local_eabcws', '', 'No existe un template con ese nombre');
        }
    }

    try {
        $upload = new tool_uploadcourse_course(3, 1, $course);

        $upload->prepare();
        $upload->proceed();
    } catch (coding_exception $e) {
        throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage(), $e->debuginfo);
    } catch (moodle_exception $e) {
        throw new moodle_exception('errormsg', 'local_eabcws', '', $e->getMessage(), $e->debuginfo);
    }
    
    return $upload->get_id();
}
