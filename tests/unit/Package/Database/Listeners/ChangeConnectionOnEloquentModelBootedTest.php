<?php

namespace unit\Package\Database\Listeners;

use Codeception\Test\Unit;
use Codeception\Util\Fixtures;
use Codeception\Util\ReflectionHelper;
use Illuminate\Database\Eloquent\Model;
use Package\Database\Listeners\ChangeConnectionOnEloquentModelBooted;
use Package\Experiment\Models\ExperimentRunResult;
use Package\User\Application\Providers\ServiceProvider;
use Package\User\Provider\UserIdProvider;
use stdClass;

class ChangeConnectionOnEloquentModelBootedTest extends Unit
{
    public const DEFAULT_CONNECTION = 'default';
    public const MAIN_CONNECTION = 'main';
    public const RANDOM_CONNECTION = 'random';

    protected function _before()
    {
        parent::_before();

        $_SESSION['login_user'] = 123;

        (new ServiceProvider())->register(container());
    }

    protected function _after()
    {
        parent::_after();

        unset($_SESSION['login_user']);

        app(UserIdProvider::class)->reset();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Fixtures::add('function_experiment', fn () => null);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Fixtures::cleanup('function_experiment');
    }

    public function testHandleWhenNoModelPassed()
    {
        $listener = new ChangeConnectionOnEloquentModelBooted();

        $listener->handle('', []); // No errors, quick exit
    }

    public function testHandleWhenWrongModelInstancePassed()
    {
        $listener = new ChangeConnectionOnEloquentModelBooted();

        $listener->handle('', [new stdClass()]); // No errors, quick exit
    }

    public function testHandleWhenNoConnectionSet()
    {
        $listener = new ChangeConnectionOnEloquentModelBooted();
        $model = new class extends Model {};

        $listener->handle('', [$model]); // Check connection name, quick exit

        $this->assertNull(ReflectionHelper::readPrivateProperty($model, 'connection'));
    }

    public function testHandleWhenConnectionSetToNotDefault()
    {
        $listener = new ChangeConnectionOnEloquentModelBooted();
        $model = new class extends Model {
            protected $connection = ChangeConnectionOnEloquentModelBootedTest::RANDOM_CONNECTION;
        };

        $listener->handle('', [$model]); // Check connection name, quick exit

        $this->assertSame(
            self::RANDOM_CONNECTION, // Connection was not changed
            ReflectionHelper::readPrivateProperty($model, 'connection')
        );
    }

    public function testHandleWhenConnectionSetToDefaultButNotInExperiment()
    {
        $listener = new ChangeConnectionOnEloquentModelBooted();
        $model = new class extends Model {
            protected $connection = ChangeConnectionOnEloquentModelBootedTest::DEFAULT_CONNECTION;
        };

        $listener->handle('', [$model]);

        $this->assertSame(
            self::DEFAULT_CONNECTION, // Connection was not changed
            ReflectionHelper::readPrivateProperty($model, 'connection')
        );
    }

    public function testHandleWhenConnectionSetToDefaultAndInExperiment()
    {
        Fixtures::add('function_experiment', fn () => new ExperimentRunResult('', 'experiment', 0));

        $listener = new ChangeConnectionOnEloquentModelBooted();
        $model = new class extends Model {
            protected $connection = ChangeConnectionOnEloquentModelBootedTest::DEFAULT_CONNECTION;
        };

        $listener->handle('', [$model]);

        $this->assertSame(
            self::MAIN_CONNECTION, // Connection was changed
            ReflectionHelper::readPrivateProperty($model, 'connection')
        );
    }
}
