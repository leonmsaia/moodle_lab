<?php

namespace local_user_reporter_chg\table;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/enrollib.php');

/**
 * Class users_courses_table
 *
 * This table class provides a SQL-backed table used to display a list of users
 * along with the courses in which each user is enrolled. It extends Moodle's
 * core {@see \table_sql} to enable server-side pagination, sorting, and flexible
 * column rendering.
 *
 * The table displays the following columns:
 * - username
 * - firstname
 * - lastname
 * - courses (dynamically generated from enrolments)
 *
 * This class is instantiated with a unique table identifier and later
 * configured by the parent controller or page script to provide SQL queries
 * for data retrieval.
 *
 * @package     local_user_reporter_chg
 * @category    table
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */
class users_courses_table extends \table_sql {

    /**
     * Constructor.
     *
     * Defines table columns, headers, sorting behavior and pagination options.
     * The SQL query itself must be supplied externally (via set_sql and set_count_sql).
     *
     * @param string $uniqueid A unique identifier for this table instance.
     */
    public function __construct(string $uniqueid) {
        parent::__construct($uniqueid);

        $this->define_columns(['username', 'firstname', 'lastname', 'courses']);
        $this->define_headers([
            get_string('username', 'local_user_reporter_chg'),
            get_string('firstname', 'local_user_reporter_chg'),
            get_string('lastname', 'local_user_reporter_chg'),
            get_string('courses', 'local_user_reporter_chg'),
        ]);

        // Enables sortable columns and pagination.
        $this->sortable(true);
        $this->pageable(true);

        // Default sorting configuration: sort by lastname ASC then firstname.
        $this->sort_default_column = 'lastname';
        $this->sort_default_order  = SORT_ASC;
    }

    /**
     * Column renderer for the "courses" column.
     *
     * Retrieves all user enrolments using {@see enrol_get_users_courses()}.
     * If the user is not enrolled in any courses, a dash "-" is displayed.
     *
     * @param \stdClass $row A database row representing a user. Must contain at least the 'id' property.
     *
     * @return string A comma-separated list of course full names, or "-".
     */
    public function col_courses($row) {
        $courses = enrol_get_users_courses($row->id, true, 'fullname');
        if (empty($courses)) {
            return '-';
        }

        $names = array_map(static function($course) {
            return $course->fullname;
        }, $courses);

        return implode(', ', $names);
    }
}
