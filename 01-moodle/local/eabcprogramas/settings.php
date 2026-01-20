<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/course/lib.php');

    $title = get_string('pluginname', 'local_eabcprogramas');
    $settings = new admin_settingpage('local_eabcprogramas', $title);

    $displaylist = \core_course_category::make_categories_list('moodle/course:create');

    $name = 'local_eabcprogramas/categoryid';
    $description = get_string('setting_category', 'local_eabcprogramas');
    $title = get_string('title_category', 'local_eabcprogramas');
    $settings->add(new admin_setting_configmultiselect($name, $title, $description, [], $displaylist));

    $name = 'local_eabcprogramas/param_aprobacion';
    $description = get_string('desc_param_aprobacion', 'local_eabcprogramas');
    $title = get_string('title_param_aprobacion', 'local_eabcprogramas');
    $settings->add(new admin_setting_configtext(
        $name,
        $title,
        $description,
        75,
        PARAM_INT,
        null
    ));

    $ADMIN->add('localplugins', $settings);
}
