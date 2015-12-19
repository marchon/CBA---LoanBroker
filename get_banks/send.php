<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();


$channel->queue_declare('get_banks', false, false, false, false);

$msg = array();
$msg['ssn'] = '921003-3141';
$msg['score'] = '600';

$msg = json_encode($msg);

$msg = new AMQPMessage($msg);
$channel->basic_publish($msg, '', 'get_banks');

echo " [x] Sent 'Hello World!'\n";

$channel->close();
$connection->close();

?>