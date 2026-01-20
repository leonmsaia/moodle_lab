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
 * This file contains the definition for the class assignment
 *
 * This class provides all the functionality for the new assign module.
 *
 * @package   mod_assign
 * @copyright 2012 NetSpot {@link http://www.netspot.com.au}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_rating_item;

use renderer_base;
use renderable;
use templatable;
use stdClass;
use mod_assign\output\grading_app;

class grading_app_grade extends grading_app {
    /**
     * @var $userid - The initial user id.
     */
    public $userid = 0;

    /**
     * @var $groupid - The initial group id.
     */
    public $groupid = 0;

    /**
     * @var $assignment - The assignment instance.
     */
    public $assignment = null;

    /**
     * Constructor for this renderable.
     *
     * @param int $userid The user we will open the grading app too.
     * @param int $groupid If groups are enabled this is the current course group.
     * @param assign $assignment The assignment class
     */
    public function __construct($userid, $groupid, $assignment) {
        parent::__construct($userid, $groupid, $assignment);
    }
}
