<?php
// check_users.php
require_once 'includes/db.php';

$users = get_data('users');
echo "ID | Username | Role | HC ID\n";
echo "---|----------|------|-------\n";
foreach ($users as $u) {
    echo $u['UserID'] . " | " . $u['Username'] . " | " . $u['Role'] . " | " . ($u['HealthCenterID'] ?? 'NULL') . "\n";
}
