<?php

namespace local_mutualreport;

defined('MOODLE_INTERNAL') || die();

global $CFG;

include_once($CFG->libdir . '/tablelib.php');

class table extends \table_sql
{
    /*
    public function col_fullname($row)
    {
        global $DB;
        $user = $DB->get_record('user', array('username' => $row->rut));
        if (!empty($user)) {
            return parent::col_fullname($user);
        } else {
            return '-';
        }
    }*/
}
