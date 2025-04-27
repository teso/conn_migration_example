<?php

declare(strict_types=1);

namespace Package\Database;

class DatabaseUseStatistics
{
    private static $instance = null;
    private int $selectFallbackUseCounter = 0;
    private int $selectLagProblemCounter = 0;
    private int $readEndpointCounter = 0;
    private int $writeEndpointCounter = 0;

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    public function incrementSelectFallbackUseCounter(): void
    {
        $this->selectFallbackUseCounter++;
    }

    public function incrementSelectLagProblemCounter(): void
    {
        $this->selectLagProblemCounter++;
    }

    public function incrementReadEndpointCounter(): void
    {
        $this->readEndpointCounter++;
    }

    public function incrementWriteEndpointCounter(): void
    {
        $this->writeEndpointCounter++;
    }

    public function getSelectFallbackUseCounter(): int
    {
        return $this->selectFallbackUseCounter;
    }

    public function getSelectLagProblemCounter(): int
    {
        return $this->selectLagProblemCounter;
    }

    public function getReadEndpointCounter(): int
    {
        return $this->readEndpointCounter;
    }

    public function getWriteEndpointCounter(): int
    {
        return $this->writeEndpointCounter;
    }

    public function reset(): void
    {
        $this->selectFallbackUseCounter = 0;
        $this->selectLagProblemCounter = 0;
        $this->readEndpointCounter = 0;
        $this->writeEndpointCounter = 0;
    }
}
