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

/**
 * Manage faq page
 *
 * @package    local_help
 * @copyright 2019 Osvaldo Arriola <osvaldo@e-abclearning.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


include_once('../../../config.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/tablelib.php');

$id          = optional_param('id', null, PARAM_INT);

if(empty($id)){
    redirect($CFG->wwwroot, get_string('noaccess', 'format_eabctiles'));
}
/** @var core_renderer $OUTPUT */
global $OUTPUT, $CFG, $PAGE;
$disabledsuspend = "";
$textsuspend = "";
$course = $DB->get_record('course', array('id' => $id));
//admin_externalpage_setup('local_help_managefaq');
$thisurl = new moodle_url('/course/format/eabctiles/closeactivity.php');
$datatable = array();

try {
    $context = context_course::instance($course->id, MUST_EXIST);
    require_login($course);
    if (!has_capability('format/eabctiles:closegroup',  context_course::instance($course->id, MUST_EXIST))) {
            throw new coding_exception(get_string('noaccess', 'format_eabctiles'));
    }
    
    $PAGE->set_context($context);
    $PAGE->set_url('/course/format/eabctiles/closeactivity.php', array(
        'courseid' => $course->id,
    ));
    $PAGE->set_heading($course->fullname);
    $PAGE->set_pagelayout('course');
    $PAGE->set_title(get_string('closeactivity', 'format_eabctiles'));
    $PAGE->navbar->add(get_string('closeactivity', 'format_eabctiles'), new moodle_url('/course/format/eabctiles/closeactivity.php', array(
        'id' => $course->id,
    )));
    
    $PAGE->requires->css( '/course/format/eabctiles/scss/alertify.css');
    
    echo $OUTPUT->header();
    
    if(is_siteadmin()){
        $groups = groups_get_all_groups($id);
    } else {
        $groups = groups_get_all_groups($id, $USER->id);
    }

    // Show a table of installed availability conditions.
    $table = new flexible_table('format_eabctiles');
    $table->define_columns(array('group', 'close', 'suspend'));
    $table->define_headers(array(get_string('activities'),
            get_string('closeactivities', 'format_eabctiles'), get_string('suspend', 'format_eabctiles')));
    $table->define_baseurl($PAGE->url);
    $table->set_attribute('id', 'closeandsuspendgroup');
    $table->set_attribute('class', 'closeandsuspendgroupclass');
    $table->setup();

    foreach ($groups as $group){
        $groupclose = $DB->get_record("format_eabctiles_closegroup", array("groupid" => $group->id));
        if(!empty($groupclose)){
            if($groupclose->status == 0){
                $title = get_string('close', 'format_eabctiles');
                $text = get_string('finalize', 'format_eabctiles');
                $disabled = "";
                $disabledsuspend = "";
                $textsuspend = "Suspender";
                $statusclose = $groupclose->status;
            }else {
                $disabledsuspend = "disabled";
                $textsuspend = get_string('blockbuttonsuspend', 'format_eabctiles');
                $statusclose = $groupclose->status;
                if (has_capability('format/eabctiles:button_open_group', $context)) {
                    $title = get_string('open', 'format_eabctiles');
                    $text = get_string('open', 'format_eabctiles');
                    $disabled = "";
                } else {
                    $title = get_string('finalizadotext', 'format_eabctiles');
                    $text = get_string('finalizado', 'format_eabctiles');
                    $disabled = "disabled";
                }
            }
        } else {
            $session = $DB->get_record('eabcattendance_sessions', array('groupid' => $group->id));
            $carga_masiva = $DB->get_records('eabcattendance_carga_masiva', array('guid_sesion' => $session->guid, 'recibido' => 0));

            $disabled_carga_masiva = '';
            $title_session = get_string('close', 'format_eabctiles');
            if(!empty($carga_masiva)){
                $disabled_carga_masiva = 'disabled';
                $title_session = 'Opción bloqueada por inscripciones pendientes por procesar en carga masiva';
            }
            $title = $title_session;
            $text = get_string('finalize', 'format_eabctiles');
            $disabled = $disabled_carga_masiva;
            $disabledsuspend = "";
            $statusclose = 0;
        }
        
        
        $table->add_data(array(
                $group->name, 
                //boton cerrar y abrir grupo
                $OUTPUT->render_from_template(
                        'format_eabctiles/button_close_group', 
                        array(
                            "groupid" => $group->id,
                            "courseid" => $id,
                            "title" => $title,
                            "text" => $text,
                            "disabled" => $disabled,
                            "statusclose" => $statusclose
                            )
                        ),
                //boton suspender grupo
                    $OUTPUT->render_from_template(
                        'format_eabctiles/button_suspend_group', 
                        array(
                            "groupid" => $group->id,
                            "courseid" => $id,
                            "title" => $textsuspend,
                            "text" => get_string('suspend', 'format_eabctiles'),
                            "disabled" => '',
                            "disabledsuspend" => $disabledsuspend
                            )
                        )
                    )
                );
        
    }
    echo $table->print_html();
    
    $suspension_motive_array = \format_eabctiles\utils\eabctiles_utils::get_closequiestions_configtiles();
    
    $suspension_motiveopen_array = \format_eabctiles\utils\eabctiles_utils::get_openquiestions_configtiles();
    
    $PAGE->requires->string_for_js('suspendactivity', 'format_eabctiles');
    $PAGE->requires->string_for_js('confirmsuspendactivity', 'format_eabctiles');
    $PAGE->requires->strings_for_js(array('cancel', 'confirm', 'msgalertclose', 'msgalertopen', 'closeactivity'), 'format_eabctiles');
    $PAGE->requires->js_call_amd('format_eabctiles/closegroup', 'init', array());
    $PAGE->requires->js_call_amd('format_eabctiles/suspendgroup', 'init', array(array('reasons' => $suspension_motive_array, 'reasonsopen' => $suspension_motiveopen_array)));
    
    echo $OUTPUT->footer();
} catch (coding_exception $e) {
    throw new moodle_exception("errormsg", "format_eabctiles", '', $e->getMessage(), $e->debuginfo);
} catch (moodle_exception $e) {
    throw new moodle_exception("errormsg", "format_eabctiles", '', $e->getMessage(), $e->debuginfo);
}
