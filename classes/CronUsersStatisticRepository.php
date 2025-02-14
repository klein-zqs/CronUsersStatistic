<?php

/**
 * This repository takes care of all db accesses
 */
class CronUsersStatisticRepository {
    public function __construct(
        protected ilDBInterface $db
    ) {}

    /**
     * This function takes a CronUsersStatisticInfo object and creates a new db entry for it.
     */
    public function insert(CronUsersStatisticInfo $stats) : void {
        $this->db->insert("crn_usr_statistics", [
            "id" => ["integer", $stats->getId()],
            "stat_date" => ["date", $stats->getStatDate()],
            "user_count" => ["integer", $stats->getUserCount()],
            "created_at" => ["timestamp", $stats->getTimestamp()],
        ]);
    }

    /**
     * This function returns the overall usercount of the given day.
     */
    public function getUserCount(string $day) : int {
        $query = "
            SELECT COUNT(DISTINCT usr_id) AS user_count
            FROM usr_data
            WHERE DATE(last_login) = " . $this->db->quote($day, "date");
        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);
        return $row['user_count'];
    }

    /**
     * This function returns the whole statistic table for visualisation.
     */
    public function getStats() : array{
        $query = "SELECT * FROM crn_usr_statistics ORDER BY stat_date DESC";
        $res = $this->db->query($query);
        while ($row = $this->db->fetchAssoc($res)) {
            $stats[] = [
                'id'         => (int) $row['id'],
                'stat_date'  => (string) $row['stat_date'],
                'user_count' => (int) $row['user_count'],
                'created_at' => (string) $row['created_at'],
            ];
        }
        return $stats;
    } 

    /**
     * This function returns the next free ID for a new object.
     */
    public function getNextId() : int {
        return $this->db->nextId('crn_usr_statistics');
    }

}
