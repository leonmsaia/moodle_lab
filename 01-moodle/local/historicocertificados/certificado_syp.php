<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php'); // Incluye la clase table_sql
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/historicocertificados/index.php'));
$PAGE->set_title(get_string('title_page_presencial', 'local_historicocertificados'));
$PAGE->set_heading(get_string('title_page_presencial', 'local_historicocertificados'));
$page_elearning = optional_param('page_elearning', 0, PARAM_INT);
$page_presencial = optional_param('page_presencial', 0, PARAM_INT);

// Encabezado
echo $OUTPUT->header();
// echo html_writer::tag('h3', get_string('title_page_presencial', 'local_historicocertificados'));

// =====================
// Clase personalizada
// =====================


class certificados_table_streaming_presencial extends table_sql {
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        $columns = array(
            'nombrecurso',
            'tipo_certificado',
            'disponibilidad',
        );
        $this->define_columns($columns);
        $headers = array(
            get_string('nombrecurso', 'local_resumencursos'),
            get_string('tipo_certificado', 'local_historicocertificados'),
            get_string('disponibilidad', 'local_resumencursos'),
        );
        $this->define_headers($headers);
    }

    /**
     * Lógica personalizada para la columna "disponibilidad"
     */
    public function col_disponibilidad($row) {
        global $OUTPUT;

        $request = \local_mutual\back\utils::get_certificado_from_back($row->iddocumento);
        try {
            if ($request['error']) {
                return null;
            } else {
                return '<a href="' . $request['data']->UrlArchivo . '" target="_blank">' . $OUTPUT->pix_icon('t/download', get_string('download')) . ' Descargar</a>';
           }
        } catch (\Throwable $th) {
            return null;
        }
    }

    // Formatea columnas específicas si deseas
    public function col_timecreated($row) {
        return userdate($row->timecreated, '%d/%m/%Y %H:%M');
    }
}


$table_presencial_streaming = new certificados_table_streaming_presencial('local_historicocertificados_presencial_streaming');
$sqlfields_presencial_streaming = 'c.*, 
cc.fullname as nombrecurso,
CASE WHEN c.tipodocumento = 1 THEN "Diploma" ELSE "Certificado" END AS tipo_certificado
';
$sqlfrom_presencial_streaming   = '{inscripciones_back_35} mcb 
join {certificados_back_35} c on c.idinscripcion = mcb.idinscripcion
left JOIN {eabcattendance_sessions_35} ats on ats.id = mcb.id_sesion_moodle
left JOIN {groups_35} g on g.id = ats.groupid
left JOIN {course_35} cc on cc.id = g.courseid
';
$sqlwhere_presencial_streaming  =  ' mcb.participanteidentificador ="' . $USER->username . '" ';

// Asigna SQL a la tabla
$table_presencial_streaming->set_sql($sqlfields_presencial_streaming, $sqlfrom_presencial_streaming, $sqlwhere_presencial_streaming);

// URL base
$table_presencial_streaming->define_baseurl( new moodle_url($PAGE->url, [
    'table' => 'presencial', 
    'tab' => 'presencial_streaming',
    'page_presencial' => $page_presencial
]));
$table_presencial_streaming->currpage = $page_presencial;

// Salida con paginación de 30 por página
// Generar salida de la tabla dentro de una vista con dos pestañas (tabs).
ob_start();
$table_presencial_streaming->out(10, true);
$tablehtml_presencial_streaming = ob_get_clean();

echo $tablehtml_presencial_streaming;

echo $OUTPUT->footer();
