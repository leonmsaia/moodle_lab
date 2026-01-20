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
 * Plugin administration pages are defined here.
 *
 * @package     holdingmng
 * @category    admin
 * @copyright   2020 e-ABC Learning <contacto@e-abclearning.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

require_once("$CFG->libdir/formslib.php");
 
class holdingcompanies_form extends moodleform {

     /** @var int */
     protected $holdingid;

    public function __construct($holdingid) {
        $this->holdingid = $holdingid;
        parent::__construct();
    }

    public function definition() {
        global $CFG, $DB;
 
        $mform = $this->_form;

        //companies
        $companies = $DB->get_records('holding_companies', ['holdingid'=>$this->holdingid]);

        //usuarios
        $query = "select * from {company} 
                  where id not in (select companyid 
                                     from {holding_companies} 
                                     where holdingid = ".$this->holdingid.")";

        $companies = $DB->get_records_sql($query);

        $options = array(                                                                                                           
            'multiple' => true,                                                  
            'noselectionstring' => get_string('selectcompany', 'local_holdingmng'),                                                                
        );

        $records = [];
        foreach ($companies as $company) {
            $records[$company->id] = $company->name.', '.$company->contrato;
        }

        $mform->addElement('autocomplete', 'companyid', get_string('company', 'local_holdingmng'), $records, $options);
        $mform->setType('companyid', PARAM_RAW);
        $mform->addElement('hidden', 'action', '');
        $mform->setType('action', PARAM_TEXT);
        $mform->addElement('hidden', 'holdingid', 0);
        $mform->setType('holdingid', PARAM_INT);
        $mform->addElement('submit', 'submitbutton', get_string('savechanges'));
        $mform->addElement('cancel');
    }

    //Custom validation should be added here
    function validation($data, $files) {
        return [];
    }
}
