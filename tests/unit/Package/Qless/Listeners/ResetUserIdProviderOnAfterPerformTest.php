<?php

namespace unit\Package\Qless\Listeners;

use Codeception\Test\Unit;
use package\Qless\Listeners\ResetUserIdProviderOnAfterPerform;
use Package\User\Application\Providers\ServiceProvider;
use Package\User\Provider\UserIdProvider;
use Qless\Events\User\Job\AfterPerform;

class ResetUserIdProviderOnAfterPerformTest extends Unit
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
    }

    public function testHandler()
    {
        $eventMock = $this->createMock(AfterPerform::class);
        $listener = new ResetUserIdProviderOnAfterPerform();
        $idProvider = app(UserIdProvider::class);

        $this->assertSame(self::USER_ID, $idProvider->getUserId());

        $listener->handler($eventMock);

        $this->assertSame(0, $idProvider->getUserId());
    }
}
