<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace tool_eabcetlbridge\tasks\adhoc;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/csvlib.class.php');
require_once($CFG->dirroot . '/grade/import/lib.php');

use csv_export_writer;

use core\task\adhoc_task;
use Exception;
use stdClass;
use core\context\system;
use tool_eabcetlbridge\repositories\externalmoodle;
use tool_eabcetlbridge\persistents\planners\user_grade_migration\courses;
use tool_eabcetlbridge\persistents\{batch_files, configs};

/**
 * Populate id mapping
 *
 * @package   tool_eabcetlbridge
 * @category  tasks
 * @copyright 2025 e-ABC Learning <contacto@e-abclearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_external_grades_and_create_data_batch extends adhoc_task {

    /** @var courses The course planner */
    protected $courseplanner;

    /** @var stdClass The course */
    protected $course;

    /** @var int The page */
    protected $page = 0;

    /** @var int The page size */
    protected $pagesize = 1000;

    /** @var string The separator */
    protected $separator = 'comma';

    /**
     * Get a descriptive name for this task.
     * @return string
     */
    public function get_name() {
        return get_string('get_external_grades_and_create_data_batch_task', 'tool_eabcetlbridge');
    }

    /**
     * Get an instance of the task with the given custom data.
     *
     * @param int $idnumber The idnumber to use.
     * @param class-string<courses>[] $class The class of id map to use.
     * @param int $page The page to use (default: 0).
     * @return self
     */
    public static function instance(
        int $plannerid,
        string $class,
        int $page = 0
    ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'plannerid' => $plannerid,
            'class' => $class,
            'page' => $page
        ]);

        return $task;
    }

    /**
     * Initialize the task.
     *
     * @throws Exception If the batch file or its associated configuration does not exist.
     */
    public function init() {

        $data = $this->get_custom_data();
        $plannerid = $data->plannerid ?? false;
        /** @var class-string<courses> $class The class of id map */
        $class = $data->class ?? false;
        $page = $data->page ?? 0;

        if (!$plannerid) {
            throw new Exception("No se ha recibido el plannerid del planificador");
        }

        if (!class_exists($class)) {
            throw new Exception("La clase {$class} no existe");
        }

        $this->courseplanner = $class::get_record(['id' => $plannerid]);
        $this->course = get_course($this->courseplanner->get('courseid'));
        $this->page = $page;

        // Page size of grade for request and for the batchfile.
        // Is secure to be in config??
        $this->pagesize = 1000;

    }


    /**
     * Execute the task.
     */
    public function execute() {

        try {
            $this->init();
            $this->process();
        } catch (Exception $e) {
            mtrace("Error al procesar el planificador {$this->courseplanner->get('id')} " .
                   "asociado al curso {$this->courseplanner->get('id')}: {$e->getMessage()}");
            $this->courseplanner->set('status', $this->courseplanner::STATUS_FAILED);
            $this->courseplanner->save();
        }
    }

    /**
     * Procesa el planificador y obtiene las calificaciones del curso asosiado.
     * Luego, crea un archivo CSV con las calificaciones y lo almacena en
     * la carpeta de archivos de batch_files.
     *
     * @throws Exception If the batch file or its associated configuration does not exist.
     */
    public function process() {
        $withpagination = get_config('tool_eabcetlbridge', 'get_grades_with_pagination');
        if ($withpagination == 20) {
            $this->process_without_pagination();
        } else {
            $this->process_with_pagination();
        }
    }

    /**
     * Procesa el planificador y obtiene las calificaciones del curso asosiado.
     * Luego, crea un archivo CSV con las calificaciones y lo almacena en
     * la carpeta de archivos de batch_files.
     *
     * @throws Exception If the batch file or its associated configuration does not exist.
     */
    public function process_with_pagination() {

        // Solo la primera ejecución (página 0) marca como 'procesando'.
        if ($this->page === 0) {
            $this->courseplanner->set('status', $this->courseplanner::STATUS_PROCESSING);
            $this->courseplanner->save();
        }

        $grades = externalmoodle::get_grades_by_local_course_shortname_with_pagination(
                $this->course->shortname,
                $this->page,
                $this->pagesize
        );
        if (empty($grades) || empty($grades['usergrades'])) {
            mtrace("No hay calificaciones en la página {$this->page} para el curso id " .
                   "{$this->course->id} ({$this->course->shortname})");

            // Si no hay más páginas y no hay datos, consideramos completado.
            if (empty($grades['pagination']['hasmorepages'])) {
                $this->courseplanner->set('status', $this->courseplanner::STATUS_COMPLETED);
                $this->courseplanner->save();
            }
            return;
        }

        $filename = "{$this->course->shortname}_batch_{$this->page}_automatic_export.csv";
        $csvexport = new csv_export_writer($this->separator);
        $csvexport->set_filename($filename);

        // The first header is always username, followed by all the grade item names.
        $csvheaders = array_merge(['username'], $grades['headers']);
        $csvexport->add_data($csvheaders);

        $qtylines = 1;
        $qtyrecords = 0;

        foreach ($grades['usergrades'] as $usergrade) {
            $row = [];
            $row[] = $usergrade['username'];

            // For each header, find the corresponding grade.
            // This ensures the order is correct and handles missing grades for a user.
            foreach ($grades['headers'] as $itemname) {
                $row[] = $usergrade['grades'][$itemname] ?? '';
            }

            $csvexport->add_data($row);
            $qtylines++;
            $qtyrecords++;
        }

        // Get the default configuration for grades migration to associate with the batch file.
        $config = configs::get_default_or_create();

        $filepath = "/";
        $batchfile = new batch_files(0, (object) [
            'type' => batch_files::TYPE_AUTOMATED,
            'configid' => $config->get('id'),
            'courseid' => $this->course->id,
            'delimiter' => $this->separator,
            'filepath' => $filepath,
            'filename' => $filename,
            'qtylines' => $qtylines,
            'qtyrecords' => $qtyrecords,
        ]);
        $batchfile->create();

        $context = system::instance();
        $fileinfo = [
            'contextid' => $context->id,
            'component' => $batchfile->get('component'),
            'filearea'  => $batchfile->get('filearea'),
            'itemid'    => $batchfile->get('id'),
            'filepath'  => $filepath,
            'filename'  => $filename,
            'mimetype'  => 'text/csv',
        ];

        $fs = get_file_storage();
        $fs->create_file_from_string($fileinfo, $csvexport->print_csv_data(true));

        // 3. DECIDIR SI CONTINUAR
        $pagination = $grades['pagination'];
        if ($pagination->hasmorepages) {
            $totalpages = 0;
            if ($pagination->totalusers > 0 && $pagination->pagesize > 0) {
                $totalpages = $pagination->totalusers / $pagination->pagesize;
            }
            mtrace("Lote para página {$this->page} creado. Encolando la siguiente página.");
            mtrace("Total de usuarios: {$pagination->totalusers}");
            mtrace("Tamaño de la página: {$pagination->pagesize}");
            mtrace("Página actual: {$pagination->currentpage}");
            mtrace("Total aproximado de paginas: {$totalpages}");
            mtrace("Más páginas: {$pagination->hasmorepages}");

            // La Clave Se encola a sí misma para la siguiente página.
            $nexttask = self::instance(
                $this->courseplanner->get('id'),
                get_class($this->courseplanner),
                $this->page + 1
            );
            \core\task\manager::queue_adhoc_task($nexttask);

        } else {
            mtrace("Último lote (página {$this->page}) creado para el curso {$this->course->shortname}. Proceso finalizado.");
            mtrace("Total de usuarios: {$pagination->totalusers}");
            mtrace("Tamaño de la página: {$pagination->pagesize}");
            mtrace("Página actual: {$pagination->currentpage}");
            mtrace("Más páginas: {$pagination->hasmorepages}");

            // El campo batchfileid en el planner de curso ya no tiene sentido,
            // porque ahora hay múltiples archivos. Se podría eliminar o dejar nulo.
            $this->courseplanner->set('batchfileid', 0);
            $this->courseplanner->set('status', $this->courseplanner::STATUS_COMPLETED);
            $this->courseplanner->save();
        }

    }

    /**
     * Procesa el planificador y obtiene las calificaciones del curso asosiado.
     */
    protected function process_without_pagination() {
        $this->courseplanner->set('status', $this->courseplanner::STATUS_PROCESSING);
        $this->courseplanner->save();

        $grades = externalmoodle::get_grades_by_local_course_shortname($this->course->shortname);
        if (empty($grades) || empty($grades['usergrades'])) {
            mtrace("No hay calificaciones para el curso id {$this->course->id} ({$this->course->shortname})");
            // If there are no grades, we can consider it completed, as there is nothing to process.
            $this->courseplanner->set('status', $this->courseplanner::STATUS_COMPLETED);
            $this->courseplanner->save();
            return;
        }

        $filename = "{$this->course->shortname}_automatic_export.csv";
        $csvexport = new csv_export_writer($this->separator);
        $csvexport->set_filename($filename);

        // The first header is always username, followed by all the grade item names.
        $csvheaders = array_merge(['username'], $grades['headers']);
        $csvexport->add_data($csvheaders);

        $qtylines = 1;
        $qtyrecords = 0;

        foreach ($grades['usergrades'] as $usergrade) {
            $row = [];
            $row[] = $usergrade['username'];

            // For each header, find the corresponding grade.
            // This ensures the order is correct and handles missing grades for a user.
            foreach ($grades['headers'] as $itemname) {
                $row[] = $usergrade['grades'][$itemname] ?? '';
            }

            $csvexport->add_data($row);
            $qtylines++;
            $qtyrecords++;
        }

        // Get the default configuration for grades migration to associate with the batch file.
        $config = configs::get_default_or_create();

        $filepath = "/";
        $batchfile = new batch_files(0, (object) [
            'type' => batch_files::TYPE_AUTOMATED,
            'configid' => $config->get('id'),
            'courseid' => $this->course->id,
            'delimiter' => $this->separator,
            'filepath' => $filepath,
            'filename' => $filename,
            'qtylines' => $qtylines,
            'qtyrecords' => $qtyrecords,
        ]);
        $batchfile->create();

        $context = system::instance();
        $fileinfo = [
            'contextid' => $context->id,
            'component' => $batchfile->get('component'),
            'filearea'  => $batchfile->get('filearea'),
            'itemid'    => $batchfile->get('id'),
            'filepath'  => $filepath,
            'filename'  => $filename,
            'mimetype'  => 'text/csv',
        ];

        $fs = get_file_storage();
        $fs->create_file_from_string($fileinfo, $csvexport->print_csv_data(true));

        $this->courseplanner->set('status', $this->courseplanner::STATUS_COMPLETED);
        $this->courseplanner->set('batchfileid', $batchfile->get('id'));
        $this->courseplanner->save();
    }

}
