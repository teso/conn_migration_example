<?php

namespace main_unit\Package\Database;

use Codeception\Test\Unit;
use Codeception\Util\ReflectionHelper;
use Illuminate\Contracts\Events\Dispatcher;
use Package\Database\Events\ReadEndpointUsed;
use Package\Database\Events\SelectFallbackUsed;
use Package\Database\Events\SelectLagProblemFound;
use Package\Database\Events\WriteEndpointUsed;
use Package\Database\MySqlConnection;
use RuntimeException;
use MainUnitTester;
use PDOStatement;
use PDO;

class MySqlConnectionTest extends Unit
{
    private const QUERY = 'SELECT :id;';
    private const BINDINGS = [':id' => 1];
    private const USE_READ_PDO = true;
    private const RESULT = ['result' => 1];
    private const ANOTHER_RESULT = ['result' => 2];
    private const NO_RESULT = [];
    private const MAIN_CONNECTION = 'main';
    private const DEFAULT_CONNECTION = 'default';

    protected MainUnitTester $tester;

    private PDOStatement $pdoStatementMock;
    private PDO $pdoMock;
    private MySqlConnection $connection;
    private Dispatcher $dispatcherMock;

    protected function _before()
    {
        parent::_before();

        $this->tester->mockTracerFunction();
    }

    protected function _after()
    {
        parent::_after();

        $this->tester->unmockTracerFunction();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdoStatementMock = $this->createMock(PDOStatement::class);

        $this->pdoMock = $this->createMock(PDO::class);
        $this->pdoMock
            ->method('prepare')
            ->willReturn($this->pdoStatementMock);

        $this->dispatcherMock = $this->createMock(Dispatcher::class);
        $this->connection = new MySqlConnection(
            fn () => $this->pdoMock,
            '',
            '',
            ['name' => self::MAIN_CONNECTION]
        );
        $this->connection->setEventDispatcher($this->dispatcherMock);
    }

    public function testSelectWhenNotMainConnection()
    {
        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        // Set up connection to "default"
        $connection = new MySqlConnection(
            fn () => $this->pdoMock,
            '',
            '',
            ['name' => self::DEFAULT_CONNECTION]
        );
        $connection->setEventDispatcher($this->dispatcherMock);
        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );
        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturn(self::RESULT);

        $this->assertSame(self::RESULT, $connection->select(
            self::QUERY,
            self::BINDINGS,
            self::USE_READ_PDO
        ));

        // No fallback because default logic used
        if ($hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should not be used');
        }

        // No event because no fallback
        if ($hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should not be used');
        }
    }

    public function testSelectWhenMainConnectionButReadPdoFlagIsFalse()
    {
        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );
        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturn(self::RESULT);

        $this->assertSame(self::RESULT, $this->connection->select(
            self::QUERY,
            self::BINDINGS,
            !self::USE_READ_PDO // Set read PDO flag to false
        ));

        // No fallback because default logic used
        if ($hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should not be used');
        }

        // No event because no fallback
        if ($hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should not be used');
        }
    }

    public function testSelectWhenMainConnectionAndEndpointsHaveSameResults()
    {
        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );

        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturn(self::RESULT); // Same result for read and write endpoints

        // Write endpoint result used
        $this->assertSame(self::RESULT, $this->connection->select(
            self::QUERY,
            self::BINDINGS,
            self::USE_READ_PDO
        ));

        if (!$hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should be used');
        }

        // No event because read and write endpoints return same result
        if ($hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should not be used');
        }
    }

    public function testSelectWhenMainConnectionAndEndpointsHaveDifferentNotEmptyResults()
    {
        $tryNumber = 1;

        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );

        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturnCallback(function () use (&$tryNumber) {
                if ($tryNumber === 1) {
                    $tryNumber++;

                    return self::RESULT; // Read endpoint used
                } else {
                    return self::ANOTHER_RESULT; // Write endpoint used
                }
            });

        // Write endpoint result used
        $this->assertSame(self::ANOTHER_RESULT, $this->connection->select(
            self::QUERY,
            self::BINDINGS,
            self::USE_READ_PDO
        ));

        if (!$hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should be used');
        }

        if (!$hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should be used');
        }
    }

    public function testSelectWhenMainConnectionAndReadEndpointHasNoResultButWriteEndpointHasResult()
    {
        $tryNumber = 1;

        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );

        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturnCallback(function () use (&$tryNumber) {
                if ($tryNumber === 1) {
                    $tryNumber++;

                    return self::NO_RESULT; // Read endpoint used
                } else {
                    return self::RESULT; // Write endpoint used
                }
            });

        // Write endpoint result used
        $this->assertSame(self::RESULT, $this->connection->select(
            self::QUERY,
            self::BINDINGS,
            self::USE_READ_PDO
        ));

        if (!$hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should be used');
        }

        if (!$hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should be used');
        }
    }

    public function testSelectWhenMainConnectionAndReadEndpointHasResultButWriteEndpointHasNoResult()
    {
        $tryNumber = 1;

        $hasSelectFallbackUsedEvent = false;
        $hasSelectLagProblemFoundEvent = false;

        $this->setUpDispatcherForSelect(
            $hasSelectFallbackUsedEvent,
            $hasSelectLagProblemFoundEvent
        );

        $this->pdoStatementMock
            ->method('fetchAll')
            ->willReturnCallback(function () use (&$tryNumber) {
                if ($tryNumber === 1) {
                    $tryNumber++;

                    return self::RESULT; // Read endpoint used
                } else {
                    return self::NO_RESULT; // Write endpoint used
                }
            });

        // Write endpoint result used
        $this->assertSame(self::NO_RESULT, $this->connection->select(
            self::QUERY,
            self::BINDINGS,
            self::USE_READ_PDO
        ));

        if (!$hasSelectFallbackUsedEvent) {
            throw new RuntimeException('SelectFallbackUsed event should be used');
        }

        if (!$hasSelectLagProblemFoundEvent) {
            throw new RuntimeException('SelectLagProblemFound event should be used');
        }
    }

    public function testGetReadPdoWhenNotMainConnection()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        // Set up connection to "default" with no read PDO set
        $connection = new MySqlConnection(
            fn () => null,
            '',
            '',
            ['name' => self::DEFAULT_CONNECTION]
        );
        $connection->setEventDispatcher($this->dispatcherMock);
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $connection->getReadPdo();

        // No event because default logic used
        if ($hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should not be used');
        }

        // No event because default logic used
        if ($hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should not be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndHasTransactionStarted()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        $this->connection->setReadPdo($this->createMock(PDO::class));
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $this->connection->beginTransaction();
        $this->connection->getReadPdo();

        if ($hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should not be used');
        }

        if (!$hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndHasModifiedRowButNoStickyFlag()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        $this->connection->setReadPdo($this->createMock(PDO::class));
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $this->connection->recordsHaveBeenModified(); // Set modified rows flag
        $this->connection->getReadPdo();

        if (!$hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should be used');
        }

        if ($hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should not be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndHasModifiedRowAndStickyFlagTrue()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        // Set up connection as "main" with sticky flag true
        $connection = new MySqlConnection(
            fn () => null,
            '',
            '',
            ['name' => self::MAIN_CONNECTION, 'sticky' => true]
        );
        $connection->setReadPdo($this->createMock(PDO::class));
        $connection->setEventDispatcher($this->dispatcherMock);
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $connection->recordsHaveBeenModified(); // Set modified rows flag
        $connection->getReadPdo();

        if ($hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should not be used');
        }

        if (!$hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndReadPdoAsCallable()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        $this->connection->setReadPdo(fn () => $this->createMock(PDO::class)); // Set as callable
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $this->connection->getReadPdo();

        if (!$hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should be used');
        }

        if ($hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should not be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndReadPdoAsObject()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        $this->connection->setReadPdo($this->createMock(PDO::class)); // Set as object
        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $this->connection->getReadPdo();

        if (!$hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should be used');
        }

        if ($hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should not be used');
        }
    }

    public function testGetReadPdoWhenMainConnectionAndReadPdoNotSet()
    {
        $hasReadEndpointUsedEvent = false;
        $hasWriteEndpointUsedEvent = false;

        $this->setUpDispatcherForGetReadPdo(
            $hasReadEndpointUsedEvent,
            $hasWriteEndpointUsedEvent
        );

        $this->connection->getReadPdo();

        if ($hasReadEndpointUsedEvent) {
            throw new RuntimeException('ReadEndpointUsed event should not be used');
        }

        if (!$hasWriteEndpointUsedEvent) {
            throw new RuntimeException('WriteEndpointUsed event should be used');
        }
    }

    public function testForgetRecordModificationState()
    {
        $this->connection->recordsHaveBeenModified();

        $this->assertTrue(ReflectionHelper::readPrivateProperty($this->connection, 'recordsModified'));

        $this->connection->forgetRecordModificationState();

        $this->assertFalse(ReflectionHelper::readPrivateProperty($this->connection, 'recordsModified'));
    }

    private function setUpDispatcherForSelect(
        &$hasSelectFallbackUsedEvent,
        &$hasSelectLagProblemFoundEvent
    ): void {
        $this->dispatcherMock
            ->method('dispatch')
            ->with($this->callback(function ($event) use (
                &$hasSelectFallbackUsedEvent,
                &$hasSelectLagProblemFoundEvent
            ) {
                if ($event instanceof SelectFallbackUsed) {
                    $hasSelectFallbackUsedEvent = true;
                }

                if ($event instanceof SelectLagProblemFound) {
                    $hasSelectLagProblemFoundEvent = true;
                }

                return true;
            }));
    }

    private function setUpDispatcherForGetReadPdo(
        &$hasReadEndpointUsedEvent,
        &$hasWriteEndpointUsedEvent
    ): void {
        $this->dispatcherMock
            ->method('dispatch')
            ->with($this->callback(function ($event) use (
                &$hasReadEndpointUsedEvent,
                &$hasWriteEndpointUsedEvent
            ) {
                if ($event instanceof ReadEndpointUsed) {
                    $hasReadEndpointUsedEvent = true;
                }

                if ($event instanceof WriteEndpointUsed) {
                    $hasWriteEndpointUsedEvent = true;
                }

                return true;
            }));
    }
}
