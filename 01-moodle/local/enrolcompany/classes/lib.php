<?php

namespace local_enrolcompany;


use dml_exception;
use file_exception;
use stored_file;
use stored_file_creation_exception;

defined('MOODLE_INTERNAL') || die();

class lib
{

    public static function create_item_for_file($line, $extradata, $columns)
    {
        $total = [];
        $i = 0;
        foreach ($line as $item) {
            $total[$columns[$i]] = $item;
            $i++;
        }
        return array_merge($total, $extradata);
    }

    /**
     * @param $data
     * @return stored_file
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     */
    public static function create_file($data)
    {
        $strfile = '';
        $filearray = [];
        // proceso la columna solamente
        foreach ($data[0] as $key => $dato) {
            $filearray[] = $key;
        }
        $strfile .= implode(';', $filearray)."\n";

        foreach ($data as $datos) {
            $rowarray = [];
            foreach ( $datos as $dato) {
                $rowarray[] = $dato;
            }
            $strfile .= implode(';', $rowarray)."\n";
        }

        $fs = get_file_storage();
        $filerecord = [
            "contextid" => \context_system::instance()->id,
            "component" => "local_enrolcompany",
            "filearea" => "inscripciones",
            "filepath" => "/",
            "itemid" => time(),
            "filename" => "Archivo".(string)time().'.csv'
        ];

        return $fs->create_file_from_string($filerecord, $strfile);
    }
}
