<?php

namespace local_password_company;

class utils {
    public static function save_secure_password($company) {
        global $DB, $USER;
        $get_secure_password = $DB->get_records('local_password_company', array('companyid' => $company));
        if(empty($get_secure_password)) {
            $new = new \stdClass();
            $new->companyid = $company;
            $new->timemodified = time();
            $new->user_assign = $USER->id;
            $DB->insert_record('local_password_company', $new);
        }
    }
}
