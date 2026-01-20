<?php
define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Rutas de los CSV exportados desde Moodle 3.5
$csvHoldings   = __DIR__ . '/holdings.csv';
$csvCompanies  = __DIR__ . '/companies.csv';
$csvRelations  = __DIR__ . '/relations.csv';

// --------------------------------------
// Función para leer CSV en array asociativo
// --------------------------------------

$logfile = $CFG->dataroot . '/migrate_holdings.log';
file_put_contents($logfile, "[".date('Y-m-d H:i:s')."] Inicio script\n", FILE_APPEND);

echo "Iniciando migración de holdings y companies...\n";

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

// --------------------------------------
// 1. Leer CSV de origen
// --------------------------------------
$oldHoldings   = read_csv($csvHoldings);   // oldholdingid, name
$oldCompanies  = read_csv($csvCompanies);  // oldcompanyid, name, shortname, rut, contrato
$oldRelations  = read_csv($csvRelations);  // relid, holdingid, companyid

echo "Leídos " . count($oldHoldings) . " holdings, " . count($oldCompanies) . " companies y " . count($oldRelations) . " relaciones.\n";

// --------------------------------------
// 2. Crear mapeo oldid -> newid
// --------------------------------------

// Holdings: mapear por name
$mapHoldings = [];
foreach ($oldHoldings as $h) {
    if ($new = $DB->get_record('holding', ['name' => $h['name']], 'id', IGNORE_MULTIPLE)) {
        $mapHoldings[$h['oldholdingid']] = $new->id;
    } else {
        echo "WARNING: Holding no encontrado en nueva BD: {$h['name']}\n";
    }
}

// Companies: mapear por rut (único)
$mapCompanies = [];
foreach ($oldCompanies as $c) {
    if (!empty($c['rut']) && $new = $DB->get_record('company', ['rut' => $c['rut']], 'id', IGNORE_MULTIPLE)) {
        $mapCompanies[$c['oldcompanyid']] = $new->id;
    } else {
        echo "WARNING: Company no encontrada en nueva BD (rut={$c['rut']}, name={$c['name']})\n";
    }
}

echo "Mapeados " . count($mapHoldings) . " holdings y " . count($mapCompanies) . " companies.\n";

// --------------------------------------
// 3. Insertar relaciones en mdl_holding_companies
// --------------------------------------
$inserted = 0;
foreach ($oldRelations as $r) {
    $oldHoldingId = $r['holdingid'];
    $oldCompanyId = $r['companyid'];

    if (!isset($mapHoldings[$oldHoldingId]) || !isset($mapCompanies[$oldCompanyId])) {
        echo "WARNING: Relación saltada (holdingid=$oldHoldingId, companyid=$oldCompanyId)\n";
        continue;
    }

    $newRecord = new stdClass();
    $newRecord->holdingid = $mapHoldings[$oldHoldingId];
    $newRecord->companyid = $mapCompanies[$oldCompanyId];

    // Evitar duplicados
    if (!$DB->record_exists('holding_companies', [
        'holdingid' => $newRecord->holdingid,
        'companyid' => $newRecord->companyid
    ])) {
        $DB->insert_record('holding_companies', $newRecord);
        $inserted++;
    }
}

echo "Inserción completa: $inserted relaciones creadas en mdl_holding_companies.\n";

file_put_contents($logfile, "[".date('Y-m-d H:i:s')."] Fin script\n", FILE_APPEND);