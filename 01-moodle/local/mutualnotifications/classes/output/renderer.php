<?php

namespace local_mutualnotifications\output;

class renderer extends \renderer_base {
    public function save_data_setting($data){
        $enrolment = $data->enrolment;
        $enrolment = ($enrolment)? $enrolment : 0;
        
        $fiftypercentfromenrolment = $data->fiftypercentfromenrolment;
        $fiftypercentfromenrolment = ($fiftypercentfromenrolment)? $fiftypercentfromenrolment : 0;
        
        $seventyfivepercentfromenrolment = $data->seventyfivepercentfromenrolment;
        $seventyfivepercentfromenrolment = ($seventyfivepercentfromenrolment)? $seventyfivepercentfromenrolment : 0;
        
        $finished = $data->finished;
        $finished = ($finished)? $finished : 0;
        
        set_config("enrolment" . $data->courseid, $enrolment, "local_mutualnotifications");
        set_config("fiftypercentfromenrolment" . $data->courseid, $fiftypercentfromenrolment, "local_mutualnotifications");
        set_config("seventyfivepercentfromenrolment" . $data->courseid, $seventyfivepercentfromenrolment, "local_mutualnotifications");
        set_config("finished" . $data->courseid, $finished, "local_mutualnotifications");
        
    }
}
