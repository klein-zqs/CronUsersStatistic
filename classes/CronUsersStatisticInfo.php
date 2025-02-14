<?php

class CronUsersStatisticInfo {
    public function __construct(
        private int $id, 
        private string $stat_date, 
        private int $user_count, 
        private string $timestamp
    ) {}

    public function getId() : int {
        return $this->id;
    }

    // public function withId(int $id) : self {
    //     $clone = clone $this;
    //     $clone->id = $id;
    //     return $clone;
    // } 

    public function getStatDate() : string {
        return $this->stat_date;
    }

    public function getUserCount() : int {
        return $this->user_count;
    }


    public function getTimestamp() : string {
        return $this->timestamp;
    }  
}