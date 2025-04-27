<?php

declare(strict_types=1);

namespace Package\Database\Events;

use Illuminate\Database\Connection;
use Illuminate\Database\Events\ConnectionEvent;

class SelectLagProblemFound extends ConnectionEvent
{
    private array $data;

    public function __construct(Connection $connection, array $data)
    {
        parent::__construct($connection);

        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
