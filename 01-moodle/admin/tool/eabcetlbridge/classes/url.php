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
 * Class for manage plugin urls
 *
 * @package   tool_eabcetlbridge
 * @copyright 2023 e-abclearning.com
 * @author    Isaac Petrucelli, Miguel Magdalena, Daniel Belizan
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_eabcetlbridge;

use core\url as moodle_url;

class url extends moodle_url {

    /**
     * Get main page url
     *
     * @param array $params
     * @return moodle_url
     */
    public static function view_main($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/main.php', $params);
    }

    /**
     * Get view configs page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function viewconfigs($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/viewconfigs.php', $params);
    }

    /**
     * Get edit configs page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function editconfigs($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/editconfigs.php', $params);
    }

    /**
     * Get edit batch files page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function editbatch_files($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/editbatch_files.php', $params);
    }

    /**
     * Get view batch files page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function viewbatch_files($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/viewbatch_files.php', $params);
    }

    /**
     * Get view planner page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function viewplanner($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/viewplanner.php', $params);
    }

    /**
     * Get edit planner page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function editplanner($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/editplanner.php', $params);
    }

    /**
     * Get view ad-hoc tasks page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function viewadhoc_tasks($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/viewadhoc_tasks.php', $params);
    }

    /**
     * Get edit ad-hoc tasks page
     *
     * @param array $params
     * @return moodle_url
     */
    public static function editadhoc_tasks($params = array()) {
        return new moodle_url('/admin/tool/eabcetlbridge/pages/editadhoc_tasks.php', $params);
    }

}
