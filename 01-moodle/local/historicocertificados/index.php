<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php'); // Incluye la clase table_sql
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/historicocertificados/index.php'));
$PAGE->set_title('Histórico de Certificados');
$PAGE->set_heading('Histórico de Certificados');

// Encabezado
echo $OUTPUT->header();
echo html_writer::tag('h3', 'Histórico de Certificados');

// =====================
// Clase personalizada
// =====================
class certificados_table extends table_sql {
    function __construct($uniqueid)
    {
        parent::__construct($uniqueid);
        $columns = array(
            'nombrecurso',
            // 'horas',
            'modalidad',
            // 'final_date',
            // 'grade',
            'disponibilidad',
        );
        $this->define_columns($columns);
        $headers = array(
            get_string('nombrecurso', 'local_resumencursos'),
            // get_string('time', 'local_resumencursos'),
            get_string('modalidad', 'local_resumencursos'),
            // get_string('caducidad', 'local_resumencursos'),
            // get_string('calificacion', 'local_resumencursos'),
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

    // public function __construct($uniqueid) {
    //     parent::__construct($uniqueid);

    //     // Define las columnas y encabezados
    //     $columns = [
    //         'id',
    //         'iddocumento',
    //         'idinscripcion',
    //         'modalidad',
    //         'tipodocumento',
    //         'fechaexpiracion',
    //         'timecreated'
    //     ];

    //     $headers = [
    //         'ID',
    //         'ID Documento',
    //         'ID Inscripción',
    //         'Modalidad',
    //         'Tipo Documento',
    //         'Fecha Expiración',
    //         'Creado'
    //     ];

    //     $this->define_columns($columns);
    //     $this->define_headers($headers);

    //     $this->sortable(true, 'id', SORT_DESC);
    //     $this->collapsible(false);
    //     $this->pageable(true);
    //     $this->set_attribute('class', 'generaltable generalbox');
    // }

    // Formatea columnas específicas si deseas
    public function col_timecreated($row) {
        return userdate($row->timecreated, '%d/%m/%Y %H:%M');
    }
}

// =====================
// Crear instancia
// =====================
$table = new certificados_table('local_historicocertificados');

// SQL principal
$sqlfields = 'c.*, cc.fullname as nombrecurso';
$sqlfrom   = '{inscripcion_elearning_back_35} mcb 
join {certificados_back_35} c on c.idinscripcion = mcb.participanteidregistroparticip
join {user_35} u on u.id = mcb.id_user_moodle
left join {course_35} cc on cc.id = mcb.id_curso_moodle

';
$sqlwhere  = 'u.username = "' . $USER->username . '"';

// Asigna SQL a la tabla
$table->set_sql($sqlfields, $sqlfrom, $sqlwhere);

// URL base
$table->define_baseurl($PAGE->url);

// Salida con paginación de 30 por página
$table->out(10, true);

echo $OUTPUT->footer();
