<?php

namespace Package\Database;

use Closure;
use Illuminate\Database\MySqlConnection as BaseConnection;
use Package\Database\Events\ReadEndpointUsed;
use Package\Database\Events\SelectFallbackUsed;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Events\WriteEndpointUsed;

class MySqlConnection extends BaseConnection
{
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        // Default logic when write endpoint or another connection used
        if (!$useReadPdo || $this->getName() !== TEloquentDB::CONNECTION_MAIN) {
            return parent::select($query, $bindings, $useReadPdo);
        }

        // Fallback logic when read endpoint and "main" connection used
        $readResult = parent::select($query, $bindings, false);
        $writeResult = parent::select($query, $bindings, false);

        $this->event(new SelectFallbackUsed($this));

        // Replication lag problem found
        if ($readResult !== $writeResult) {
            if ($readResult && $writeResult) {
                $type = 'update';
            } elseif ($readResult) {
                $type = 'delete';
            } elseif ($writeResult) {
                $type = 'insert';
            } else {
                $type = 'unknown';
            }

            $this->event(new SelectLagProblemFound(
                $this,
                [
                    'query' => $query,
                    'bindings' => $bindings,
                    'type' => $type,
                    'readResult' => $readResult,
                    'writeResult' => $writeResult,
                ]
            ));
        }

        return $writeResult;
    }

    public function getReadPdo()
    {
        // Default logic for other connections
        if ($this->getName() !== TEloquentDB::CONNECTION_MAIN) {
            return parent::getReadPdo();
        }

        // Add events for "main" connection
        if ($this->transactions > 0) {
            $this->event(new WriteEndpointUsed($this));

            return $this->getPdo();
        }

        if ($this->recordsModified && $this->getConfig('sticky')) {
            $this->event(new WriteEndpointUsed($this));

            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            $this->event(new ReadEndpointUsed($this));

            return $this->readPdo = call_user_func($this->readPdo);
        }

        if ($this->readPdo) {
            $this->event(new ReadEndpointUsed($this));
        } else {
            $this->event(new WriteEndpointUsed($this));
        }

        return $this->readPdo ?: $this->getPdo();
    }

    public function recordsHaveBeenModified($value = true): void
    {
        if (!$this->recordsModified) {
            $this->recordsModified = $value;
        }
    }

    public function forgetRecordModificationState(): void
    {
        if ($this->recordsModified) {
            $this->recordsModified = false;
        }
    }
}
