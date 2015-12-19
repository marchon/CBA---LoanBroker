<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Aggregator {

    private $task;

    public function __construct(){
        $this->task = new Task();
        $this->task->start();
    }

    public function subscribe(){
        $connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('aggregator', false, false, false, false);
        $channel->basic_consume('aggregator', '', false, true, false, false, array($this, 'on_response'));

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    public function on_response($msg){
        echo "Pushing to queue";
        $this->task->update_queue($msg);
    }
}

class Task extends Thread {

    private $queue;
    private $socket;
    const TIMEOUT = 3;
    //Flag used to pause queue check when its being updated
    private $pause;


    public function __construct(){
        $this->queue = array();
        $this->pause = false;
        //$this->socket = stream_socket_client('localhost:9999/echo');
    }

    public function update_queue($msg){
        echo "Updating queue \n";
        $this->pause = true;
        $response = json_decode($msg->body);

        if(!array_key_exists($msg->get('correlation_id'), $this->queue)){
            //Gets in here and correlation_id is a non-null value
            echo "Correlation does not exist" . $msg->get('correlation_id').  "\n";
            $this->queue[$msg->get('correlation_id')] = array();
            $this->queue[$msg->get('correlation_id')]['created_at'] = time();
            $this->queue[$msg->get('correlation_id')]['response'] = array();
        }

        //Null here
        var_dump($this->queue[$msg->get('correlation_id')]);

        //Response is a non-nul value
        $this->queue[$msg->get('correlation_id')]['response'][] = $response;
        $this->pause = false;

        //Always 0 here
        echo "Count: " . count($this->queue) . "\n";

        echo "Queue updated \n";
    }

    public function __destruct(){
        //fclose($this->socket);
    }

    public function run(){

        while(true){
            if($this->pause){continue;}

            //Always 0 here!
            //echo "Count: " . count($this->queue) . "\n";


            foreach($this->queue as $k => $v){
                echo $v['created_at'] . "\n";
                if(self::TIMEOUT + $v['created_at'] < time()){

                    $response = array();
                    $response['correlation_id'] = $k;
                    $response['response'] = $v['response'];

                    var_dump($response);

                    $response = json_encode($response);
                    //fwrite($this->sockett, $response);
                    //unset($this->queue[$k]);
                }
            }
        }
    }
}

$aggregator = new Aggregator();
$aggregator->subscribe();
?>

