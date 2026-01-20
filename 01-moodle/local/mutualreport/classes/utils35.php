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

namespace local_mutualreport;

defined('MOODLE_INTERNAL') || die;

use coding_exception;
use dml_exception;
use moodle_database;
use local_mutualreport\local_external_db_connection as external_db_connection;

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

    /**
     * Gets companies for a user, with search capabilities.
     *
     * @param string $username The username to search for
     * @param bool $includeall Whether to include an 'All' option in the returned array
     * @return array An array with company IDs as keys and company names as values
     */
    public function get_companies_from_username_options($username, $includeall = false) {

        $companies = [];

        if (is_siteadmin()) {
            // Los administradores pueden ver todas las empresas.
            $companies = $this->db->get_records_menu('company', null, 'name ASC', 'id, name');
        } else {
            // Para otros usuarios, obtenemos las empresas a las que tienen acceso.
            $sql = "SELECT DISTINCT c.id, c.name
                      FROM {company} c
                      JOIN (
                          -- Empresas a través de holdings.
                          SELECT hc.companyid
                            FROM {holding_companies} hc
                            JOIN {holding_users} hu ON hu.holdingid = hc.holdingid
                            JOIN {user} u ON u.id = hu.userid
                           WHERE u.username = :username1
                          UNION
                          -- Empresas asignadas directamente.
                          SELECT cu.companyid
                            FROM {company_users} cu
                            JOIN {user} u ON u.id = cu.userid
                           WHERE u.username = :username2
                      ) AS usercompanies ON usercompanies.companyid = c.id
                  ORDER BY c.name ASC";

            $params = [
                'username1' => $username,
                'username2' => $username
            ];
            $records = $this->db->get_records_sql($sql, $params);

            if ($records) {
                foreach ($records as $record) {
                    $companies[$record->id] = $record->name;
                }
            }
        }

        if ($includeall) {
            $alloption = ['' => get_string('all')];
            $companies = $alloption + $companies;
        }

        return $companies;
    }

    /**
     * Returns an array of course records with id and name fields.
     *
     * @return array
     */
    public function get_courses_options() {
        $notsitesql = $this->db->sql_equal('id', ':site', false, false, true);
        $params = ['site' => 1];

        return $this->db->get_records_select_menu(
            'course',
            $notsitesql,
            $params,
            'fullname ASC',
            'id, fullname'
        );
    }

    /**
     * Search companies for a user in the external DB, with search capabilities.
     *
     * @param string $username The user's username.
     * @param string $query The search query.
     * @return array An array of company objects with id and name.
     */
    public function search_companies_for_user(string $username, string $query = ''): array {
        if (!$this->validate_connection()) {
            return [];
        }

        $params = [];
        $searchcondition = '';

        if (!empty($query)) {
            $searchsql = $this->db->sql_like('c.name', ':name', false, false) .
                ' OR ' . $this->db->sql_like('c.rut', ':rut', false, false) .
                ' OR ' . $this->db->sql_like('c.contrato', ':contrato', false, false);
            $searchcondition = "AND ($searchsql)";
            $params['name'] = '%' . $query . '%';
            $params['rut'] = '%' . $query . '%';
            $params['contrato'] = '%' . $query . '%';
        }

        if (is_siteadmin()) {
            $sql = "SELECT c.id, c.name
                      FROM {company} c
                     WHERE 1=1 $searchcondition
                  ORDER BY c.name ASC";
        } else {
            $sql = "SELECT DISTINCT c.id, c.name
                      FROM {company} c
                      JOIN (
                          -- Empresas a través de holdings.
                          SELECT hc.companyid
                            FROM {holding_companies} hc
                            JOIN {holding_users} hu ON hu.holdingid = hc.holdingid
                            JOIN {user} u ON u.id = hu.userid
                           WHERE u.username = :username1
                          UNION
                          -- Empresas asignadas directamente.
                          SELECT cu.companyid
                            FROM {company_users} cu
                            JOIN {user} u ON u.id = cu.userid
                           WHERE u.username = :username2
                      ) AS usercompanies ON usercompanies.companyid = c.id
                     WHERE 1=1 $searchcondition
                  ORDER BY c.name ASC";

            $params['username1'] = $username;
            $params['username2'] = $username;
        }

        $companies = $this->db->get_records_sql($sql, $params);

        return array_values($companies);
    }
}
