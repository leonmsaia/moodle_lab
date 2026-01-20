<?php

namespace local_pubsub\event;

class certificado_saved extends \core\event\base
{

    protected function init()
    {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name()
    {
        return "Certificado guardado";
    }

    public function get_description()
    {
        return json_encode($this->other['data']);
    }
}
