<?php
require_once 'includes/db.php';
$hcs = get_data('health_centers');
echo "ID | Name\n";
foreach($hcs as $hc) {
    echo $hc['HealthCenterID'] . " | " . $hc['Name'] . "\n";
}
