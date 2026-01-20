<?php
define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// CSV exportados desde 3.5
$csvHoldings     = __DIR__ . '/holdings.csv';       // oldholdingid, name
$csvUsers        = __DIR__ . '/users.csv';          // olduserid, username
$csvHoldingUsers = __DIR__ . '/holding_users.csv';  // id, holdingid, userid

// Función para leer CSV
function read_csv($file, $delimiter = ',') {
    $rows = [];
    if (($handle = fopen($file, "r")) !== false) {
        $headers = fgetcsv($handle, 0, $delimiter);
        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = array_combine($headers, $data);
        }
        fclose($handle);
    }
    return $rows;
}

// 1. Leer CSV
$oldHoldings     = read_csv($csvHoldings);
$oldUsers        = read_csv($csvUsers);
$oldHoldingUsers = read_csv($csvHoldingUsers);

echo "Leídos " . count($oldHoldings) . " holdings, "
              . count($oldUsers) . " users y "
              . count($oldHoldingUsers) . " relaciones.\n";

// 2. Mapeo Holdings (por nombre)
$mapHoldings = [];
foreach ($oldHoldings as $h) {
    if ($new = $DB->get_record('holding', ['name' => $h['name']], 'id', IGNORE_MULTIPLE)) {
        $mapHoldings[$h['oldholdingid']] = $new->id;
    } else {
        echo "WARNING: Holding no encontrado en nueva BD: {$h['name']}\n";
    }
}

// 3. Mapeo Users (por username)
$mapUsers = [];
foreach ($oldUsers as $u) {
    if (!empty($u['username']) && $new = $DB->get_record('user', ['username' => $u['username']], 'id', IGNORE_MULTIPLE)) {
        $mapUsers[$u['olduserid']] = $new->id;
    } else {
        echo "WARNING: User no encontrado en nueva BD (username={$u['username']})\n";
    }
}

echo "Mapeados " . count($mapHoldings) . " holdings y "
              . count($mapUsers) . " users.\n";

// 4. Insertar relaciones
$inserted = 0;
foreach ($oldHoldingUsers as $hu) {
    $oldHoldingId = $hu['holdingid'];
    $oldUserId    = $hu['userid'];

    if (!isset($mapHoldings[$oldHoldingId]) || !isset($mapUsers[$oldUserId])) {
        echo "WARNING: Relación saltada (holdingid=$oldHoldingId, userid=$oldUserId)\n";
        continue;
    }

    $newRecord = new stdClass();
    $newRecord->holdingid = $mapHoldings[$oldHoldingId];
    $newRecord->userid    = $mapUsers[$oldUserId];

    // Evitar duplicados
    if (!$DB->record_exists('holding_users', [
        'holdingid' => $newRecord->holdingid,
        'userid'    => $newRecord->userid
    ])) {
        $DB->insert_record('holding_users', $newRecord);
        $inserted++;
    }
}

echo "Inserción completa: $inserted relaciones creadas en mdl_holding_users.\n";
