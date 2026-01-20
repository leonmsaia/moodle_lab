<?php

namespace local_help\output;

class renderer extends \renderer_base {

    /*
    Get all faqs
     *      */
    public function get_faqs(){
        global $DB;
        return $DB->get_records("local_help");
    }
    
    /*
    Get faq
     param id especific faq
     */
    public function get_faq_by_id($id){
        global $DB;
        return $DB->get_record("local_help", array("id" => $id));
    }
    
    /*
    Return object parse to array
     param objecto 
     */
    public function parse_object_to_array($object){
        $array = array();
        foreach ($object as $obj){
            array_push($array, (array)($obj));
        }
        return $array;
    }
}
