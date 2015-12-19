<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('abstract_translator.php');

class Translator extends AbstractTranslator {

    protected function translate_request($request){
        $request = json_decode($request, true);
        var_dump($request);
        $formatted_request = array();
        $formatted_request['ssn'] = implode('', explode('-', $request['ssn']));
        $formatted_request['creditScore'] = $request['credit_score'];
        $formatted_request['loanAmount'] = $request['amount'];
            $formatted_request['loanDuration'] = $request['duration'];
        var_dump($formatted_request);
        return json_encode($formatted_request);
    }
}

$translator_params = array(
    'host' => 'localhost',
    'port' => '5672',
    'username' => 'test',
    'password' => 'test',
    'routing_key' => '22222222',
);

$bank_params = array(
    'host' => 'localhost',
    'port' => '5672',
    'username' => 'test',
    'password' => 'test',
    'exchange' => 'bank_not_nordea',
    'reply_to' => 'vongrad_not_nordea'
);

$translator = new Translator($translator_params, $bank_params);
$translator->run();
