<?php

namespace TasmotaMqttClient;

use Bluerhinos\phpMQTT;
use Closure;

/**
 * @method array Status(?int $value = null, Closure $callback = null)
 * @method array Latitude(?string $value = null, Closure $callback = null)
 * @method array Longitude(?string $value = null, Closure $callback = null)
 * @method array Power(?int $value = null, Closure $callback = null)
 * @method array Color(?string $value = null, Closure $callback = null)
 * @method array Color2(?string $value = null, Closure $callback = null)
 * @method array CT(?int $value = null, Closure $callback = null)
 * @method array Dimmer(?int $value = null, Closure $callback = null)
 * @method array Fade(?bool $value = null, Closure $callback = null)
 * @method array Speed(?int $value = null, Closure $callback = null)
 * @method array Scheme(?int $value = null, Closure $callback = null)
 * @method array LedTable(?bool $value = null, Closure $callback = null)
 * @method array Wakeup(?int $value = null, Closure $callback = null)
 * @method array WakeupDuration(?int $value = null, Closure $callback = null)
 * @method array Upgrade(?int $value = null, Closure $callback = null)
 * @method array OtaUrl(?string $value = null, Closure $callback = null)
 */
class Request
{
    private const DEFAULT_TIMEOUT = 10;

    public function __construct(
        private phpMQTT $client,
        private Topic $topic,
        private int $timeout = self::DEFAULT_TIMEOUT,
    )
    {
    }

    /**
     * @param string[] $subscribeTopics
     */
    public function send(string $publishTopic, array $subscribeTopics, $payload = null, Closure $callback = null): array
    {
        if (is_bool($payload)) {
            $payload = (int) $payload;
        }

        $payload = (string) $payload;

        $responses = [];
        $topics = [];
        foreach ($subscribeTopics as $subscribeTopic) {
            $topics[$subscribeTopic] = [
                'qos' => 0,
                'function' => function (string $topic, $message) use (&$responses) {
                    $responses = array_merge($responses, $this->handleResponse($message));
                }
            ];
        }

        $this->client->subscribe($topics);

        $this->client->publish($publishTopic, $payload ?? '');

        $stopAt = microtime(true) + $this->timeout;
        while ($this->client->proc() && count($responses) !== count($subscribeTopics) && $stopAt > microtime(true));

        if (is_callable($callback)) {
            $callback($responses);
        } else {
            return $responses;
        }

        return [];
    }

    public function __call(string $topic, array $arguments = []): array
    {
        return $this->callMethod($topic, $arguments);
    }

    public function getClient(): phpMQTT
    {
        return $this->client;
    }

    public function getTopic(): Topic
    {
        return $this->topic;
    }

    protected function callMethod(string $topic, array $arguments = []): array
    {
        $subscribeTopic = [
            $this->topic->build('RESULT'),
        ];

        switch ($topic) {
            case 'Status':
                $payload = array_shift($arguments);
                array_unshift($arguments, $payload);

                $subscribeTopic = [
                    $this->topic->build('STATUS'),
                ];

                if ($payload === 0) {
                    for ($i = 1; $i < 12; ++$i) {
                        $subscribeTopic[] = $this->topic->build('STATUS' . $i);
                    }
                } else {
                    $subscribeTopic[] = $this->topic->build('STATUS' . $payload);
                }

                break;
        }

        return $this->send(
            $this->topic->build($topic),
            $subscribeTopic,
            array_shift($arguments),
            array_shift($arguments)
        );
    }

    /**
     * @throws UnknownCommandException
     */
    protected function handleResponse(string $response): array
    {
        $result = json_decode($response, true);
        if (!empty($result['Command']) && $result['Command'] === 'Unknown') {
            throw new UnknownCommandException('command is unknown');
        }

        return $result;
    }
}
