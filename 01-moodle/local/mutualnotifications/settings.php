<?php  

if ($hassiteconfig) {
    $ADMIN->add(
            'localplugins', new admin_externalpage(
            'local_mutualnotifications', get_string('pluginname', 'local_mutualnotifications'), $CFG->wwwroot . '/local/mutualnotifications/adminsettings.php', 'local/help:view'
            )
    );
}
