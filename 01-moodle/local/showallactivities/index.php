<?php
require_once('../../config.php');

$download = optional_param('download', '', PARAM_ALPHA);
$tsort = optional_param('tsort', '', PARAM_ALPHA);
$page = optional_param('page', '', PARAM_ALPHA);
$rut = optional_param('rut', '', PARAM_RAW);
$nombre = optional_param('nombre', '', PARAM_RAW);
$empresa = optional_param('empresa', '', PARAM_RAW);
$rutempresa = optional_param('rutempresa', '', PARAM_RAW);
$nroempresa = optional_param('nroempresa', '', PARAM_RAW);
$curso = optional_param('curso', '', PARAM_RAW);
$modalidadopresencial = optional_param('modalidadopresencial', '', PARAM_RAW);
$modalidadsemipresencial = optional_param('modalidadsemipresencial', '', PARAM_RAW);
$modalidaddistancia = optional_param('modalidaddistancia', '', PARAM_RAW);
$modalidadelearning = optional_param('modalidadelearning', '', PARAM_RAW);
$modalidadstreaming = optional_param('modalidadstreaming', '', PARAM_RAW);
$modalidadmobile = optional_param('modalidadmobile', '', PARAM_RAW);
$dateto = optional_param_array('dateto', '', PARAM_RAW);
$datefrom = optional_param_array('datefrom', '', PARAM_RAW);
$evaluacion = optional_param('evaluacion', '', PARAM_RAW);
$estadoabierto = optional_param('estadoabierto', '', PARAM_RAW);
$estadocerrado = optional_param('estadocerrado', '', PARAM_RAW);

//creo objeto para url de las paginas y data de formulario
$fromform = new stdClass();
$fromform->rut = $rut;
$fromform->nombre = $nombre;
$fromform->empresa = $empresa;
$fromform->rutempresa = $rutempresa;
$fromform->nroempresa = $nroempresa;
$fromform->curso = $curso;
$fromform->modalidadopresencial = $modalidadopresencial;
$fromform->modalidadsemipresencial = $modalidadsemipresencial;
$fromform->modalidaddistancia = $modalidaddistancia;
$fromform->modalidadelearning = $modalidadelearning;
$fromform->modalidadstreaming = $modalidadstreaming;
$fromform->modalidadmobile = $modalidadmobile;
$fromform->evaluacion = $evaluacion;
$fromform->estadocerrado = $estadoabierto;
$fromform->estadocerrado = $estadocerrado;

$url = new moodle_url('/local/showallactivities/index.php', (array)$fromform);

if(empty($dateto)){
    $date = explode('-', date("d-m-Y",strtotime(date("d-m-Y")." -1 month")));
    $dateto = array(
        'day' => $date[0],
        'month' => $date[1],
        'year' => $date[2],
    );
}
if(empty($datefrom)){
    $datefrom = array(
        'day' => date('d'),
        'month' => date('m'),
        'year' => date('Y'),
    );
}

$fromform->dateto = $dateto;
$fromform->datefrom = $datefrom;

$modalidad = "";
$where = \local_showallactivities\utils::get_where($fromform);

$PAGE->set_context(context_system::instance());
$PAGE->set_url($url);

$table = new local_showallactivities\table\custom_table('showallactivities');
$table->is_downloading($download, $download);

$select = 'rand(1000) as id, a.name , c.fullname as coursename, 
        s.groupid, u.username as rut, 
        CONCAT(u.firstname, " ", u.lastname) as nombre,
        sb.nombrecomuna as comuna,
        DATE_FORMAT(FROM_UNIXTIME(s.sessdate),"%d/%m/%Y") as fechasesion, 
        sb.nombreadherente, sb.rutadherente rutadherente,
        sb.numeroadherente as nroadherente,
        CASE
            WHEN sb.estado = "100000001" THEN "Abierto"
            WHEN sb.estado = "100000003" THEN "Cerrado"
            ELSE ""
        END as estado,
        aes.description as nota, 
        cb.tipomodalidad as modalidad,
        cb.modalidaddistancia';
$from = "{eabcattendance_sessions} as s JOIN {eabcattendance} as a ON a.id = s.eabcattendanceid 
        JOIN {course} as c ON c.id = a.course LEFT JOIN {groups_members} as gm on gm.groupid = s.groupid
        LEFT JOIN {user} as u ON u.id = gm.userid 
        LEFT JOIN {sesion_back} as sb ON sb.id_sesion_moodle = s.id 
        LEFT JOIN {eabcattendance_log} as al ON ((al.sessionid = s.id) && (al.studentid = u.id))
        LEFT JOIN {eabcattendance_statuses} AS aes ON aes.id = al.statusid
        LEFT JOIN {curso_back} AS cb ON cb.id_curso_moodle = c.id 
        ";

if ($table->is_downloading($download, $download)) {
    $table->set_sql($select, $from, '1=1 '.$where);
    $table->define_baseurl($url);
    $table->out(0, true);
    exit;
}

require_login();
require_capability('local/showallactivities:showallactivities', context_system::instance());


echo $OUTPUT->header();
echo "<h1>Filtro</h1>";
//Instantiate simplehtml_form 
$mform = new \local_showallactivities\form\filter_form_activities();

$mform->set_data($fromform);

//Form processing and displaying is done here
if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
    //$where = \local_showallactivities\utils::get_where($fromform); 
} 

  //displays the form
ob_start();
echo $mform->display();
$outputmform_body = ob_get_contents();
ob_end_clean();
echo '<div class="showfilters">';
echo $outputmform_body ;
echo "</div>";
echo '<button type="button" class="btn btn-info toggle-filter">'.get_string('showhidefilter', 'local_showallactivities').'</button>';

$table->set_sql($select, $from, '1=1 '.$where);
$table->define_baseurl($url);
$table->out(10, true);

$PAGE->requires->js_call_amd('local_showallactivities/showfilters', 'init');

echo $OUTPUT->footer();