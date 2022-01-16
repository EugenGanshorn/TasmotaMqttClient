<?php

use Bluerhinos\phpMQTT;
use TasmotaHttpClient\Request;
use TasmotaHttpClient\Topic;

require_once 'vendor/autoload.php';

$topic = new Topic();
$topic->setTopic('sonoff_foobar');

$client = new phpMQTT('mqtt.local', 1883, 'TasmotaMqttClient');
$client->connect_auto(true, null, 'user', 'pass');

$request = new Request();
$request->setClient($client);
$request->setTopic($topic);

/** @noinspection ForgottenDebugOutputInspection */
var_dump($request->Latitude());

/** @noinspection ForgottenDebugOutputInspection */
var_dump($request->Longitude());