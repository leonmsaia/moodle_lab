<?php

defined('MOODLE_INTERNAL') || die();


class local_cron_testcase extends \advanced_testcase
{
    public $user1;
    public $user2;
    public $user3;
    public $user4;
    public $user5;
    public $user6;

    public $course;

    public $enrol1;
    public $enrol2;

    protected function setUp()
    {
        /** @var mysqli_native_moodle_database $DB */
        global $DB;
        $this->resetAfterTest(true);
        set_config('enablecompletion', true);
        set_config('days', 30, 'local_cron');
        set_config('local_cron_key_decrypt', 'abcdefghijkl');
        set_config('local_cron_endpoint', '');
        set_config('testing', true, 'local_cron');
        set_config('gradevalue', false);
        $this->course = $this->getDataGenerator()->create_course(array('enablecompletion' => true));
        $user_record1 = [
            "idnumber" => 1234
        ];
        $user_record2 = [
            "idnumber" => 1235
        ];

        $user_record3 = [
            "idnumber" => 1236
        ];

        $user_record4 = [
            "idnumber" => null
        ];

        $user_record5 = [
            "idnumber" => 1234567
        ];
        $user_record6 = [
            "idnumber" => 45789
        ];
        $user_record7 = [
            "idnumber" => null
        ];
        $this->user1 = $this->getDataGenerator()->create_user($user_record1);
        $this->user2 = $this->getDataGenerator()->create_user($user_record2);
        $this->user3 = $this->getDataGenerator()->create_user($user_record3);
        $this->user4 = $this->getDataGenerator()->create_user($user_record4);
        $this->user5 = $this->getDataGenerator()->create_user($user_record5);
        $this->user6 = $this->getDataGenerator()->create_user($user_record6);
        $this->user7 = $this->getDataGenerator()->create_user($user_record7);


        $this->getDataGenerator()->enrol_user($this->user1->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user2->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user4->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user5->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user6->id, $this->course->id);
        $this->getDataGenerator()->enrol_user($this->user7->id, $this->course->id);


        $instances = $DB->get_records('enrol', array('courseid' => $this->course->id, 'enrol' => 'manual'));
        $instance = reset($instances);
        $this->enrol1 = $DB->get_record('user_enrolments', array('userid' => $this->user1->id, 'enrolid' => $instance->id));
        $enrol2 = $DB->get_record('user_enrolments', array('userid' => $this->user6->id, 'enrolid' => $instance->id));

        // le resto 35 dias al timecreated para probar luego el reprobado por inasistencia
        $DB->update_record('user_enrolments', (object)["id" => $enrol2->id, "timecreated" => (time() - 60 * 60 * 24 * (int) get_config('local_cron', 'days') - 60 * 60 * 24 * 5)]);
        $this->enrol2 = $DB->get_record('user_enrolments', array('userid' => $this->user6->id, 'enrolid' => $instance->id));
    }

    public function test_general()
    {
        /** @var mysqli_native_moodle_database $DB */
        global $DB;

        $enrolledids = [$this->user1->id, $this->user2->id, $this->user5->id, $this->user6->id];


        $enrolments = \local_cron\utils::get_cron_enrolments();

        // deben haber 2 usuarios matriculados y deben ser los ids del array enrolledids
        $this->assertCount(4, $enrolments);

        foreach ($enrolments as $enrolment) {
            $this->assertContains($enrolment->userid, $enrolledids);
            $this->assertEquals($enrolment->courseid, $this->course->id);
        }
        // el user3 no debe estar matriculado
        $this->assertNotContains($this->user3->id, $enrolledids);
        // el usuario4 no debe ser considerado tampoco por no tener idnumber
        $this->assertNotContains($this->user4->id, $enrolledids);
        // el user1 y user2 son una inscripcion de cron
        $this->assertTrue(\local_cron\utils::is_cron_inscription($this->user1, $this->course->id));
        $this->assertTrue(\local_cron\utils::is_cron_inscription($this->user2, $this->course->id));
        // el user4 y user7 no es una inscripcion de cron por no tener idnumber
        $this->assertFalse(\local_cron\utils::is_cron_inscription($this->user4, $this->course->id));
        $this->assertFalse(\local_cron\utils::is_cron_inscription($this->user7, $this->course->id));

        $today = time();
        $days = $today + 60 * 60 * 24 * 5; // 5 dias pasados

        $intervalo5 = \local_cron\utils::interval($today, $days);
        $this->assertEquals(5, $intervalo5);
        $this->assertNotEquals(6, $intervalo5);

        $intervaloreverse = \local_cron\utils::interval($days, $today);
        $this->assertEquals(0, $intervaloreverse);

        // inserto un registro a la tabla local cron, este usuario ya no deberia ser considerado para evaluar el estado
        $DB->insert_record('mutual_log_local_cron', (object)[
            "status" => \local_cron\utils::STATUS_APROBADO,
            "userid" => $this->user2->id,
            "courseid" => $this->course->id,
            "timemodified" => time(),
            "gradeuser" => 80,
            "gradeapprovecourse" => 70
        ]);

        $enrolments2 = \local_cron\utils::get_cron_enrolments();

        // ahora deberia dar 3 porque 1 fue agregado a la tabla local cron y ya no se considera evaluable
        $this->assertCount(3, $enrolments2);
    }


    public function test_status()
    {
        $completion = new \completion_completion(array("course" => $this->course->id, "userid" => $this->user1->id));
        $completion->mark_complete();
        $status = \local_cron\utils::get_user_course_status($this->user1, $this->course, $this->enrol1->timecreated, (int)get_config('local_cron', 'days'));
        // no hay grade configurado
        $this->assertEquals(\local_cron\utils::STATUS_REPROBADO, $status->status);
        $this->assertEquals(0, $status->grade);


        $coursegradeitem = \grade_item::fetch_course_item($this->course->id);
        // se aprueba con mas de 75
        $coursegradeitem->gradepass = "75.00000";
        $coursegradeitem->update();
        $datagrade = 50;
        $grade_grade = new grade_grade();
        $grade_grade->itemid = $coursegradeitem->id;
        $grade_grade->userid = $this->user1->id;
        $grade_grade->rawgrade = $datagrade;
        $grade_grade->finalgrade = $datagrade;
        $grade_grade->rawgrademax = 100;
        $grade_grade->rawgrademin = 0;
        $grade_grade->timecreated = time();
        $grade_grade->timemodified = time();
        $grade = $grade_grade->insert();
        // Usuario reprobado
        $status = \local_cron\utils::get_user_course_status($this->user1, $this->course, $this->enrol1->timecreated, (int)get_config('local_cron', 'days'));
        $this->assertEquals(\local_cron\utils::STATUS_REPROBADO, $status->status);
        $this->assertEquals(50, $status->grade);


        // usuario aprobado
        $datagrade = 80;
        $grade_grade = new grade_grade(array('id' => $grade));
        $grade_grade->finalgrade = $datagrade;
        $grade_grade->rowgrade = $datagrade;
        $grade_grade->update();
        $status = \local_cron\utils::get_user_course_status($this->user1, $this->course, $this->enrol1->timecreated, (int)get_config('local_cron', 'days'));
        $this->assertEquals(\local_cron\utils::STATUS_APROBADO, $status->status);
        $this->assertEquals(80, $status->grade);

        // usuario en curso, aun no completo
        $status = \local_cron\utils::get_user_course_status($this->user6, $this->course, $this->enrol2->timecreated, (int)get_config('local_cron', 'days'));
        $this->assertEquals(\local_cron\utils::STATUS_REPROBADO_INASISTENCIA, $status->status);
        $this->assertEquals(0, $status->grade);
    }

    public function test_scheduled_task()
    {
        /** @var \mysqli_native_moodle_database $DB */
        global $DB;
        $completion = new \completion_completion(array("course" => $this->course->id, "userid" => $this->user1->id));
        $completion->mark_complete();
        $coursegradeitem = \grade_item::fetch_course_item($this->course->id);
        // se aprueba con mas de 75
        $coursegradeitem->gradepass = "75.00000";
        $coursegradeitem->update();
        $datagrade = 80;
        $grade_grade = new grade_grade();
        $grade_grade->itemid = $coursegradeitem->id;
        $grade_grade->userid = $this->user1->id;
        $grade_grade->rawgrade = $datagrade;
        $grade_grade->finalgrade = $datagrade;
        $grade_grade->rawgrademax = 100;
        $grade_grade->rawgrademin = 0;
        $grade_grade->timecreated = time();
        $grade_grade->timemodified = time();
        $grade_grade->insert();
        $task = \core\task\manager::get_scheduled_task("\\local_cron\\task\\finalizarCapacitacionElearnging");
        $task->execute();
        $enviados = $DB->get_records('mutual_log_local_cron');
        // solo estos usuarios debieron ser guardados
        $this->assertCount(2, $enviados);

        $enviadoaprobado = $DB->get_record('mutual_log_local_cron', ['userid' => $this->user1->id, 'courseid' => $this->course->id]);

        $this->assertEquals(\local_cron\utils::STATUS_APROBADO, $enviadoaprobado->status);
        $this->assertEquals(80, $enviadoaprobado->gradeuser);

        $enviadodesaprobado = $DB->get_record('mutual_log_local_cron', ['userid' => $this->user6->id, 'courseid' => $this->course->id]);

        $this->assertEquals(\local_cron\utils::STATUS_REPROBADO_INASISTENCIA, $enviadodesaprobado->status);
        $this->assertEquals(0, $enviadodesaprobado->gradeuser);
        

        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($this->course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->unenrol_user($instance, $this->user6->id);

        $totalcron = $DB->get_records('mutual_log_local_cron');

        // Al desmatricular se debe ejecutar el observer para borrar el estado en local cron
        $this->assertCount(1, $totalcron);

        $usuarioborradodecron = $DB->record_exists('mutual_log_local_cron', ['userid' => $this->user6->id, 'courseid' => $this->course->id]);

        // Comprobar si se borro el usuario adecuado
        $this->assertFalse($usuarioborradodecron);



        // genero una inconsistencia entre la matriculacion y la tabla cron para probar la tarea que deberia limpiarlos
        $ue = $DB->get_record('user_enrolments', array('userid' => $this->user1->id, 'enrolid' => $instance->id));
        $DB->update_record('user_enrolments', (object)["id" => $ue->id, "timecreated" => ((int)$enviadoaprobado->timemodified + 10)]);


        // agrego otro usuario a la tabla local cron
        $DB->insert_record('mutual_log_local_cron', (object)[
            "status" => \local_cron\utils::STATUS_APROBADO,
            "userid" => $this->user5->id,
            "courseid" => $this->course->id,
            "timemodified" => time(),
            "gradeuser" => 80,
            "gradeapprovecourse" => 70
        ]);

        // desmatriculo al usuario recien agregado, deberia ser borrado de local cron al ejecutar la tarea siguiente
        $enrol->unenrol_user($instance, $this->user5->id);
        
        // probar tarea programada que limpia la tabla cron
        $task = \core\task\manager::get_scheduled_task("\\local_cron\\task\\finalizarCapacitacionElearngingClearTable");
        $task->execute();

        $totalcron = $DB->get_records('mutual_log_local_cron');
        $this->assertEmpty($totalcron);
    }
}
