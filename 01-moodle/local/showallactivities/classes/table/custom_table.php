<?php

namespace local_showallactivities\table;
require_once("$CFG->libdir/tablelib.php");
use stdClass;
use core_plugin_manager;
use html_writer;

class custom_table extends \table_sql{
    var $pagesize    = 50;

    function __construct($uniqueid) {
        parent::__construct($uniqueid);
        $columns = array(
            'name', 
            'coursename', 
            'rut',
            'nombre',
            'comuna',
            'fechasesion',
            'nombreadherente',
            'rutadherente',
            'nroadherente',
            'estado',
            'nota',
            'modalidad'
        );
        $this->define_columns($columns);    
        
        $headers = array(
            get_string('attendancename', 'local_showallactivities'),
            get_string('coursename', 'local_showallactivities'),
            get_string('rut', 'local_showallactivities'),
            get_string('username', 'local_showallactivities'),
            get_string('comuna', 'local_showallactivities'),
            get_string('datesession', 'local_showallactivities'),
            get_string('adherentename', 'local_showallactivities'),
            get_string('adherenterut', 'local_showallactivities'),
            get_string('adherentennumber', 'local_showallactivities'),
            get_string('state', 'local_showallactivities'),
            get_string('grade', 'local_showallactivities'),
            get_string('modalidad', 'local_showallactivities'),
        );
        $this->define_headers($headers);
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
    

    function other_cols($colname, $data){
        if($colname == 'modalidad'){
            return \local_resumencursos\utils\summary_utils::get_modalidad($data);
        }
    }
    
}