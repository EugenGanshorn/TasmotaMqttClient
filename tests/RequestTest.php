<?php

use Bluerhinos\phpMQTT;
use TasmotaHttpClient\Request;
use TasmotaHttpClient\UnknownCommandException;

class RequestTest extends PHPUnit\Framework\TestCase
{
    public function testClientMethodGetWasCalled(): void
    {
        $client = $this->createMock(phpMQTT::class);
        $client
            ->expects($this->once())
            ->method('subscribeAndWaitForMessage')
            ->with(
                $this->equalTo('RESULT'),
                $this->equalTo(0)
            )
            ->willReturn('{}')
        ;

        $sut = new Request();
        $sut->setClient($client);

        $result = $sut->send('foobar', 'RESULT');

        $this->assertSame([], $result);
    }

    public function testJsonDecodeWorks(): void
    {
        $client = $this->createMock(phpMQTT::class);
        $client
            ->expects($this->once())
            ->method('subscribeAndWaitForMessage')
            ->with(
                $this->equalTo('RESULT'),
                $this->equalTo(0)
            )
            ->willReturn('{"key": "value"}')
        ;

        $sut = new Request();
        $sut->setClient($client);

        $result = $sut->send('foobar', 'RESULT');

        $this->assertSame(['key' => 'value'], $result);
    }

    public function testJsonDecodeThrowAnExceptionIfJsonIsBroken(): void
    {
        $client = $this->createMock(phpMQTT::class);
        $client
            ->expects($this->once())
            ->method('subscribeAndWaitForMessage')
            ->with(
                $this->equalTo('RESULT'),
                $this->equalTo(0)
            )
            ->willReturn('{"key: "value"}')
        ;

        $sut = new Request();
        $sut->setClient($client);

        $this->expectException(TypeError::class);
        $sut->send('foobar', 'RESULT');
    }

    public function testUnknownCommandExceptionIsThrown(): void
    {
        $client = $this->createMock(phpMQTT::class);
        $client
            ->expects($this->once())
            ->method('subscribeAndWaitForMessage')
            ->with(
                $this->equalTo('RESULT'),
                $this->equalTo(0)
            )
            ->willReturn('{"Command": "Unknown"}')
        ;

        $sut = new Request();
        $sut->setClient($client);

        $this->expectException(UnknownCommandException::class);
        $sut->send('foobar', 'RESULT');
    }
}
