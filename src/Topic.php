<?php

namespace TasmotaHttpClient;

class Topic
{
    protected string $topic;

    public function build(?string $command = null): string
    {
        $prefix = 'cmnd';
        switch ($command) {
            case 'RESULT':
            case 'STATUS':
                $prefix = 'stat';
                break;

        }

        return sprintf('%s/%s/%s', $prefix, $this->topic, $command ?? '#');
    }

    public function getTopic(): string
    {
        return $this->topic;
    }

    public function setTopic(string $topic): Topic
    {
        $this->topic = $topic;
        return $this;
    }
}
