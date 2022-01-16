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
    private bool $messageReceived = false;

    /**
     * @throws UnknownCommandException
     */
    public function Status(?int $value = null, Closure $callback = null): array
    {
        if ($value !== null) {
            return $this->callMethod('Status', [$value, $callback]);
        }

        $status = [];
        $status = array_merge($status, $this->callMethod('Status'));
        for ($i = 1; $i < 12; ++$i) {
            if ($i === 9) {
                continue;
            }

            $status = array_merge($status, $this->callMethod('Status', [$i]));
        }

        if ($callback) {
            return $callback($status);
        }

        return $status;
    }

    /**
     * @throws UnknownCommandException
     */
    public function send(string $publishTopic, string $subscribeTopic, $payload = null, Closure $callback = null): array
    {
        if (is_bool($payload)) {
            $payload = (int) $payload;
        }

        $payload = (string) $payload;

        $this->client->publish($publishTopic, $payload ?? '');
        $this->messageReceived = false;
        if ($callback === null) {
            $response = $this->client->subscribeAndWaitForMessage($subscribeTopic, 0);

            return $this->handleResponse($response);
        } else {
            $this->messageReceived = false;

            $topics = [];
            $topics[$subscribeTopic]  = [
                'qos' => 0,
                'function' => function (string $topic, $response) use ($callback) {
                    $result = $this->handleResponse($response);
                    $callback($result);
                    $this->messageReceived = true;
                }
            ];

            $this->client->subscribe($topics);

            $i = 0;
            while (!$this->messageReceived && $this->client->proc()) {
                if (++$i % 100 === 0) {
                    $this->client->ping();
                }
            }

            return [];
        }
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
