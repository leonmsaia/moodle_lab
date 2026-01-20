<?php

namespace local_password_company\form;
require_once("$CFG->libdir/formslib.php");

class add_secure_password extends \moodleform {
    public $courseid;
    
    public function __construct($customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                $ajaxformdata=null) {
        parent::__construct(new \moodle_url("/local/password_company/view.php"), $customdata, $method, $target, $attributes, $editable,
                                $ajaxformdata);
    }
    //Add elements to form
    public function definition() {
        global $CFG, $DB;
        $areanames = array();
        $mform = $this->_form; // Don't forget the underscore! 
 
        
        /* $from = 'select c.* from {local_password_company} AS p
    LEFT join {company} as c on c.id = p.companyid
'; */

        $from = 'select p.* from {company} AS p
        LEFT join {local_password_company} as c on  c.companyid = p.id 
        ';
        $modinfo = $DB->get_records_sql_menu($from);
        
        $options = array(
            'multiple' => false,
            'noselectionstring' => get_string('allareas', 'search'),    
        );
        $mform->addElement('autocomplete', 'company',  get_string('selectcompany', 'local_password_company'),  $modinfo, $options);
        $mform->setType('company', PARAM_RAW);
        
        $this->add_action_buttons(false);
  
    }
    //Custom validation should be added here
    function validation($data, $files) {
        return array();
    }
}
