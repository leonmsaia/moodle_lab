<?php

namespace local_pubsub\task;

class get_message extends \core\task\scheduled_task {
 
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('get_message', 'local_pubsub');
    }
 
    /**
     * Execute the task.
     */
    public function execute() {
		
		global $CFG, $DB;
		
	
		$msg = \local_pubsub\sistema_get::get_message();				//Mensaje JSON del backend, 
//		$fp = fopen($CFG->dirroot."/local/pubsub/registro.txt", "a");	//Archivo para los logs
		
		while($msg != false){
			
			$ahora= date('l jS \of F Y h:i:s A');
	
	//creacion de cursos		
			if(strcmp ($msg->accion , "Alta") == 0){ 
				try {
					
					
					$nombre_curso = $msg->mensaje[get_config('local_pubsub', 'coursename')];
					$nombre_corto = $msg->mensaje[get_config('local_pubsub', 'courseshortname')];
					$id_categoria = 1; //$msg->mensaje[get_config('local_pubsub', 'coursecategory')];
					echo "\nAlta curso: ". $nombre_curso ." - guid: ".$msg->guid ." ";
			
					$curso_id =  \local_pubsub\metodos_comunes::crear_curso($nombre_curso, $nombre_corto, $id_categoria);

		//asocio el ID del curso con el guid del backend			
					$dataObj = new \stdClass();
					$dataObj->courseid = $curso_id; 
					$dataObj->guid = $msg->guid;
					$DB->insert_record('eabcattendance_course_gu', $dataObj);
					
//					fputs($fp, $ahora . " ... " . "Creado el curso: ".$nombre_curso.", El guid: ". $msg->guid."\n\n");		 //LOGS 
							
				} catch (Exception $e) {
					echo "<h3>".$e->getMessage()."</h3>";
//					fputs($fp, $ahora . " ... " . "Problema al crear curso: ".$e->getMessage().", El guid: ". $msg->guid."\n\n");		 //LOGS 
//					fclose($fp);
				}
				
				
			}
			
			
	//actualizacion de cursos		
			if(strcmp ($msg->accion , "Actualizacion") == 0){ 
				
				$nombre_curso = $msg->mensaje[get_config('local_pubsub', 'coursename')];
				
		//Esta registrado en el sistema ese curso?
				if (!$DB->record_exists('eabcattendance_course_gu', array('guid' => $msg->guid))){
						
//					fputs($fp, $ahora . " ... " . "ERROR: Actualizacion del curso  ". $nombre_curso ." Fallida: El guid: ". $msg->guid ." no coincide con ninguno de los cursos registrados\n\n");		  //LOGS
					$msg = \local_pubsub\sistema_get::get_message();					
					continue;
				}
				
				
				try{
					
				//busco el id de curso que corresponde al id del producto curso del backend
					$registro = $DB->get_record('eabcattendance_course_gu', array('guid' => $msg->guid));
							
					$data = new \stdClass();	
					$data->id = $registro->courseid;
					$data->shortname = $msg->mensaje[get_config('local_pubsub', 'courseshortname')];
					$data->fullname = $msg->mensaje[get_config('local_pubsub', 'coursename')];
					$data->category = 1; // $msg->mensaje[get_config('local_pubsub', 'coursecategory')];
					$data->summary = $msg->mensaje['Descripcion'] . " <br>Horas de cursado: " . $msg->mensaje['Horas'] . " <br> Modalidad: " . $msg->mensaje['Modalidad'] . " <br> Objetivos: " . $msg->mensaje['Objetivo']." <br> Objetivos: Tematica:" . $msg->mensaje['Tematica'];
					
					echo "\nActualizacion curso: ". $data->fullname ." - guid: ".$msg->guid ." ";
									
							
					\local_pubsub\metodos_comunes::actualizar_curso($data);

//					fputs($fp, $ahora . " ... " . "Actualizado el curso: ".$nombre_curso.", El guid: ". $msg->guid."\n\n");		//LOGS
		
				}catch (Exception $e) {
					echo "<h3>".$e->getMessage()."</h3>";
//					fputs($fp, $ahora . " ... " . "Problema Actualizado el curso: ". $nombre_curso .": ".$e->getMessage()."\nEl guid: ".$msg->guid."\n\n");		 //LOGS 
//					fclose($fp);
				}
				
			}
			
			$msg = \local_pubsub\sistema_get::get_message();
			
			
		}
		
//		fclose($fp);
		
    }
}
