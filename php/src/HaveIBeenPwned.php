<?php

namespace HaveIBeenPwned\PwnedPasswordsSpeedChallenge;

class HaveIBeenPwned
{
    public int $checked_passwords = 0;

    public float $transfer_time = 0.0;

    public int $requests_to_api = 0;

    public int $requests_to_origin = 0;

    public array $results = [];

    /**
     * Get average response time of the API
     * @return float|int
     */
    public function getAverageApiResponseTime(): float|int
    {
        return round(($this->transfer_time * 1000) / $this->requests_to_api, 3);
    }

    /**
     * Get average of checked passwords per second
     *
     * @return float
     */
    public function getCheckedPasswordsPerSecond(): float
    {
        return round($this->checked_passwords / $this->transfer_time, 2);
    }

    /**
     * Get total number of requests
     *
     * @return int
     */
    public function getTotalRequests(): int
    {
        return $this->requests_to_api + $this->requests_to_origin;
    }

    /**
     * Get transfer time in milliseconds
     *
     * @return float|int
     */
    public function getTransferTime(): float|int
    {
        return $this->transfer_time * 1000;
    }
}
