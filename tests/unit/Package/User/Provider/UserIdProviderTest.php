<?php

namespace unit\Package\User\Provider;

use Codeception\Test\Unit;
use Package\User\Provider\UserIdProvider;
use Qless\Jobs\JobData;
use ApiApp;

class UserIdProviderTest extends Unit
{
    private const DEFAULT_VALUE = 0;
    private const CUSTOM_VALUE = 10;
    private const ANOTHER_CUSTOM_VALUE = 20;
    private const DEFAULT_API_APPLICATION_USER_ID = '';

    public function testConstructWhenNoValuePassed()
    {
        $instance = new UserIdProvider();

        $this->assertSame(self::DEFAULT_VALUE, $instance->getUserId());
    }

    public function testConstructWhenValuePassed()
    {
        $instance = new UserIdProvider(self::CUSTOM_VALUE);

        $this->assertSame(self::CUSTOM_VALUE, $instance->getUserId());
    }

    public function testApplyToJobPayloadWhenNoValueExists()
    {
        $instance = new UserIdProvider(self::CUSTOM_VALUE);
        $payload = new JobData();

        $instance->applyToJobPayload($payload);

        $this->assertSame(self::CUSTOM_VALUE, $payload['__meta']['userId']);
    }

    public function testApplyToJobPayloadWhenValueExists()
    {
        $instance = new UserIdProvider(self::CUSTOM_VALUE);
        $payload = new JobData([
            '__meta' => [
                'userId' => self::ANOTHER_CUSTOM_VALUE,
            ]
        ]);

        $instance->applyToJobPayload($payload);

        $this->assertSame(self::ANOTHER_CUSTOM_VALUE, $payload['__meta']['userId']);
    }

    public function testInitFromJobPayloadWhenValueExists()
    {
        $instance = new UserIdProvider();
        $payload = new JobData([
            '__meta' => [
                'userId' => self::CUSTOM_VALUE,
            ]
        ]);

        $instance->initFromJobPayload($payload);

        $this->assertSame(self::CUSTOM_VALUE, $instance->getUserId());
    }


    public function testInitFromJobPayloadWhenNoValueExists()
    {
        $instance = new UserIdProvider();
        $payload = new JobData();

        $instance->initFromJobPayload($payload);

        $this->assertSame(self::DEFAULT_VALUE, $instance->getUserId());
    }

    public function testInitFromApiApplicationWhenValueExists()
    {
        $instance = new UserIdProvider();
        $apiMock = $this->createMock(ApiApp::class);
        $apiMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(self::CUSTOM_VALUE);

        $instance->initFromApiApplication($apiMock);

        $this->assertSame(self::CUSTOM_VALUE, $instance->getUserId());
    }

    public function testInitFromApiApplicationWhenNoValueExists()
    {
        $instance = new UserIdProvider();
        $apiMock = $this->createMock(ApiApp::class);
        $apiMock
            ->expects($this->once())
            ->method('getUserId')
            ->willReturn(self::DEFAULT_API_APPLICATION_USER_ID);

        $instance->initFromApiApplication($apiMock);

        $this->assertSame(self::DEFAULT_VALUE, $instance->getUserId());
    }

    public function testReset()
    {
        $instance = new UserIdProvider(self::CUSTOM_VALUE);

        $this->assertSame(self::CUSTOM_VALUE, $instance->getUserId());

        $instance->reset();

        $this->assertSame(self::DEFAULT_VALUE, $instance->getUserId());
    }
}
