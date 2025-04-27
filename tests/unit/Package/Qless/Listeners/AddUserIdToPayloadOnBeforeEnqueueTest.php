<?php

namespace unit\Package\Qless\Listeners;

use Codeception\Test\Unit;
use Package\Qless\Listeners\AddUserIdToPayloadOnBeforeEnqueue;
use Package\User\Application\Providers\ServiceProvider;
use Package\User\Provider\UserIdProvider;
use Qless\Events\User\Queue\BeforeEnqueue;
use Qless\Jobs\JobData;

class AddUserIdToPayloadOnBeforeEnqueueTest extends Unit
{
    private const USER_ID = 123;

    protected function _before()
    {
        parent::_before();

        $_SESSION['login_user'] = self::USER_ID;

        (new ServiceProvider())->register(container());
    }

    protected function _after()
    {
        parent::_after();

        unset($_SESSION['login_user']);

        // Reset singleton state after tests
        app(UserIdProvider::class)->reset();
    }

    public function testHandler()
    {
        $payload = new JobData();
        $event = new BeforeEnqueue(null, 0, $payload, '');
        $listener = new AddUserIdToPayloadOnBeforeEnqueue();

        $this->assertTrue(empty($payload['__meta']['userId']));

        $listener->handler($event);

        $this->assertSame(self::USER_ID, $payload['__meta']['userId']);
    }
}
