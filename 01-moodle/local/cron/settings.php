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
 * settings file
 *
 * @package    local_cron
 * @copyright  2019 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */


defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_cron', get_string('pluginname', 'local_cron'));
    /** @var parentable_part_of_admin_tree $ADMIN */
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_cron/days',
        get_string('days', 'local_cron'), get_string('days_desc', 'local_cron'), 30, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_cron/days_enrol',
        get_string('days_enrol', 'local_cron'), get_string('days_enrol_desc', 'local_cron'), 30, PARAM_INT));
    
    $settings->add(new admin_setting_configtext('local_cron/reaggregate_date_from',
        get_string('reaggregate_date_from', 'local_cron'), get_string('reaggregate_date_from_desc', 'local_cron'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('local_cron/reaggregate_date_to',
        get_string('reaggregate_date_to', 'local_cron'), get_string('reaggregate_date_to_desc', 'local_cron'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('local_cron/courses',
        get_string('courses'), get_string('courses'), '', PARAM_RAW));

    $settings->add(new admin_setting_configtext('local_cron/users',
    get_string('users'), get_string('users'), '', PARAM_RAW));
    
}

