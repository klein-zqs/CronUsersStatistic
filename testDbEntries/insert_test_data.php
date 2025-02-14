<?php
// Skript zum Erstellen von Nutzerzahlen zum Testen
// Ausführen aus ilias root dir mit:
// /usr/local/opt/php@8.2/bin/php Customizing/global/plugins/Services/Cron/CronHook/CronUsersStatistic/testDbEntries/insert_test_data.php

if (php_sapi_name() === 'cli') {
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SERVER_NAME'] = 'localhost';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['SCRIPT_NAME'] = '/insert_test_data.php';
}

include_once("Customizing/global/plugins/Services/Cron/CronHook/CronUsersStatistic/classes/CronUsersStatisticInfo.php");
include_once("Customizing/global/plugins/Services/Cron/CronHook/CronUsersStatistic/classes/CronUsersStatisticRepository.php");
require_once "Services/Init/classes/class.ilInitialisation.php";

ilInitialisation::initILIAS();

global $DIC;
$db = $DIC->database();
$cron_users_statistic_repository = new CronUsersStatisticRepository($db);


$startDate = new DateTime('2024-01-01');
$endDate = new DateTime('2025-02-28');

$interval = new DateInterval('P1D'); // 1 Tag
$period = new DatePeriod($startDate, $interval, $endDate);

foreach ($period as $date) {
    $stat_date = $date->format('Y-m-d');
    $user_count = rand(50, 500); // Zufällige Nutzeranzahl zwischen 50 und 500
    
    $next_id = $cron_users_statistic_repository->getNextId();
    $cron_users_statistics_info = new CronUsersStatisticInfo($next_id, $stat_date, $user_count, $db->now());
    $cron_users_statistic_repository->insert($cron_users_statistics_info);
}

echo "✅ Testdaten erfolgreich eingefügt!";
?>