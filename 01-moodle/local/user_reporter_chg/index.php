<?php
/**
 * Entry point for the User Reporter CHG local plugin.
 *
 * This script renders a report listing Moodle users and the courses
 * in which they are enrolled. It relies on the custom
 * {@see \local_user_reporter_chg\table\users_courses_table} class
 * to provide pagination, sorting and export capabilities.
 *
 * Features:
 * - System-level access control via capability local/user_reporter_chg:view
 * - Optional download mode (CSV/Excel/etc.) using the table API
 * - Configurable "per page" option through a simple selector
 *
 * This page is intended to be accessed via the Moodle navigation,
 * not directly by unauthenticated users.
 *
 * @package     local_user_reporter_chg
 * @category    page
 * @author      Leon. M. Saia
 * @email       leonmsaia@gmail.com
 * @website     https://leonmsaia.com
 */

require_once(__DIR__ . '/../../config.php');

require_login();

// -----------------------------------------------------------------------------
// Context and capability checks
// -----------------------------------------------------------------------------

$context  = context_system::instance();
require_capability('local/user_reporter_chg:view', $context);

// -----------------------------------------------------------------------------
// Parameters and page setup
// -----------------------------------------------------------------------------

// Optional download type (e.g. 'csv', 'xls'), handled by table_sql::is_downloading().
$download = optional_param('download', '', PARAM_ALPHA);

// Number of records per page when viewing in browser (default = 3).
$perpage  = optional_param('perpage', 3, PARAM_INT); // default = 3
if ($perpage <= 0) {
    $perpage = 3;
}

// Configure the Moodle page object.
$PAGE->set_url('/local/user_reporter_chg/index.php', ['perpage' => $perpage]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('reporttitle', 'local_user_reporter_chg'));

// -----------------------------------------------------------------------------
// Table setup
// -----------------------------------------------------------------------------

/** @var \local_user_reporter_chg\table\users_courses_table $table */
$table = new \local_user_reporter_chg\table\users_courses_table('local_user_reporter_chg_table');

// Enable download mode if requested (filename: user_courses_report).
$table->is_downloading($download, 'user_courses_report', 'user_courses_report');

// Define the SQL query used by the table.
// Note: Additional joins/filters can be added here if needed.
$table->set_sql(
    "u.id, u.username, u.firstname, u.lastname",
    "{user} u",
    "u.deleted = 0 AND u.suspended = 0"
);

// Base URL for pagination and sorting links.
$table->define_baseurl($PAGE->url);

// -----------------------------------------------------------------------------
// Output (header, filter controls, table, footer)
// -----------------------------------------------------------------------------

if (!$table->is_downloading()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('reporttitle', 'local_user_reporter_chg'));

    // Per-page selector to control how many records are displayed in the browser.
    $perpageoptions = [
        3  => '3',
        10 => '10',
        25 => '25',
        50 => '50',
    ];

    $baseurl = new moodle_url('/local/user_reporter_chg/index.php');
    $select  = new single_select($baseurl, 'perpage', $perpageoptions, $perpage, null, 'perpageform');
    
    // Simple wrapper around the per-page selector.
    // Note: The label is hard-coded in English; it can be moved to a lang string if needed.
    echo html_writer::div(
        html_writer::label('Records per page', 'perpageform', false, ['style' => 'margin-right: 8px;']) .
        $OUTPUT->render($select),
        'mb-3'
    );
}

// Render the table.
// - In download mode: output all records, no paging, no footer.
// - In normal mode: paginated output and page footer.
if ($table->is_downloading()) {
    $table->out(0, false);
} else {
    $table->out($perpage, true);
    echo $OUTPUT->footer();
}
