<?php


use ILIAS\UI\Component\Table\DataRetrieval;
use ILIAS\UI\Component\Table\DataRowBuilder;
use ILIAS\Data\Range;
use ILIAS\Data\Order;

/**
 * This repository takes care of all db accesses
 */
class CronUsersStatisticRepository implements DataRetrieval{
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
     * Not used since switching to using the ILIAS data table.
     */
    // public function getStats() : array{
    //     $query = "SELECT * FROM crn_usr_statistics ORDER BY stat_date DESC";
    //     $res = $this->db->query($query);

    //     while ($row = $this->db->fetchAssoc($res)) {
    //         $stats[] = [
    //             'id'         => (int) $row['id'],
    //             'stat_date'  => (string) $row['stat_date'],
    //             'user_count' => (int) $row['user_count'],
    //             'created_at' => (string) $row['created_at'],
    //         ];
    //     }
    //     return $stats;
    // } 

    /**
     * This function returns the next free ID for a new object.
     */
    public function getNextId() : int {
        return $this->db->nextId('crn_usr_statistics');
    }

    /**
     * This function yields the data to be displayed from the CronUsersStatistic db. 
     * Needed to implement DataRetrieval.
     */
    public function getRows(
        DataRowBuilder $row_builder,
        array $visible_column_ids,
        Range $range,
        Order $order,
        ?array $filter_data,
        ?array $additional_parameters
    ): \Generator {
        $sql_order_part = $order->join('ORDER BY', fn(...$o) => implode(' ', $o));
        $sql_range_part = sprintf('LIMIT %2$s OFFSET %1$s', ...$range->unpack());
        
        $sql_where_part = $this->getFilterWherePart($filter_data);
        $query = "SELECT * FROM crn_usr_statistics " . $sql_where_part . " " . $sql_order_part . " " .  $sql_range_part;
        $res = $this->db->query($query);

        while ($row = $this->db->fetchAssoc($res)) {
            $row['stat_date'] = new DateTimeImmutable($row['stat_date']);
            $row['created_at'] = new DateTimeImmutable($row['created_at']);
            yield $row_builder->buildDataRow($row['id'], $row);
        }
    }

    /**
     * This function counts the rows to be displayed from the CronUsersStatistic db. 
     * Needed to implement DataRetrieval, mainly for the purpose of pagination-support.
     */
    public function getTotalRowCount(
        ?array $filter_data,
        ?array $additional_parameters
    ): ?int {
        $sql_where_part = $this->getFilterWherePart($filter_data);
        $query = "SELECT count(*) as row_cnt FROM crn_usr_statistics ". $sql_where_part;
        $res = $this->db->query($query);
        $row = $this->db->fetchAssoc($res);
        return $row['row_cnt'];
    }

    /**
     * This is a helper function for getRows/getTotalRowCount.
     * It creates the SQL statement part to constrain the statistic rows to an interval.
     */
    private function getFilterWherePart(array $filter_data) : string {
        list($start, $end) = $filter_data;
        $sql_where_part = "";
        if($start != null && $end != null){
            $sql_where_part = sprintf('WHERE stat_date >= "' . $start->format('Y-m-d') . '" AND stat_date <= "' . $end->format('Y-m-d') . '"');
        } elseif($start != null){
            $sql_where_part = sprintf('WHERE stat_date >= "' . $start->format('Y-m-d') . '"'); 
        } elseif($end != null){
            $sql_where_part = sprintf('WHERE stat_date <= "' . $end->format('Y-m-d') . '"');
        }
        return $sql_where_part;
    }
}
