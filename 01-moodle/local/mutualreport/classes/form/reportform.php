<?php

namespace local_mutualreport\form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
class reportform extends \moodleform
{
    public function definition()
    {
        $mform = $this->_form;
        $custom = $this->_customdata;
        $mform->addElement('date_selector', 'fromdate', get_string('fromdate', 'local_mutualreport'));

        $mform->addElement('date_selector', 'todate', get_string('todate', 'local_mutualreport'));

        $companies = \local_mutualreport\utils::get_companies_from_userid($custom['userid']);
        $options = ['' => get_string('all')];
        foreach ($companies as $company) {
            $options[$company->id] = $company->name;
        }
        $mform->addElement('select', 'company', get_string('company', 'local_mutualreport'), $options);

        $mform->addElement('text', 'rut', get_string('rut', 'local_mutualreport'));
        $mform->setType('rut', PARAM_RAW);

        $courses = get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname');
        $optionscourses = ['' => get_string('all')];
        foreach($courses as $course) {
            $optionscourses[$course->id] = $course->fullname;
        }

        $mform->addElement('autocomplete', 'course', get_string('course', 'local_mutualreport'), $optionscourses);

        $mform->addElement('text', 'rut_company', get_string('rut_company', 'local_mutualreport'));
        $mform->setType('rut_company', PARAM_RAW);

        $mform->addElement('text', 'adherente', get_string('adherente', 'local_mutualreport'));
        $mform->setType('adherente', PARAM_RAW);

        $this->add_action_buttons(false, get_string('filter', 'local_mutualreport'));
    }
}
