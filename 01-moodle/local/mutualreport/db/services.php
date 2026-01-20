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

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_mutualreport_get_companies_by_user' => [
        'classname' => 'local_mutualreport\external\company_summary_exporter',
        'methodname' => 'get_companies_by_user',
        'description' => 'Get companies for a user, with search capabilities.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_mutualreport_get_companies_by_user_35' => [
        'classname' => 'local_mutualreport\external\company_summary_exporter',
        'methodname' => 'get_companies_by_user_35',
        'description' => 'Get companies for a user, with search capabilities.',
        'type' => 'read',
        'ajax' => true,
    ],
];
