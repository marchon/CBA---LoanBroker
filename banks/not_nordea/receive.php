<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Bank{

    private $connection;
    private $channel;

    public function __destruct(){
        $this->channel->close();
        $this->connection->close();
    }

    public function listen(){
        $this->connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare('bank_not_nordea', 'fanout', false, false, false);

        list($queue_name, ,) = $this->channel->queue_declare('', false, false, true, false);
        $this->channel->queue_bind($queue_name, 'bank_not_nordea');

        echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";
        $this->channel->basic_consume($queue_name, '', false, true, false, false, array($this, 'on_receive'));

        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }

    public function on_receive($req){
        var_dump($req->body);

        $ssn = intval($req->body);
        $resp = $this->calculateInterestRate($ssn);

        $msg = new AMQPMessage(
            (string) $resp,
            array('correlation_id' => $req->get('correlation_id'))
        );

        $this->channel->queue_declare($req->get('reply_to'), false, false, false, false);
        $this->channel->basic_publish($msg, '', $req->get('reply_to'));
    }

    private function calculateInterestRate($ssn) {
        $interest_rate = $this->random_float(1, 5);
        $response = array();
        $response['ssn'] = $ssn;
        $response['interestRate'] = $interest_rate;
        return json_encode($response);
    }

    private function random_float($min,$max) {
        return ($min + lcg_value() * (abs($max - $min)));
    }
}

$bank = new Bank();
$bank->listen();

?>
