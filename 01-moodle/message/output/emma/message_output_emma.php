<?php

use core\message\message;
use core_message\api;

global $CFG;
require_once($CFG->dirroot.'/message/output/lib.php');
require_once('classes/message_procesor.php');

class message_output_emma extends message_output {
    /**
     * @param stdClass|message $message
     * @return bool
     * @throws coding_exception
     */
    function send_message($message): bool
    {
        global $CFG;

        // skip any messaging suspended and deleted users
        if ($message->userto->auth === 'nologin' or $message->userto->suspended or $message->userto->deleted) {
            return true;
        }

        //the user the email is going to
        $recipient = null;

        //check if the recipient has a different email address specified in their messaging preferences Vs their user profile
        $emailmessagingpreference = get_user_preferences('message_processor_emma_email', null, $message->userto);
        $emailmessagingpreference = clean_param($emailmessagingpreference, PARAM_EMAIL);

        // If the recipient has set an email address in their preferences use that instead of the one in their profile
        // but only if overriding the notification email address is allowed
        if (!empty($emailmessagingpreference) && !empty($CFG->messagingallowemailoverride)) {
            //clone to avoid altering the actual user object
            $recipient = clone($message->userto);
            $recipient->email = $emailmessagingpreference;
        } else {
            $recipient = $message->userto;
        }

        // Check if we have attachments to send.
        $attachment = '';
        $attachname = '';
        $attachpatch = '';
        if (!empty($CFG->allowattachments) && !empty($message->attachment)) {
            if (empty($message->attachname)) {
                // Attachment needs a file name.
                debugging('Attachments should have a file name. No attachments have been sent.', DEBUG_DEVELOPER);
            } else if (!($message->attachment instanceof stored_file)) {
                // Attachment should be of a type stored_file.
                debugging('Attachments should be of type stored_file. No attachments have been sent.', DEBUG_DEVELOPER);
            } else {
                // Copy attachment file to a temporary directory and get the file path.
                $attachment = $message->attachment->copy_content_to_temp();
                $attachpatch = $message->attachment->get_filepath();

                // Get attachment file name.
                $attachname = clean_filename($message->attachname);
            }
        }

        $message_procesor   = new \emma\message\message_procesor();
        $result = $message_procesor->enviadirecto($message, $attachpatch);

        // Remove an attachment file if any.
        if (!empty($attachment) && file_exists($attachment)) {
            unlink($attachment);
        }

        return $result;
    }

    /**
     * @param stdClass $preferences
     * @return string
     * @throws coding_exception
     */
    function config_form($preferences): string
    {
        global $USER, $OUTPUT, $CFG;
        $string = '';

        $choices = array();
        $choices['0'] = get_string('textformat');
        $choices['1'] = get_string('htmlformat');
        $current = $preferences->mailformat;
        $string .= $OUTPUT->container(html_writer::label(get_string('emailformat'), 'mailformat'));
        $string .= $OUTPUT->container(html_writer::select($choices, 'mailformat', $current, false, array('id' => 'mailformat')));
        $string .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'userid', 'value' => $USER->id));

        if (!empty($CFG->allowusermailcharset)) {
            $choices = array();
            $charsets = get_list_of_charsets();
            if (!empty($CFG->sitemailcharset)) {
                $choices['0'] = get_string('site').' ('.$CFG->sitemailcharset.')';
            } else {
                $choices['0'] = get_string('site').' (UTF-8)';
            }
            $choices = array_merge($choices, $charsets);
            $current = $preferences->mailcharset;
            $string .= $OUTPUT->container(html_writer::label(get_string('emailcharset'), 'mailcharset'));
            $string .= $OUTPUT->container(
                html_writer::select($choices, 'preference_mailcharset', $current, false, array('id' => 'mailcharset'))
            );
        }

        if (!empty($CFG->messagingallowemailoverride)) {
            $inputattributes = array('size' => '30', 'name' => 'emma_email', 'value' => $preferences->emma_email,
                'id' => 'emma_email');
            $string .= html_writer::label(get_string('email', 'message_email'), 'emma_email');
            $string .= $OUTPUT->container(html_writer::empty_tag('input', $inputattributes));

            if (empty($preferences->emma_email) && !empty($preferences->userdefaultemail)) {
                $string .= $OUTPUT->container(get_string('ifemailleftempty', 'message_email', $preferences->userdefaultemail));
            }

            if (!empty($preferences->emma_email) && !validate_email($preferences->emma_email)) {
                $string .= $OUTPUT->container(get_string('invalidemail'), 'error');
            }

            $string .= '<br/>';
        }

        return $string;
    }

    /**
     * @param stdClass $form
     * @param array $preferences
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    function process_form($form, &$preferences){
        global $CFG;

        if (isset($form->emma_email)) {
            $preferences['message_processor_emma_email'] = clean_param($form->emma_email, PARAM_EMAIL);
        }
        if (isset($form->preference_mailcharset)) {
            $preferences['mailcharset'] = $form->preference_mailcharset;
            if (!array_key_exists($preferences['mailcharset'], get_list_of_charsets())) {
                $preferences['mailcharset'] = '0';
            }
        }
        if (isset($form->mailformat) && isset($form->userid)) {
            require_once($CFG->dirroot.'/user/lib.php');

            $user = core_user::get_user($form->userid, '*', MUST_EXIST);
            $user->mailformat = clean_param($form->mailformat, PARAM_INT);
            user_update_user($user, false, false);
        }
    }

    /**
     * Returns the default message output settings for this output
     *
     * @return int The default settings
     */
    public function get_default_messaging_settings(): int
    {
        return  ESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF;
    }

    /**
     * @param array $preferences
     * @param int $userid
     * @throws coding_exception
     */
    function load_data(&$preferences, $userid){
        $preferences->emma_email = get_user_preferences( 'message_processor_emma_email', '', $userid);
    }

    /**
     * Returns true as message can be sent to internal support user.
     *
     * @return bool
     */
    public function can_send_to_any_users(): bool
    {
        return true;
    }
}