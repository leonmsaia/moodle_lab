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

namespace tool_eabcetlbridge\output_handlers;

/**
 * Captures output for Web Service response instead of printing to CLI
 */
class ws_output_handler {

    private $logs = [];

    public function output($message) {
        // Limpiamos caracteres de nueva línea si existen al final
        $this->logs[] = trim($message);
    }

    public function get_logs() {
        return $this->logs;
    }
}
