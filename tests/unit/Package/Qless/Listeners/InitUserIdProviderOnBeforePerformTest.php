<?php

namespace unit\Package\Qless\Listeners;

use Codeception\Test\Unit;
use package\Qless\Listeners\InitUserIdProviderOnBeforePerform;
use Package\User\Application\Providers\ServiceProvider;
use Package\User\Provider\UserIdProvider;
use Qless\Client;
use Qless\Events\User\Job\BeforePerform;
use Qless\Jobs\BaseJob;
use function Package\Utils\json_encode;

class InitUserIdProviderOnBeforePerformTest extends Unit
{
    private const USER_ID = 123;

    protected function _before()
    {
        parent::_before();

        (new ServiceProvider())->register(container());
    }

    protected function _after()
    {
        parent::_after();

        // Reset singleton state after tests
        app(UserIdProvider::class)->reset();
    }

    public function testHandler()
    {
        $clientMock = $this->createMock(Client::class);
        $job = new BaseJob($clientMock, [
            'jid' => 0,
            'klass' => '',
            'queue' => '',
            'worker' => '',
            'state' => '',
            'data' => json_encode(['__meta' => ['userId' => self::USER_ID]]),
        ]);
        $event = new BeforePerform(null, $job);
        $listener = new InitUserIdProviderOnBeforePerform();
        $idProvider = app(UserIdProvider::class);

        $this->assertSame(0, $idProvider->getUserId());

        $listener->handler($event);

        $this->assertSame(self::USER_ID, $idProvider->getUserId());
    }
}
