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

declare(strict_types=1);

namespace local_mutualreport\reportbuilder\local\entities;

use context_helper;
use context_system;
use context_user;
use core\context;
use core_component;
use core_date;
use core\output\html_writer;
use lang_string;
use core\url as moodle_url;
use stdClass;
use theme_config;
use core_user\fields;
use core_reportbuilder\local\filters\boolean_select;
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\select;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\filters\user as user_filter;
use core_reportbuilder\local\helpers\user_profile_fields;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use core_reportbuilder\local\entities\user as core_user;
use local_mutualreport\reportbuilder\local\filters\text35;
use local_mutualreport\reportbuilder\local\filters\username_list;
use local_mutualreport\reportbuilder\local\filters\user_username_list35;
use local_mutualreport\reportbuilder\local\formatters\common as common_formatter;

/**
 * User entity class implementation.
 *
 * This entity defines all the user columns and filters to be used in any report.
 *
 * @package    core_reportbuilder
 * @copyright  2020 Sara Arjona <sara@moodle.com> based on Marina Glancy code.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user extends core_user {

    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        $tables = parent::get_default_tables();

        $tables[] = 'course';
        $tables[] = 'inscripcion_elearning_back';

        return $tables;
    }

    /**
     * Returns list of all available columns
     *
     * Overridden to provide a custom implementation for 'fullnamewithpicturelink'
     * that handles download formatting correctly.
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        $columns = parent::get_all_columns();

        $usertablealias = $this->get_table_alias('user');
        $fullnameselect = self::get_name_fields_select($usertablealias);
        $fullnamesort = explode(', ', $fullnameselect);

        $coursealias = $this->get_table_alias('course');
        $ieblalias = $this->get_table_alias('inscripcion_elearning_back');

        $userpictureselect = fields::for_userpic()->get_sql($usertablealias, false, '', '', false)->selects;

        // Add our custom 'fullnamewithpicturelink' column.
        $columns[] = (new column(
            'fullnamewithpicturelink2',
            new lang_string('userfullnamewithpicturelink', 'core_reportbuilder'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_fields($fullnameselect)
            ->add_field("{$usertablealias}.id")
            ->add_fields($userpictureselect) // Important for user_picture()
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable($this->is_sortable('fullnamewithpicturelink'), $fullnamesort)
            ->add_callback(static function(?string $value, stdClass $row): string {
                global $PAGE, $OUTPUT;

                if ($value === null) {
                    return '';
                }

                // Ensure we populate all required name properties for fullname().
                $namefields = fields::get_name_fields();
                foreach ($namefields as $namefield) {
                    $row->{$namefield} = $row->{$namefield} ?? '';
                }

                $fullname = fullname($row);

                // Check if the report is being downloaded.
                // When downloading, $PAGE->pagetype is not the report page type.
                // This is a reliable way to detect a download context within a callback.
                $isdownloading = ($PAGE->pagetype !== 'local_mutualreport');

                if ($isdownloading) {
                    // For downloads (CSV, Excel, etc.), return only the plain text name.
                    return $fullname;
                } else {
                    // For web view, return the full HTML with picture and link.
                    return html_writer::link(
                        new moodle_url('/user/profile.php', ['id' => $row->id]),
                        $OUTPUT->user_picture($row, ['link' => false, 'alttext' => false]) . ' ' . $fullname
                    );
                }
            });

        // Username with link to profile.
        $columns[] = (new column(
            'eabcusernamewithlink',
            new lang_string('field_rut', 'local_mutualreport'), // Using RUT as title, as it seems to be the convention.
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_field("{$usertablealias}.id")
            ->add_field("{$usertablealias}.username")
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable($this->is_sortable('username'), ["{$usertablealias}.username"])
            ->add_callback(static function(?string $value, stdClass $row): string {
                global $PAGE;

                if (empty($row->username)) {
                    return '';
                }

                // Check if the report is being downloaded.
                $isdownloading = ($PAGE->pagetype !== 'local_mutualreport');

                if ($isdownloading) {
                    // For downloads, return only the plain text username.
                    return $row->username;
                } else {
                    // For web view, return the full HTML with the link.
                    return html_writer::link(
                        new moodle_url('/user/profile.php', ['id' => $row->id]),
                        $row->username
                    );
                }
            });

        // Enrolment time created.
        $columns[] = (new column(
            'lastaccess2',
            new lang_string('sitelastaccess', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_field("{$usertablealias}.lastaccess")
            ->set_is_sortable(true)
            ->add_callback([common_formatter::class, 'userdate']);

        // We define unique aliases for the JOINs of the profile fields.
        $uifnombresalias = 'uif_cn'; // Contactonombres (field).
        $uidnombresalias = 'uid_cn'; // Contactonombres (data).
        $uifapellidopalias = 'uif_cap'; // Contactoapellidopaterno (field).
        $uidapellidopalias = 'uid_cap'; // Contactoapellidopaterno (data).
        $uifemailalias = 'uif_ce'; // Contactoemail (field).
        $uidemailalias = 'uid_ce'; // Contactoemail (data).
        $uifrutalias = 'uif_cr'; // Cintactoiddoc (field).
        $uidrutalias = 'uid_cr'; // Cintactoiddoc (data).

        // JOIN for the table inscripcion_elearning_back.
        $iebljoin =
            "LEFT JOIN {inscripcion_elearning_back} {$ieblalias}
                       ON {$ieblalias}.id = (
                           SELECT id
                           FROM {inscripcion_elearning_back}
                           WHERE id_user_moodle = {$usertablealias}.id
                                 AND (
                                     responsablenombre IS NOT NULL
                                     OR responsableapellido1 IS NOT NULL
                                     OR responsablerut IS NOT NULL
                                 )
                                 AND id_curso_moodle = {$coursealias}.id
                           ORDER BY id DESC
                           LIMIT 1
                       )";

        // Manager name.
        $columns[] = (new column(
            'nombreencargado',
            new lang_string('managername', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($iebljoin)
            ->add_join("
                LEFT JOIN {user_info_field} {$uifnombresalias}
                          ON {$uifnombresalias}.shortname = 'contactonombres'
            ")
            ->add_join("
                LEFT JOIN {user_info_data} {$uidnombresalias}
                          ON {$uidnombresalias}.userid = {$usertablealias}.id
                         AND {$uidnombresalias}.fieldid = {$uifnombresalias}.id
            ")
            ->add_join("
                LEFT JOIN {user_info_field} {$uifapellidopalias}
                          ON {$uifapellidopalias}.shortname = 'contactoapellidopaterno'
            ")
            ->add_join("
                LEFT JOIN {user_info_data} {$uidapellidopalias}
                          ON {$uidapellidopalias}.userid = {$usertablealias}.id
                         AND {$uidapellidopalias}.fieldid = {$uifapellidopalias}.id
            ")
            ->add_field("COALESCE(
                CONCAT(
                        {$ieblalias}.responsablenombre,
                        ' ',
                        {$ieblalias}.responsableapellido1,
                        ' ',
                        {$ieblalias}.responsableapellido2
                ),
                CONCAT(
                    {$uidnombresalias}.data,
                    ' ',
                    {$uidapellidopalias}.data
                )
            )", 'nombreencargado')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false);

        // Manager email.
        $columns[] = (new column(
            'mailencargado',
            new lang_string('managermail', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($iebljoin)
            ->add_join("
                LEFT JOIN {user_info_field} {$uifemailalias}
                    ON {$uifemailalias}.shortname = 'contactoemail'
            ")
            ->add_join("
                LEFT JOIN {user_info_data} {$uidemailalias}
                    ON {$uidemailalias}.userid = {$usertablealias}.id
                   AND {$uidemailalias}.fieldid = {$uifemailalias}.id
            ")
            ->add_field("COALESCE(
                {$ieblalias}.responsableemail,
                {$uidemailalias}.data
            )", 'mailencargado')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false);

        // Manager RUT.
        $columns[] = (new column(
            'rutencargado',
            new lang_string('managerrut', 'local_mutualreport'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->add_join($iebljoin)
            ->add_join("
                LEFT JOIN {user_info_field} {$uifrutalias}
                    ON {$uifrutalias}.shortname = 'cintactoiddoc'
            ")
            ->add_join("
                LEFT JOIN {user_info_data} {$uidrutalias}
                    ON {$uidrutalias}.userid = {$usertablealias}.id
                   AND {$uidrutalias}.fieldid = {$uifrutalias}.id
            ")
            ->add_field("COALESCE(
                CONCAT({$ieblalias}.responsablerut, '-', {$ieblalias}.responsabledv),
                {$uidrutalias}.data
            )", 'rutencargado')
            ->set_type(column::TYPE_TEXT)
            ->set_is_sortable(false);

        return $columns;

    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {

        $filters = parent::get_all_filters();

        $tablealias = $this->get_table_alias('user');

        // Username filter.
        $filters[] = (new filter(
            text35::class,
            'username35',
            new lang_string('rut', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$tablealias}.username"
        ))
            ->add_joins($this->get_joins());

        // Username list filter.
        $filters[] = (new filter(
            username_list::class,
            'usernamelist',
            new lang_string('filter_usernamelist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$tablealias}.username"
        ))
            ->add_joins($this->get_joins());

        // User username list filter 35.
        $filters[] = (new filter(
            user_username_list35::class,
            'usernamelist35',
            new lang_string('filter_usernamelist', 'local_mutualreport'),
            $this->get_entity_name(),
            "{$tablealias}.username"
        ))
            ->add_joins($this->get_joins());

        return $filters;

    }

}
