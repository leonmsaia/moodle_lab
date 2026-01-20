<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php'); // Incluye la clase table_sql
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/historicocertificados/index.php'));
$PAGE->set_title(get_string('title_page_elearning', 'local_historicocertificados'));
$PAGE->set_heading(get_string('title_page_elearning', 'local_historicocertificados'));
$page_elearning = optional_param('page_elearning', 0, PARAM_INT);
$page_presencial = optional_param('page_presencial', 0, PARAM_INT);

// Encabezado
echo $OUTPUT->header();
// echo html_writer::tag('h3', get_string('title_page_elearning', 'local_historicocertificados'));

// =====================
// Clase personalizada
// =====================
class certificados_table extends table_sql {
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



// =====================
// Crear instancia
// =====================
$table_elearning = new certificados_table('local_historicocertificados_elearning');

// SQL principal
$sqlfields = "c.*, 
cc.fullname as nombrecurso,
CASE WHEN c.tipodocumento = 1 THEN 'Diploma' ELSE 'Certificado' END AS tipo_certificado
";
$sqlfrom   = '{inscripcion_elearning_back_35} mcb 
join {certificados_back_35} c on c.idinscripcion = mcb.participanteidregistroparticip
join {user_35} u on u.id = mcb.id_user_moodle
left join {course_35} cc on cc.id = mcb.id_curso_moodle

';
$sqlwhere  = 'u.username = "' . $USER->username . '"' ;

// Asigna SQL a la tabla
$table_elearning->set_sql($sqlfields, $sqlfrom, $sqlwhere);

// URL base
$table_elearning->define_baseurl(new moodle_url($PAGE->url, [
    'table' => 'elearning' , 
    'tab' => 'elearning',
    'page_elearning' => $page_elearning,
]));
$table_elearning->currpage = $page_elearning;

// Salida con paginación de 30 por página
// Generar salida de la tabla dentro de una vista con dos pestañas (tabs).
ob_start();
$table_elearning->out(10, true);
$tablehtml = ob_get_clean();


echo $tablehtml;

echo $OUTPUT->footer();
