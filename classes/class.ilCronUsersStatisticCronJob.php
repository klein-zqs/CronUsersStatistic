<?php

include_once("./Services/Cron/classes/class.ilCronJob.php");
use ILIAS\Cron\Schedule\CronJobScheduleType;

class ilCronUsersStatisticCronJob extends ilCronJob
{
    public function getId() : string
    {
        return "crn_usr_statistics";
    }

    public function getTitle() : string
    {
        return "User activity Statistics";
    }

    public function getDescription() : string
    {
        return ilCronUsersStatisticPlugin::getInstance()->txt("cron_description");
    }

    public function run(): ilCronJobResult
    {
        global $ilDB, $ilLog;
        $result = new ilCronJobResult();
        $ilLog->write("UserStatisticsCronJob: Starting cron job.");
        $cron_users_statistic_repository = new CronUsersStatisticRepository($ilDB);

        try {
            // Logic to record daily logins
            $yesterday = date("Y-m-d", strtotime("-1 day"));
            $user_count = $cron_users_statistic_repository->getUserCount($yesterday);

            // Generate the next id using the sequence
            $next_id = $cron_users_statistic_repository->getNextId();
            $cron_users_statistics_info = new CronUsersStatisticInfo($next_id, $yesterday, $user_count, $ilDB->now());
            $cron_users_statistic_repository->insert($cron_users_statistics_info);

            $ilLog->write("UserStatisticsCronJob: Recorded $user_count users for date $yesterday.");

            $result->setStatus(ilCronJobResult::STATUS_OK);
        } catch (Exception $e) {
            $ilLog->write("UserStatisticsCronJob: Error occurred - " . $e->getMessage());
            $result->setStatus(ilCronJobResult::STATUS_CRASHED);
        }

        $ilLog->write("UserStatisticsCronJob: Cron job finished.");
        return $result;
    }

    public function hasAutoActivation(): bool
    {
        return true;
    }

    public function hasFlexibleSchedule(): bool
    {
        return false;
    }

    public function getDefaultScheduleType(): CronJobScheduleType
    {
        return CronJobScheduleType::SCHEDULE_TYPE_DAILY;
    }

    public function getDefaultScheduleValue(): int
    {
        return 1;  // Run once a day
    }
}
