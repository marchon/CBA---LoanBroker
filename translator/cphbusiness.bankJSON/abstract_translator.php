<?php

require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

abstract class AbstractTranslator {

    protected $bank;
    protected $translator;

    protected $bank_params;
    protected $translator_params;

    public function __construct($translator_params, $bank_params){
        $this->translator_params = $translator_params;
        $this->bank_params = $bank_params;
    }

    public function __destruct(){
        $this->close();
    }

    public function run(){
        $this->initialize_translator();
        $this->initialize_bank();
        $this->subscribe();
        $this->close();
    }

    public function on_response($msg){
        $this->query_bank($msg);
    }

    abstract protected function translate_request($request);

    protected function subscribe(){
        $this->translator['channel']->basic_consume($this->translator_params['queue_name'], '', false,
            false, false, false, array($this, 'on_response'));

        while(count($this->translator['channel']->callbacks)) {
            $this->translator['channel']->wait();
        }
    }

    protected function query_bank($req_msg){
        $msg = new AMQPMessage(
            $this->translate_request($req_msg->body),
            array(
                'correlation_id' => $req_msg->get('correlation_id'),
                'reply_to' => $this->bank_params['reply_to']
            ));
        $this->bank['channel']->basic_publish($msg, $this->bank_params['exchange']);
    }

    protected function initialize_bank(){
        $connection = new AMQPStreamConnection($this->bank_params['host'], $this->bank_params['port'],
            $this->bank_params['username'], $this->bank_params['password']);
        $channel = $connection->channel();
        $channel->exchange_declare($this->bank_params['exchange'], 'fanout', false, false, false);
        $this->bank = array(
            'connection' => $connection,
            'channel' => $channel
        );
    }

    protected function initialize_translator(){
        $connection = new AMQPStreamConnection($this->translator_params['host'], $this->translator_params['port'],
            $this->translator_params['username'], $this->translator_params['password']);
        $channel = $connection->channel();
        $channel->exchange_declare('bank_translators_exchange', 'direct', false, false, false);

        list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
        $this->translator_params['queue_name'] = $queue_name;

        $channel->queue_bind($queue_name, 'bank_translators_exchange', $this->translator_params['routing_key']);

        echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

        $this->translator = array(
            'connection' => $connection,
            'channel' => $channel
        );
    }

    public function close(){
        $this->bank['channel']->close();
        $this->bank['connection']->close();
        $this->translator['channel']->close();
        $this->translator['connection']->close();
    }
}