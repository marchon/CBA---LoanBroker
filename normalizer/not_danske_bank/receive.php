<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//$connection = new AMQPStreamConnection('datdb.cphbusiness.dk', '5672', 'student', 'cph');
//echo "Is connected: " . $connection->isConnected();

/*
$banks = array(
    '11111111' => array(
        'host' => 'localhost',
        'port' => '5672',
        'username' => 'test',
        'password' => 'test',
        'queue_name' => 'vongrad_not_danske'
    ),
    '22222222' => array(
        'host' => 'localhost',
        'port' => '5672',
        'username' => 'test',
        'password' => 'test',
        'queue_name' => 'vongrad_not_nordea'
    ),

    '33333333' => array(
        'host' => 'datdb.cphbusiness.dk',
        'port' => '5672',
        'username' => 'student',
        'password' => 'cph',
        'queue_name' => 'vongrad_cphbusiness_json_1'
    ),
    '44444444' => array(
        'host' => 'datdb.cphbusiness.dk',
        'port' => '5672',
        'username' => 'student',
        'password' => 'cph',
        'queue_name' => 'vongrad_cphbusiness_xml'
    )
);
 */

/*
 * NORMALIZED RESPONSE
$response = {
    'interestRate' : 111,
    'ssn' : 11111111
}
*/

class Normalizer {

    private $running;
    private $connection;
    private $channel;
    private $bank_config;

    public function __construct($bank_config){
        $this->connection = new AMQPStreamConnection($bank_config['host'], $bank_config['port'], $bank_config['username'], $bank_config['password']);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($bank_config['queue_name'], false, false, false, false);
        $this->bank_config = $bank_config;
    }

    public function on_response($msg){
        $correlation_id = $msg->get('correlation_id');
        $response = json_decode($msg->body);
        $response->bankName = 'NotNordea';
        var_dump($response);
        $this->forward_to_aggregator($response, $correlation_id);
    }

    public function __destruct(){
        $this->channel->close();
        $this->connection->close();
    }

    public function run(){
        $this->channel->basic_consume($this->bank_config['queue_name'], '', false, true, false, false, array($this, 'on_response'));
        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $this->running = true;
        while(count($this->channel->callbacks) && $this->running) {
            $this->channel->wait();
        }
    }

    public function stop(){
        $this->running = false;
    }

    function forward_to_aggregator($response, $correlation_id){
        $connection = new AMQPStreamConnection('localhost', 5672, 'test', 'test');
        $channel = $connection->channel();
        $channel->queue_declare('aggregator', false, false, false, false);
        $msg = new AMQPMessage(json_encode($response), array('correlation_id' => $correlation_id));
        $channel->basic_publish($msg, '', 'aggregator');
        $channel->close();
        $connection->close();
    }
}

$bank_config = array(
    'host' => 'localhost',
    'port' => '5672',
    'username' => 'test',
    'password' => 'test',
    'queue_name' => 'vongrad_not_danske_bank'
);

$normalizer = new Normalizer($bank_config);
$normalizer->run();

?>