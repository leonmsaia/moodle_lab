<?php
namespace local_companymessage\hook;

use core\hook\output\before_http_headers;

defined('MOODLE_INTERNAL') || die();

class before_http_headers_handler {

    /**
     * Hook observer for the header_content hook point.
     * This method is called when the hook is triggered.
     *
     * @return void
     */
    public static function show_message(): void {
        global $PAGE, $USER, $DB;



        // Company-specific message logic (dashboard only).
        if ($PAGE->pagetype !== 'site-index') {
            return;
        }

        // Do nothing if no user is logged in.
        if (!isloggedin() || isguestuser()) {
            return;
        }

        // Maintenance message logic (global).
        $pluginconfig = get_config('local_companymessage');
        if(empty($pluginconfig)){
            return;
        }

        if ($pluginconfig->maintenance_enabled) {
            $maintenancemessage = $pluginconfig->maintenance_message;
            $maintenancestarttime = $pluginconfig->maintenance_starttime;


            if (!empty($maintenancemessage) && !empty($maintenancestarttime)) {
                // Assuming start time is in HH:MM format.
                $starttime = strtotime(date('Y-m-d') . ' ' . $maintenancestarttime);
                if (time() >= $starttime) {
                    echo \core\notification::info($maintenancemessage, 'info');
                }
            }
        }

        // Get plugin settings.
        $companyrutssetting = $pluginconfig->companyruts;
        $message = $pluginconfig->loginmessage;

        // If settings are empty, do nothing.
        if (empty($companyrutssetting) || empty($message)) {
            return;
        }

        // Prepare the list of RUTs for the query.
        $companyruts = array_map('trim', preg_split('/\r\n|\r|\n/', $companyrutssetting));
        $companyruts = array_filter($companyruts); // Remove empty lines.

        if (empty($companyruts)) {
            return;
        }

        // Build the SQL query to check if the user belongs to any of the configured companies.
        list($sql_in, $params) = $DB->get_in_or_equal($companyruts, SQL_PARAMS_NAMED, 'rut');
        list($sql_in2, $params2) = $DB->get_in_or_equal($companyruts, SQL_PARAMS_NAMED, 'rut2');

        $params['userid'] = $USER->id;
        $params = array_merge($params, $params2);

        $sql = "SELECT c.id
              FROM {company_users} cu
              JOIN {company} c ON c.id = cu.companyid
             WHERE (SUBSTRING_INDEX(c.rut, '-', 1) $sql_in OR c.rut $sql_in2)
               AND cu.userid = :userid";


        // If the user's company is in the list, show the message.
        if ($DB->record_exists_sql($sql, $params)) {
            echo \core\notification::info($message, 'info');
        }
    }
}
