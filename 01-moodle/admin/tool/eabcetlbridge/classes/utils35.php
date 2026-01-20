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

namespace tool_eabcetlbridge;

use moodle_database;
use tool_eabcetlbridge\local_external_db_connection as external_db_connection;

/**
 * Temporal Class to manage queries to DB35
 */
class utils35 {

    /** @var \moodle_database External database connection */
    public $db = null;

    /**
     * Constructor method. Attempts to establish a connection to the Moodle 3.5 database.
     * If the connection fails, it simply returns without throwing an exception.
     *
     * @return void
     */
    public function __construct() {
        try {
            $this->db = external_db_connection::get_moodle35_connection();
        } catch (\Exception) {
            return;
        }
    }

    /**
     * Validates the external database connection.
     *
     * @return bool True if connection is valid and working
     */
    public function validate_connection() {
        return external_db_connection::validate_connection($this->db);
    }

}
