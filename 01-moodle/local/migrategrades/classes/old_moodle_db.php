<?php

namespace local_migrategrades;

defined('MOODLE_INTERNAL') || die();

class old_moodle_db {
    /** @var \mysqli|null */
    private $mysqli;

    public function __construct() {
        $this->mysqli = null;
    }

    public function connect(): void {
        global $CFG;

        if ($this->mysqli instanceof \mysqli) {
            return;
        }

        // Prefer $CFG->moodle35_db when provided (reuse settings), otherwise fall back to plugin config.
        $usecfg = isset($CFG->moodle35_db) && is_array($CFG->moodle35_db);

        if ($usecfg) {
            $cfg = $CFG->moodle35_db;

            $host = $cfg['dbhost'] ?? '';
            $user = $cfg['dbuser'] ?? '';
            $pass = $cfg['dbpass'] ?? '';
            $dbname = $cfg['dbname'] ?? '';

            // Port: prefer dboptions['dbport'], then dbport top-level, then 3306.
            $port = 3306;
            if (!empty($cfg['dboptions']) && is_array($cfg['dboptions']) && isset($cfg['dboptions']['dbport'])) {
                $port = (int)$cfg['dboptions']['dbport'];
            } else if (isset($cfg['dbport'])) {
                $port = (int)$cfg['dbport'];
            }

            // Charset: prefer dboptions['dbcharset'], then derive from dboptions['dbcollation'],
            // otherwise default to utf8mb4.
            $charset = 'utf8mb4';
            if (!empty($cfg['dboptions']) && is_array($cfg['dboptions'])) {
                if (!empty($cfg['dboptions']['dbcharset'])) {
                    $charset = $cfg['dboptions']['dbcharset'];
                } else if (!empty($cfg['dboptions']['dbcollation'])) {
                    // e.g. "utf8mb4_unicode_ci" => "utf8mb4"
                    $coll = $cfg['dboptions']['dbcollation'];
                    $parts = explode('_', $coll);
                    if (!empty($parts[0])) {
                        $charset = $parts[0];
                    }
                }
            }
        } else {
            // Fallback to plugin settings.
            $host = get_config('local_migrategrades', 'old_dbhost');
            $port = (int)get_config('local_migrategrades', 'old_dbport') ?: 3306;
            $dbname = get_config('local_migrategrades', 'old_dbname');
            $user = get_config('local_migrategrades', 'old_dbuser');
            $pass = get_config('local_migrategrades', 'old_dbpass');
            $charset = get_config('local_migrategrades', 'old_dbcharset') ?: 'utf8mb4';
        }

        if (empty($host) || empty($dbname) || empty($user)) {
            throw new \moodle_exception('dbsettings', 'local_migrategrades');
        }

        $mysqli = new \mysqli($host, $user, $pass, $dbname, $port);
        if ($mysqli->connect_errno) {
            throw new \moodle_exception('dberror', 'error', '', null, 'No se pudo conectar a la BD vieja: ' . $mysqli->connect_error);
        }

        if (!$mysqli->set_charset($charset)) {
            // Non-fatal; keep connection but do not throw.
        }

        $this->mysqli = $mysqli;
    }

    public function close(): void {
        if ($this->mysqli instanceof \mysqli) {
            $this->mysqli->close();
        }
        $this->mysqli = null;
    }

    public function ping(): void {
        $this->connect();

        // Simple query to validate connectivity.
        $res = $this->mysqli->query('SELECT 1');
        if ($res === false) {
            throw new \moodle_exception('dberror', 'error', '', null, 'Ping failed: ' . $this->mysqli->error);
        }
        if ($res instanceof \mysqli_result) {
            $res->free();
        }
    }

    public function prefix(): string {
        $prefix = get_config('local_migrategrades', 'old_dbprefix');
        if ($prefix === null || $prefix === '') {
            $prefix = 'mdl_';
        }
        return $prefix;
    }

    public function get_one(string $sql, array $params) {
        $this->connect();
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new \moodle_exception('dberror', 'error', '', null, 'Prepare failed: ' . $this->mysqli->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = array();
            foreach ($params as $p) {
                if (is_int($p)) {
                    $types .= 'i';
                } else if (is_float($p)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $p;
            }
            $stmt->bind_param($types, ...$values);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new \moodle_exception('dberror', 'error', '', null, 'Execute failed: ' . $err);
        }

        // Prefer get_result() when available (mysqlnd). Fallback otherwise.
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            return $row;
        }

        $meta = $stmt->result_metadata();
        if (!$meta) {
            $stmt->close();
            return null;
        }

        $fields = $meta->fetch_fields();
        $row = array();
        $bind = array();
        foreach ($fields as $f) {
            $row[$f->name] = null;
            $bind[] = &$row[$f->name];
        }

        call_user_func_array(array($stmt, 'bind_result'), $bind);
        $fetched = $stmt->fetch();
        $stmt->close();

        if ($fetched === null || $fetched === false) {
            return null;
        }

        // Ensure a plain array (not references).
        return array_map(function($v) { return $v; }, $row);
    }

    public function get_all(string $sql, array $params) : array {
        $this->connect();
        $stmt = $this->mysqli->prepare($sql);
        if (!$stmt) {
            throw new \moodle_exception('dberror', 'error', '', null, 'Prepare failed: ' . $this->mysqli->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = array();
            foreach ($params as $p) {
                if (is_int($p)) {
                    $types .= 'i';
                } else if (is_float($p)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
                $values[] = $p;
            }
            $stmt->bind_param($types, ...$values);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new \moodle_exception('dberror', 'error', '', null, 'Execute failed: ' . $err);
        }

        // Prefer get_result() when available (mysqlnd).
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            $out = array();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $out[] = $row;
                }
            }
            $stmt->close();
            return $out;
        }

        $meta = $stmt->result_metadata();
        if (!$meta) {
            $stmt->close();
            return array();
        }

        $fields = $meta->fetch_fields();
        $row = array();
        $bind = array();
        foreach ($fields as $f) {
            $row[$f->name] = null;
            $bind[] = &$row[$f->name];
        }
        call_user_func_array(array($stmt, 'bind_result'), $bind);

        $out = array();
        while (true) {
            $fetched = $stmt->fetch();
            if ($fetched === null || $fetched === false) {
                break;
            }
            $out[] = array_map(function($v) { return $v; }, $row);
        }
        $stmt->close();
        return $out;
    }
}
