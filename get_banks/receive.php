<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
$channel = $connection->channel();

$channel->queue_declare('get_banks', false, false, false, false);

$callback = function($msg) {
    $request = json_decode($msg->body);
    $corr_id = $msg->get('correlation_id');

    // Get cURL resource
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "http://localhost:8000/banks/{$request->credit_score}",
    ));
    $resp = curl_exec($curl);
    curl_close($curl);

    $request->banks = json_decode($resp);

    var_dump($request);

    $msg = new AMQPMessage(json_encode($request), array('correlation_id' => $corr_id));
    forward_to_router($msg, 'bank_router');
};


$channel->basic_consume('get_banks', '', false, true, false, false, $callback);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

//Publish message to the bank_translators_exchange exchange
function forward_to_router($msg, $routing_key){
    $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
    $channel = $connection->channel();
    $channel->queue_declare($routing_key, false, false, false, false);
    $channel->basic_publish($msg, '', $routing_key);
    $channel->close();
    $connection->close();
}

?>