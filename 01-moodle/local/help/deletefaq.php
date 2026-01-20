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
 * delete faq instance
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_help\faq;

include_once('../../config.php');


/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

require_once($CFG->libdir . '/adminlib.php');

try {
    $id = required_param('id', PARAM_INT);
    $confirm = optional_param('confirm', null, PARAM_INT);

    admin_externalpage_setup('local_help_managefaq');

    $PAGE->set_url(new moodle_url('/local/help/deletefaq.php', ["id" => $id, "confirm" => $confirm]));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_subpage("deletefaq");
    $PAGE->set_pagetype("deletefaq");


    $faq = null;

    if (!empty($id)) {
        $faq = new faq($id);
    }
    if(!empty($confirm)) {
        $faq->delete();
        redirect(new moodle_url('/local/help/managefaq.php'));
    } else {
        echo $OUTPUT->header();
        $faq = $faq->to_record();
        echo $OUTPUT->confirm(
            get_string("confirm", "local_help", $faq),
            new moodle_url('/local/help/deletefaq.php', ["id" => $faq->id, "confirm" => 1]),
            new moodle_url('/local/help/managefaq.php')
        );
        echo $OUTPUT->footer();
    }


} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
}
