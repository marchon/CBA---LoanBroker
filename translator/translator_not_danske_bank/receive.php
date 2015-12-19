<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('bank_translators_exchange', 'direct', false, false, false);

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

$channel->queue_bind($queue_name, 'bank_translators_exchange', '11111111');

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function($msg) {
    $request = json_decode($msg->body);
    var_dump($request);
    $resp = request_interest_rate($request);
    forward_interest_rate($resp);
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while(count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();

//Publish message to the bank_translators_exchange exchange
function request_interest_rate($request){

    $req = new stdClass();
    $req->ssn = $request->ssn;
    $req->creditScore = $request->credit_score;
    $req->loanAmount = $request->amount;
    $req->loadDuration = $request->duration;
    $req = json_encode($req);

    // Get cURL resource
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => "localhost:3000/createLoanRequest",
        CURLOPT_POSTFIELDS => $req,
        CURLOPT_HTTPHEADER =>  array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($req)))
    );
    $resp = curl_exec($curl);
    curl_close($curl);
    return $resp;
}

function forward_to_normalizer($response){
    $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
    $channel = $connection->channel();
    $channel->queue_declare("bank_normalizer", false, false, true, false);
    $msg = new AMQPMessage($response);
    $channel->basic_publish($msg, '', 'bank_normalizer');
    $channel->close();
    $connection->close();

}

?>