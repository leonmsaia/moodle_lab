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
 * edit or add faq instance
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_help\faq;
use local_help\form\editfaq;

include_once('../../config.php');


/**
 * @var core_renderer $OUTPUT
 * @var moodle_page $PAGE
 */
global $OUTPUT, $CFG, $PAGE;

require_once($CFG->libdir . '/adminlib.php');

try {
    $id = optional_param('id', null, PARAM_INT);

    admin_externalpage_setup('local_help_managefaq');

    $PAGE->set_url(new moodle_url('/local/help/editfaq.php', ["id" => $id]));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_subpage("editfaq");
    $PAGE->set_pagetype("editfaq");


    $faq = null;

    if (!empty($id)) {
        $faq = new faq($id);
        $faq = $faq->to_record();
    }


    $customdata = [
        'faq' => $faq  // An instance, or null.
    ];

    $form = new editfaq($PAGE->url->out(false), $customdata);

    if (($data = $form->get_data())) {

        if (empty($data->id)) {
            // If we don't have an ID, we know that we must create a new record.
            // Call your API to create a new persistent from this data.
            // Or, do the following if you don't want capability checks (discouraged).
            $faq = new faq();
            $faq->set("title", $data->title);
            $faq->set("content", $data->content);
            $faq->create();
        } else {
            // We had an ID, this means that we are going to update a record.
            // Call your API to update the persistent from the data.
            // Or, do the following if you don't want capability checks (discouraged).
            $faq = new faq($data->id);
            $update = (object) [
                "id" => $data->id,
                "title" => $data->title,
                "content" => $data->content
            ];
            $faq->from_record($update);
            $faq->update();
        }

        // We are done, so let's redirect somewhere.
        redirect(new moodle_url('/local/help/managefaq.php'));
    }

    echo $OUTPUT->header();
    $form->display();
    echo $OUTPUT->footer();

} catch (coding_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception('errormsg', 'local_help', '', $e->getMessage(), $e->debuginfo);
}



