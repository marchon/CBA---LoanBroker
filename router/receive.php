<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

//Bank Router queue
$channel->queue_declare('bank_router', false, false, false, false);

$callback = function($msg) {
    $request = json_decode($msg->body);
    $corr_id = $msg->get('correlation_id');

    var_dump($request);

    $request_clean = clone $request;
    unset($request_clean->banks);

    foreach($request->banks as $bank){
        echo "Publishing to {$bank->cvr} \n";
        $msg = new AMQPMessage(json_encode($request_clean), array('correlation_id' => $corr_id));
        forward_to_translator($msg, $bank->cvr);
    }
};


//Subscribe to get messages from default rabbitmq exchange
$channel->basic_consume('bank_router', '', false, true, false, false, $callback);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

//Publish message to the bank_translators_exchange exchange
function forward_to_translator($msg, $routing_key){
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $channel->exchange_declare('bank_translators_exchange', 'direct', false, false, false);
    $channel->basic_publish($msg, 'bank_translators_exchange', $routing_key);
    $channel->close();
    $connection->close();
}

?>