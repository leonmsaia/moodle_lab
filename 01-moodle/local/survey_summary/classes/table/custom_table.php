<?php

namespace local_survey_summary\table;
require_once("$CFG->libdir/tablelib.php");
use stdClass;
use core_plugin_manager;
use html_writer;
use moodle_url;

class custom_table extends \table_sql{
    var $pagesize    = 50;
    
    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array(
            'courseid',
            'horas',
            'modalidad',
        );
        $this->define_columns($columns);
        $headers = array(
            get_string('nombrecurso', 'local_resumencursos'),
            get_string('time', 'local_resumencursos'),
            get_string('modalidad', 'local_resumencursos'),
        );
        $this->define_headers($headers);
    }

    function other_cols($colname, $data){
        global $DB, $CFG, $USER, $OUTPUT;
        if($colname == 'courseid'){
            
            $course = $DB->get_record('course', array('id' => $data->courseid));
            $id = "";
            $url = "";
            $modinfo = get_fast_modinfo($course);
            $get_feedbacks = $modinfo->get_instances_of("feedback");
            $get_questionnaires = $modinfo->get_instances_of("questionnaire");
            if (!empty($get_feedbacks)) {
                foreach ($get_feedbacks as $get_feedback) {
                    if ($get_feedback->deletioninprogress == 0 && $get_feedback->visible == 1) {
                        $id = $get_feedback->id;
                        break;
                    }
                }
                $url = new moodle_url($CFG->wwwroot . '/mod/feedback/analysis.php', array('id' => $id));
            } else if (!empty($get_questionnaires)) {
                foreach ($get_questionnaires as $get_questionnaire) {
                    if ($get_questionnaire->deletioninprogress == 0 && $get_questionnaire->visible == 1) {
                        $id = $get_questionnaire->instance;
                        break;
                    }
                }
                $url = new moodle_url($CFG->wwwroot . '/mod/questionnaire/report.php', array('instance' => $id));
            }

            if(empty($url)){
                $url = new moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $course->id));
            }
            if ($this->is_downloading()) {
                return $course->fullname;
            } else {
                return html_writer::link($url, $course->fullname, array('target' => '_blank'));
            }
            
        }
        if($colname == 'modalidad'){
            return \local_resumencursos\utils\summary_utils::get_modalidad($data);
        }
    }


    /**
     * Get the html for the download buttons
     *
     * Usually only use internally
     */
    public function download_buttons() {
        global $OUTPUT;

        if ($this->is_downloadable() && !$this->is_downloading()) {
            return $this->download_dataformat_selector_table(get_string('downloadas', 'table'),
                    $this->baseurl->out_omit_querystring(), 'download', $this->baseurl->params());
        } else {
            return '';
        }
    }
    
    
    /**
     * Returns a dataformat selection and download form
     *
     * @param string $label A text label
     * @param moodle_url|string $base The download page url
     * @param string $name The query param which will hold the type of the download
     * @param array $params Extra params sent to the download page
     * @return string HTML fragment
     */
    public function download_dataformat_selector_table($label, $base, $name = 'dataformat', $params = array()) {
        global $OUTPUT;
        $formats = core_plugin_manager::instance()->get_plugins_of_type('dataformat');
        $options = array();
        $selected = false;
        foreach ($formats as $format) {
            if ($format->is_enabled()) {
                $selected = ($format->name == 'excel') ? true : false;
                $options[] = array(
                    'value' => $format->name,
                    'label' => get_string('dataformat', $format->component),
                    'selected' => $selected
                );
            }
        }
        $hiddenparams = array();
        foreach ($params as $key => $value) {
            $hiddenparams[] = array(
                'name' => $key,
                'value' => $value,
            );
        }
        $data = array(
            'label' => $label,
            'base' => $base,
            'name' => $name,
            'params' => $hiddenparams,
            'options' => $options,
            'sesskey' => sesskey(),
            'submit' => get_string('download'),
        );

        return $OUTPUT->render_from_template('local_showallactivities/dataformat_selector', $data);
    }
}