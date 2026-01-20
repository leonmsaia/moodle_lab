<?php

class mail {
    
    public static function sendMail()
    {
        global  $COURSE, $DB, $USER;

        $userto = $DB->get_record('user', array('id' => 4));

        $message = new \core\message\message();
        $message->component = 'moodle';
        $message->name = 'instantmessage';
        $message->userfrom = $USER;
        $message->userto = $userto;
        $message->subject = 'Vencimiento de plazo para cierre de curso';
        $message->fullmessage = 'El curso XXX está a punto de vencer en 20 minutos, por favor cierrelo';
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = '<p>El curso XXX está a punto de vencer en 20 minutos, por favor cierrelo</p>';
        $message->smallmessage = 'small message';
        $message->notification = '0';
        $message->contexturl = '';
        $message->contexturlname = 'Context name';
        $message->replyto = "alain@e-abclearning.com";
        $content = array('*' => array('header' => ' test ', 'footer' => ' test ')); // Extra content for specific processor
        $message->set_additional_content('email', $content);
        $message->courseid = $COURSE->id; // This is required in recent versions, use it from 3.2 on https://tracker.moodle.org/browse/MDL-47162
        message_send($message);
        
    }
}