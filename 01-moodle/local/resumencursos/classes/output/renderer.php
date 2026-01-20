<?php

namespace local_resumencursos\output;

class renderer extends \renderer_base{
    public function print_hello($user) {
        $data = new \stdClass();
        $data->nombre = $user->firstname;
        $data->apellido = $user->lastname;
        return $this->render_from_template('local_resumencursos/hello', $data);
    }
}
