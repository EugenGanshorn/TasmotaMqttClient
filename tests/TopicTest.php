<?php

use TasmotaMqttClient\Topic;

class TopicTest extends PHPUnit\Framework\TestCase
{
    public function testTopicContainsTopic(): void
    {
        $sut = new Topic();
        $sut->setTopic('foobar');

        $this->assertSame('cmnd/foobar/#', $sut->build());
    }

    public function testUrlContainsCommand(): void
    {
        $sut = new Topic();
        $sut->setTopic('foobar');

        $this->assertSame('cmnd/foobar/command', $sut->build('command'));
    }

    public function testUrlContainsPrefix(): void
    {
        $sut = new Topic();
        $sut->setTopic('foobar');

        $this->assertSame('stat/foobar/RESULT', $sut->build('RESULT'));
    }
}
