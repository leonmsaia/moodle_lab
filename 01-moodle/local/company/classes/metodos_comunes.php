<?php

// (27/01/2020 FHS)
//Definicion de la clase metodos_comunes.

/* Contiene metodos para:

 * Crear cursos                                      [crear_curso($fullname, $short_name, $id_category)]
 * Borrar cursos										[borrar_curso($id)]
 * Actualizar cursos									[actualizar_curso($datos_curso)]
 */

namespace local_company;

use coding_exception;
use dml_exception;
use moodle_exception;
use stdClass;


defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $OUTPUT, $PAGE, $COURSE;

class metodos_comunes
{
/**
     * Gets the current users company ID depending on 
     * if the user is an admin and editing a company or is a
     * company user tied to a company.
     * @returns int
     */
    public static function get_my_companyid() {
        global $DB, $USER;
        if ($usercompanies = $DB->get_records('company_users', array('userid' => $USER->id), 'id', 'id,companyid', 0, 1)) {
            $usercompany = array_pop($usercompanies);
            return $usercompany->companyid;
        } else {
            return false;
        }
    }

    /**
     * Check to see if a user is associated to a company.
     *
     * Returns int or false;
     *
     **/
    public static function is_company_user ($user = null) {
        
    }

    /**
     * Get a users company id.
     *
     *
     **/
    public static function companyid($id) {
        global $DB;
        $company = $DB->get_records('company', array('id' => $id));
        if ($company) {
            return $company;
        } else {
            return false;
        }
    }


    static public function create_company($datacompany){
        global $DB, $CFG;
        $companyid = $DB->insert_record('company', $datacompany);
        $eventother = array('companyid' => $companyid);
        // $event = \block_comp_company_admin\event\company_created::create(array('context' => \context_system::instance(),
        //                                                                           'other' => $eventother));
        // $event->trigger();
        return $companyid;
    }

    /**
     * Return all companies as array of records
     */
    static public function get_all_companies() {
        global $DB;
        return $DB->get_records('company', null, 'id ASC');
    }

    /**
     * Return companies paginated and filtered by optional search term.
     * Returns array: list => records, total => totalcount
     */
    static public function get_companies_paginated($search = '', $page = 0, $perpage = 20) {
        global $DB;
        $params = array();
        $wheres = '';
        if (!empty($search)) {
            // Use distinct parameter names for each LIKE to satisfy DB drivers that
            // require a param per placeholder occurrence.
            // Search by name, shortname, city, rut or contrato
            $wheres = "WHERE name LIKE :search1 OR shortname LIKE :search2 OR city LIKE :search3 OR rut LIKE :search4 OR contrato LIKE :search5";
            $like = '%'.$search.'%';
            $params['search1'] = $like;
            $params['search2'] = $like;
            $params['search3'] = $like;
            $params['search4'] = $like;
            $params['search5'] = $like;
        }

        $offset = $page * $perpage;

        // LIMIT and OFFSET cannot reliably be passed as bound string parameters in all drivers,
        // build them as integers here to avoid quoted values causing SQL syntax errors.
        $limit = (int)$perpage;
        $offset = (int)$offset;

        if ($wheres) {
            $sql = "SELECT * FROM {company} " . $wheres . " ORDER BY name ASC LIMIT $limit OFFSET $offset";
        } else {
            $sql = "SELECT * FROM {company} ORDER BY id ASC LIMIT $limit OFFSET $offset";
        }

        $records = $DB->get_records_sql($sql, $params);

        // Get total count
        if (!empty($search)) {
            $countsql = "SELECT COUNT(1) FROM {company} " . $wheres;
            // pass the same parameters for the COUNT query
            $total = $DB->count_records_sql($countsql, $params);
        } else {
            $total = $DB->count_records('company');
        }

        return array('list' => $records, 'total' => $total);
    }

    /**
     * Update a company record. Expects an object with id set.
     */
    static public function update_company($company) {
        global $DB;
        if (empty($company->id)) {
            return false;
        }
        return $DB->update_record('company', $company);
    }

    /**
     * Return users not assigned to any company, optionally filtered by search (name or email).
     * Returns array('list'=>records, 'total'=>count)
     */
    static public function get_unassigned_users($search = '', $page = 0, $perpage = 20) {
        global $DB;
        $offset = (int)$page * (int)$perpage;
        $limit = (int)$perpage;

        $params = array();
        $where = " WHERE NOT EXISTS (SELECT 1 FROM {company_users} cu WHERE cu.userid = u.id) ";
        if (!empty($search)) {
            $where .= " AND (u.firstname LIKE :s1 OR u.lastname LIKE :s2 OR u.email LIKE :s3 OR u.username LIKE :s4)";
            $like = '%'.$search.'%';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        $sql = "SELECT u.* FROM {user} u " . $where . " ORDER BY u.lastname ASC LIMIT $limit OFFSET $offset";
        $records = $DB->get_records_sql($sql, $params);

        // total
        $countsql = "SELECT COUNT(1) FROM {user} u " . $where;
        $total = $DB->count_records_sql($countsql, $params);

        return array('list' => $records, 'total' => $total);
    }

    /**
     * Assign multiple users to a company by inserting into company_users.
     * Returns number assigned.
     */
    static public function assign_users_to_company($companyid, array $userids) {
        global $DB, $USER;
        $count = 0;
        foreach ($userids as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            // prevent duplicates
            if ($DB->record_exists('company_users', array('companyid' => $companyid, 'userid' => $uid))) {
                continue;
            }
            $rec = new stdClass();
            $rec->companyid = $companyid;
            $rec->userid = $uid;
            $rec->managertype = 0;
            $rec->departmentid = 0;
            $DB->insert_record('company_users', $rec);
            $count++;

            // --- Update user's custom profile fields with company data ---
            // Map of user_info_field shortname => company field name
            $company = $DB->get_record('company', array('id' => $companyid), '*', IGNORE_MISSING);
            if ($company) {
                $fieldmap = array(
                    'empresarut' => 'rut',
                    'empresarazonsocial' => 'name',
                    'empresacontrato' => 'contrato'
                );
                foreach ($fieldmap as $fieldshort => $companyfield) {
                    $uif = $DB->get_record('user_info_field', array('shortname' => $fieldshort));
                    if (!$uif) {
                        // field not present in the system, skip
                        continue;
                    }
                    $value = isset($company->{$companyfield}) ? $company->{$companyfield} : '';
                    // check existing data
                    $existing = $DB->get_record('user_info_data', array('userid' => $uid, 'fieldid' => $uif->id));
                    if ($existing) {
                        $existing->data = $value;
                        $DB->update_record('user_info_data', $existing);
                    } else {
                        $drec = new stdClass();
                        $drec->userid = $uid;
                        $drec->fieldid = $uif->id;
                        $drec->data = $value;
                        $DB->insert_record('user_info_data', $drec);
                    }
                }
            }
        }
        return $count;
    }

    /**
     * Return users assigned to a company, optionally filtered by search (name or email).
     * Returns array('list'=>records, 'total'=>count)
     */
    static public function get_assigned_users($companyid, $search = '', $page = 0, $perpage = 20) {
        global $DB;
        $offset = (int)$page * (int)$perpage;
        $limit = (int)$perpage;

        $params = array('companyid' => $companyid);
        $where = ' WHERE cu.companyid = :companyid ';
        if (!empty($search)) {
            $where .= " AND (u.firstname LIKE :s1 OR u.lastname LIKE :s2 OR u.email LIKE :s3 OR u.username LIKE :s4)";
            $like = '%'.$search.'%';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
            $params['s4'] = $like;
        }

        $sql = "SELECT u.*, cu.id as companyuserlinkid FROM {user} u JOIN {company_users} cu ON cu.userid = u.id " . $where . " ORDER BY u.lastname ASC LIMIT $limit OFFSET $offset";
        $records = $DB->get_records_sql($sql, $params);

        $countsql = "SELECT COUNT(1) FROM {user} u JOIN {company_users} cu ON cu.userid = u.id " . $where;
        $total = $DB->count_records_sql($countsql, $params);

        return array('list' => $records, 'total' => $total);
    }

    /**
     * Unassign multiple users from a company by deleting records in company_users.
     * Returns number unassigned.
     */
    static public function unassign_users_from_company($companyid, array $userids) {
        global $DB;
        $count = 0;
        foreach ($userids as $uid) {
            $uid = (int)$uid;
            if ($uid <= 0) {
                continue;
            }
            $deleted = $DB->delete_records('company_users', array('companyid' => $companyid, 'userid' => $uid));
            if ($deleted) {
                $count += $deleted;
            }
        }
        return $count;
    }

    static public function assign($companyid, $userid){
        global $DB;
        $id = $DB->insert_record('company_users', array('companyid' => $companyid, 'userid' => $userid));
        $eventother = array('id' => $id);
        // $event = \block_comp_company_admin\event\company_created::create(array('context' => \context_system::instance(),
        //                                                                           'other' => $eventother));
        // $event->trigger();
        // After assigning, update user's custom profile fields from company
        $company = $DB->get_record('company', array('id' => $companyid), '*', IGNORE_MISSING);
        if ($company) {
            $fieldmap = array(
                'empresarut' => 'rut',
                'empresarazonsocial' => 'name',
                'empresacontrato' => 'contrato'
            );
            foreach ($fieldmap as $fieldshort => $companyfield) {
                $uif = $DB->get_record('user_info_field', array('shortname' => $fieldshort));
                if (!$uif) {
                    continue;
                }
                $value = isset($company->{$companyfield}) ? $company->{$companyfield} : '';
                $existing = $DB->get_record('user_info_data', array('userid' => $userid, 'fieldid' => $uif->id));
                if ($existing) {
                    $existing->data = $value;
                    $DB->update_record('user_info_data', $existing);
                } else {
                    $drec = new stdClass();
                    $drec->userid = $userid;
                    $drec->fieldid = $uif->id;
                    $drec->data = $value;
                    $DB->insert_record('user_info_data', $drec);
                }
            }
        }

        return $id;
    }

    static public function unassign($userid, $companyid){
        global $DB;
        $DB->delete_records('company_users', array('companyid' => $companyid, 'userid' => $userid));

    }

}
