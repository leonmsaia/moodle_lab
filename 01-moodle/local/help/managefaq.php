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
 * Manage faq page
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_help\faq;

include_once('../../config.php');

/** @var core_renderer $OUTPUT */
global $OUTPUT, $CFG;

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_help_managefaq');
$thisurl = new moodle_url('/local/help/managefaq.php');

$faqs = faq::get_records();

$table = new flexible_table("local_help_managefaq");
$table->baseurl = $thisurl;
$table->define_columns(["title", "actions"]);
try {
    
    
    $table->define_headers([get_string("title", "local_help"), get_string("actions", "local_help")]);
    echo $OUTPUT->header();
    
    require_once('classes/form/faq_form_setting.php');
    
    $table->setup();
    foreach ($faqs as $faq) {
        $faq = $faq->to_record();
        $faq->actions = $OUTPUT->render_from_template('local_help/actions', ["wwwroot" => $CFG->wwwroot, "id" => $faq->id]);
        $table->add_data_keyed($faq);
    }
    $table->finish_output();
    echo $OUTPUT->single_button(new moodle_url('/local/help/editfaq.php'), get_string('addnew', 'local_help'));

    $mform = new faq_form_setting(null, array());
    $active = 0;
    if ($mform->is_cancelled()) {
       
    } else if ($fromform = $mform->get_data()) {
        $active = (empty($fromform->activepluginhelp)) ? "0" : $fromform->activepluginhelp;
        set_config("activepluginhelp", $active, "local_help");
    } else {
    }
    
    $mform->set_data(array("activepluginhelp" => get_config("local_help", "activepluginhelp")));
    $mform->add_action_buttons();
    $mform->display();

    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception("errormsg", "local_help", '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception("errormsg", "local_help", '', $e->getMessage(), $e->debuginfo);
}

