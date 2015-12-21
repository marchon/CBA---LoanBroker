<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('abstract_translator.php');

class Translator extends AbstractTranslator {

    protected function translate_request($request){
        //Request is all right as it is
        $request = json_decode($request, true);
        return $request;
    }

    protected function initialize_bank(){
        //Nothing needed here, its asimple curl request}
    }

    protected function query_bank($req_msg){
        //Just to maintain the same control flow
        $request = $this->translate_request($req_msg->body);
        var_dump($request);

        // Get cURL resource
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_HTTPHEADER => array('reply_to' => $this->bank_params['reply_to']),
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_URL => $this->bank_params['host']
        ));

        curl_exec($curl);
        curl_close($curl);
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
    'host' => 'http://localhost/createLoanRequest',
    'reply_to' => 'vongrad_not_danske_bank'
);

$translator = new Translator($translator_params, $bank_params);
$translator->run();
