<?php
namespace App\DTO;

class LeaderboardEntry
{
    public string $clubName;
    public int $points;

    public function __construct(string $clubName, int $points)
    {
        $this->clubName = $clubName;
        $this->points = $points;
    }
}