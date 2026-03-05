<?php
require_once 'includes/db.php';
global $db;
$tables = ['HCPatient', 'HCPatientRequisition', 'HCPatientRequisitionItem', 'HC_Patient', 'HC_PatientRequisition'];
foreach ($tables as $t) {
    try {
        $db->fetchAll("DESCRIBE $t");
        echo "$t exists\n";
    } catch (Exception $e) {
        echo "$t does not exist\n";
    }
}
