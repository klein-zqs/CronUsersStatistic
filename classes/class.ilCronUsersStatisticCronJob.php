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

        try {
            // Logic to record daily logins
            $yesterday = date("Y-m-d", strtotime("-1 day"));
            $query = "
                SELECT COUNT(DISTINCT usr_id) AS user_count
                FROM usr_data
                WHERE DATE(last_login) = " . $ilDB->quote($yesterday, "date");

            $res = $ilDB->query($query);
            $row = $ilDB->fetchAssoc($res);
            $user_count = $row['user_count'];

            // Generate the next id using the sequence
            $next_id = $ilDB->nextId("crn_usr_statistics");

            $ilDB->insert("crn_usr_statistics", [
                "id" => ["integer", $next_id],
                "stat_date" => ["date", $yesterday],
                "user_count" => ["integer", $user_count],
                "created_at" => ["timestamp", $ilDB->now()],
            ]);

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
