<?php

namespace TasmotaMqttClient;

use Bluerhinos\phpMQTT;
use Closure;

/**
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
    private phpMQTT $client;
    private Topic $topic;

    /**
     * @throws UnknownCommandException
     */
    public function Status(?int $value = null, Closure $callback = null): array
    {
        if ($value !== null && $value !== 0) {
            return $this->callMethod('Status', [$value, $callback]);
        }

        $status = [];
        $status = array_merge($status, $this->callMethod('Status'));
        for ($i = 1; $i < 12; ++$i) {
            $status = array_merge($status, $this->callMethod('Status', [$i]));
        }

        if ($callback) {
            $callback($status);
            return [];
        }

        return $status;
    }

    public function send(string $publishTopic, string $subscribeTopic, $payload = null, Closure $callback = null): array
    {
        if (is_bool($payload)) {
            $payload = (int) $payload;
        }

        $payload = (string) $payload;

        $this->client->publish($publishTopic, $payload ?? '');

        $response = null;
        $topics = [];
        $topics[$subscribeTopic]  = [
            'qos' => 0,
            'function' => function (string $topic, $message) use ($callback, &$response) {
                $response = $this->handleResponse($message);

                if (is_callable($callback)) {
                    $callback($response);
                }
            }
        ];

        $this->client->subscribe($topics);

        $stopAt = microtime(true) + 5;
        while ($this->client->proc() && $response === null && $stopAt > microtime(true));

        if ($callback === null && $response !== null) {
            return $response;
        }

        return [];
    }

    /**
     * @throws UnknownCommandException
     */
    public function __call(string $topic, array $arguments = []): array
    {
        return $this->callMethod($topic, $arguments);
    }

    public function getClient(): phpMQTT
    {
        return $this->client;
    }

    /**
     * @required
     */
    public function setClient(phpMQTT $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getTopic(): Topic
    {
        return $this->topic;
    }

    /**
     * @required
     */
    public function setTopic(Topic $topic): self
    {
        $this->topic = $topic;
        return $this;
    }

    /**
     * @throws UnknownCommandException
     */
    protected function callMethod(string $topic, array $arguments = []): array
    {
        $subscribeTopic = 'RESULT';
        switch ($topic) {
            case 'Status':
                $payload = array_shift($arguments);
                $subscribeTopic = 'STATUS' . $payload;
                array_unshift($arguments, $payload);
                break;
        }

        return $this->send(
            $this->topic->build($topic),
            $this->topic->build($subscribeTopic),
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
