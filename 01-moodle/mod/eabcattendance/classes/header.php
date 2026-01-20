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
 * Class definition for mod_eabcattendance_header
 *
 * @package    mod_eabcattendance
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Used to render the page header.
 *
 * @package    mod_eabcattendance
 * @author     Daniel Thee Roperto <daniel.roperto@catalyst-au.net>
 * @copyright  2017 Catalyst IT Australia {@link http://www.catalyst-au.net}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_eabcattendance_header implements renderable {
    /** @var mod_eabcattendance_structure */
    private $eabcattendance;

    /** @var string */
    private $title;

    /**
     * mod_eabcattendance_header constructor.
     *
     * @param mod_eabcattendance_structure $eabcattendance
     * @param null                     $title
     */
    public function __construct(mod_eabcattendance_structure $eabcattendance, $title = null) {
        $this->eabcattendance = $eabcattendance;
        $this->title = $title;
    }

    /**
     * Gets the eabcattendance data.
     *
     * @return mod_eabcattendance_structure
     */
    public function get_eabcattendance() {
        return $this->eabcattendance;
    }

    /**
     * Gets the title. If title was not provided, use the module name.
     *
     * @return string
     */
    public function get_title() {
        return is_null($this->title) ? $this->eabcattendance->name : $this->title;
    }

    /**
     * Checks if the header should be rendered.
     *
     * @return bool
     */
    public function should_render() {
        return !is_null($this->title) || !empty($this->eabcattendance->intro);
    }
}
