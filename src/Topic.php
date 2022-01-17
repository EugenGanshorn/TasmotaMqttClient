<?php

namespace TasmotaMqttClient;

class Topic
{
    public function __construct(protected string $topic)
    {
    }

    public function build(?string $command = null): string
    {
        $prefix = match (substr($command, 0, 6)) {
            'RESULT', 'STATUS' => 'stat',
            default => 'cmnd',
        };

        return sprintf('%s/%s/%s', $prefix, $this->topic, $command ?? '#');
    }

    public function getTopic(): string
    {
        return $this->topic;
    }
}
