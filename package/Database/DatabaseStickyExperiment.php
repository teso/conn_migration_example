<?php

declare(strict_types=1);

namespace Package\Database;

class DatabaseStickyExperiment
{
    private static $instance = null;
    private bool $isChecking = false;

    // It should be singleton to make protection
    // from infinite recursion works
    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function inExperiment(int $userId): bool
    {
        // There are a few database queries inside experiment() function.
        // To avoid infinite recursion we use flag $isChecking.
        // If ($isChecking === true) - it means we are inside experiment() function.
        // So, we exit before next experiment() function call
        if ($this->isChecking) {
            return false;
        }

        $this->isChecking = true;

        $result = // check experiment result by experiment name and user id

        $this->isChecking = false;

        return $result;
    }
}
